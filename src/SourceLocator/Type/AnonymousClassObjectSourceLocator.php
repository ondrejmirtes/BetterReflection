<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\SourceLocator\Type;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Parser;
use ReflectionClass as CoreReflectionClass;
use ReflectionException;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Exception\ParseToAstFailure;
use PHPStan\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use PHPStan\BetterReflection\SourceLocator\Exception\EvaledAnonymousClassCannotBeLocated;
use PHPStan\BetterReflection\SourceLocator\Exception\NoAnonymousClassOnLine;
use PHPStan\BetterReflection\SourceLocator\Exception\TwoAnonymousClassesOnSameLine;
use PHPStan\BetterReflection\SourceLocator\FileChecker;
use PHPStan\BetterReflection\SourceLocator\Located\AnonymousLocatedSource;
use PHPStan\BetterReflection\Util\FileHelper;

use function array_filter;
use function assert;
use function file_get_contents;
use function strpos;

/** @internal */
final class AnonymousClassObjectSourceLocator implements SourceLocator
{
    /**
     * @var CoreReflectionClass
     */
    private $coreClassReflection;
    /**
     * @var \PhpParser\Parser
     */
    private $parser;

    /** @throws ReflectionException */
    public function __construct(object $anonymousClassObject, Parser $parser)
    {
        $this->parser = $parser;
        $this->coreClassReflection = new CoreReflectionClass($anonymousClassObject);
    }

    /**
     * {@inheritDoc}
     *
     * @throws ParseToAstFailure
     */
    public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?\PHPStan\BetterReflection\Reflection\Reflection
    {
        return $this->getReflectionClass($reflector, $identifier->getType());
    }

    /**
     * {@inheritDoc}
     *
     * @throws ParseToAstFailure
     */
    public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
    {
        return array_filter([$this->getReflectionClass($reflector, $identifierType)]);
    }

    private function getReflectionClass(Reflector $reflector, IdentifierType $identifierType): ?\PHPStan\BetterReflection\Reflection\ReflectionClass
    {
        if (! $identifierType->isClass()) {
            return null;
        }

        if (! $this->coreClassReflection->isAnonymous()) {
            return null;
        }

        /** @phpstan-var non-empty-string $fileName */
        $fileName = $this->coreClassReflection->getFileName();

        if (strpos($fileName, 'eval()\'d code') !== false) {
            throw EvaledAnonymousClassCannotBeLocated::create();
        }

        FileChecker::assertReadableFile($fileName);

        $fileName = FileHelper::normalizeWindowsPath($fileName);

        $nodeVisitor = new class ($fileName, $this->coreClassReflection->getStartLine()) extends NodeVisitorAbstract
        {
            /** @var list<Class_> */
            private $anonymousClassNodes = [];
            /**
             * @var string
             */
            private $fileName;
            /**
             * @var int
             */
            private $startLine;

            public function __construct(string $fileName, int $startLine)
            {
                $this->fileName = $fileName;
                $this->startLine = $startLine;
            }

            /**
             * {@inheritDoc}
             */
            public function enterNode(Node $node)
            {
                if (! ($node instanceof Node\Stmt\Class_) || $node->name !== null || $node->getLine() !== $this->startLine) {
                    return null;
                }

                $this->anonymousClassNodes[] = $node;

                return null;
            }

            public function getAnonymousClassNode(): Class_
            {
                if ($this->anonymousClassNodes === []) {
                    throw NoAnonymousClassOnLine::create($this->fileName, $this->startLine);
                }

                if (isset($this->anonymousClassNodes[1])) {
                    throw TwoAnonymousClassesOnSameLine::create($this->fileName, $this->startLine);
                }

                return $this->anonymousClassNodes[0];
            }
        };

        $fileContents = file_get_contents($fileName);
        /** @var list<Node\Stmt> $ast */
        $ast = $this->parser->parse($fileContents);

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new NameResolver());
        $nodeTraverser->addVisitor($nodeVisitor);
        $nodeTraverser->traverse($ast);

        $reflectionClass = (new NodeToReflection())->__invoke($reflector, $nodeVisitor->getAnonymousClassNode(), new AnonymousLocatedSource($fileContents, $fileName), null);
        assert($reflectionClass instanceof ReflectionClass);

        return $reflectionClass;
    }
}
