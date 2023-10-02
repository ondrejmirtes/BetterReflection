<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\SourceLocator\Type;

use Closure;
use PhpParser\Node;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Parser;
use ReflectionFunction as CoreFunctionReflection;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflection\ReflectionFunction;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Exception\ParseToAstFailure;
use PHPStan\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use PHPStan\BetterReflection\SourceLocator\Exception\EvaledClosureCannotBeLocated;
use PHPStan\BetterReflection\SourceLocator\Exception\NoClosureOnLine;
use PHPStan\BetterReflection\SourceLocator\Exception\TwoClosuresOnSameLine;
use PHPStan\BetterReflection\SourceLocator\FileChecker;
use PHPStan\BetterReflection\SourceLocator\Located\AnonymousLocatedSource;
use PHPStan\BetterReflection\Util\FileHelper;

use function array_filter;
use function assert;
use function file_get_contents;
use function strpos;

/** @internal */
final class ClosureSourceLocator implements SourceLocator
{
    /**
     * @var CoreFunctionReflection
     */
    private $coreFunctionReflection;
    /**
     * @var \PhpParser\Parser
     */
    private $parser;

    public function __construct(Closure $closure, Parser $parser)
    {
        $this->parser = $parser;
        $this->coreFunctionReflection = new CoreFunctionReflection($closure);
    }

    /**
     * {@inheritDoc}
     *
     * @throws ParseToAstFailure
     */
    public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?\PHPStan\BetterReflection\Reflection\Reflection
    {
        return $this->getReflectionFunction($reflector, $identifier->getType());
    }

    /**
     * {@inheritDoc}
     *
     * @throws ParseToAstFailure
     */
    public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
    {
        return array_filter([$this->getReflectionFunction($reflector, $identifierType)]);
    }

    private function getReflectionFunction(Reflector $reflector, IdentifierType $identifierType): ?\PHPStan\BetterReflection\Reflection\ReflectionFunction
    {
        if (! $identifierType->isFunction()) {
            return null;
        }

        /** @phpstan-var non-empty-string $fileName */
        $fileName = $this->coreFunctionReflection->getFileName();

        if (strpos($fileName, 'eval()\'d code') !== false) {
            throw EvaledClosureCannotBeLocated::create();
        }

        FileChecker::assertReadableFile($fileName);

        $fileName = FileHelper::normalizeWindowsPath($fileName);

        $nodeVisitor = new class ($fileName, $this->coreFunctionReflection->getStartLine()) extends NodeVisitorAbstract
        {
            /** @var list<array{node: Node\Expr\Closure|Node\Expr\ArrowFunction, namespace: Namespace_|null}> */
            private $closureNodes = [];

            /**
             * @var \PhpParser\Node\Stmt\Namespace_|null
             */
            private $currentNamespace = null;
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
                if ($node instanceof Namespace_) {
                    $this->currentNamespace = $node;

                    return null;
                }

                if (
                    $node->getStartLine() === $this->startLine
                    && ($node instanceof Node\Expr\Closure || $node instanceof Node\Expr\ArrowFunction)
                ) {
                    $this->closureNodes[] = ['node' => $node, 'namespace' => $this->currentNamespace];
                }

                return null;
            }

            /**
             * {@inheritDoc}
             */
            public function leaveNode(Node $node)
            {
                if (! ($node instanceof Namespace_)) {
                    return null;
                }

                $this->currentNamespace = null;

                return null;
            }

            /**
             * @return array{node: Node\Expr\Closure|Node\Expr\ArrowFunction, namespace: Namespace_|null}
             *
             * @throws NoClosureOnLine
             * @throws TwoClosuresOnSameLine
             */
            public function getClosureNodes(): array
            {
                if ($this->closureNodes === []) {
                    throw NoClosureOnLine::create($this->fileName, $this->startLine);
                }

                if (isset($this->closureNodes[1])) {
                    throw TwoClosuresOnSameLine::create($this->fileName, $this->startLine);
                }

                return $this->closureNodes[0];
            }
        };

        $fileContents = file_get_contents($fileName);
        /** @var list<Node\Stmt> $ast */
        $ast = $this->parser->parse($fileContents);

        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new NameResolver());
        $nodeTraverser->addVisitor($nodeVisitor);
        $nodeTraverser->traverse($ast);

        $closureNodes = $nodeVisitor->getClosureNodes();

        $reflectionFunction = (new NodeToReflection())->__invoke($reflector, $closureNodes['node'], new AnonymousLocatedSource($fileContents, $fileName), $closureNodes['namespace']);
        assert($reflectionFunction instanceof ReflectionFunction);

        return $reflectionFunction;
    }
}
