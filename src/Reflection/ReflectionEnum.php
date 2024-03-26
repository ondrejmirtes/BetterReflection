<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use LogicException;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_ as ClassNode;
use PhpParser\Node\Stmt\Enum_ as EnumNode;
use PhpParser\Node\Stmt\Interface_ as InterfaceNode;
use PhpParser\Node\Stmt\Trait_ as TraitNode;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;

use function array_combine;
use function array_filter;
use function array_key_exists;
use function array_map;
use function assert;

/** @psalm-immutable */
class ReflectionEnum extends ReflectionClass
{
    /**
     * @var \Roave\BetterReflection\Reflection\ReflectionNamedType|null
     */
    private $backingType;

    /** @var array<non-empty-string, ReflectionEnumCase> */
    private $cases;
    /**
     * @var \Roave\BetterReflection\Reflector\Reflector
     */
    private $reflector;
    /** @phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found */
    private function __construct(Reflector $reflector, EnumNode $node, LocatedSource $locatedSource, ?string $namespace = null)
    {
        $this->reflector = $reflector;
        parent::__construct($reflector, $node, $locatedSource, $namespace);
        $this->backingType = $this->createBackingType($node);
        $this->cases       = $this->createCases($node);
    }
    /**
     * @internal
     * @param ClassNode|InterfaceNode|TraitNode|EnumNode $node
     * @return $this
     */
    public static function createFromNode(Reflector $reflector, $node, LocatedSource $locatedSource, ?string $namespace = null): \Roave\BetterReflection\Reflection\ReflectionClass
    {
        $node = $node;
        assert($node instanceof EnumNode);
        return new self($reflector, $node, $locatedSource, $namespace);
    }

    /** @param non-empty-string $name */
    public function hasCase(string $name): bool
    {
        return array_key_exists($name, $this->cases);
    }

    /** @param non-empty-string $name */
    public function getCase(string $name): ?\Roave\BetterReflection\Reflection\ReflectionEnumCase
    {
        return $this->cases[$name] ?? null;
    }

    /** @return array<non-empty-string, ReflectionEnumCase> */
    public function getCases(): array
    {
        return $this->cases;
    }

    /** @return array<non-empty-string, ReflectionEnumCase> */
    private function createCases(EnumNode $node): array
    {
        $enumCasesNodes = array_filter($node->stmts, static function (Node\Stmt $stmt) : bool {
            return $stmt instanceof Node\Stmt\EnumCase;
        });

        return array_combine(array_map(static function (Node\Stmt\EnumCase $enumCaseNode): string {
            $enumCaseName = $enumCaseNode->name->toString();
            assert($enumCaseName !== '');

            return $enumCaseName;
        }, $enumCasesNodes), array_map(function (Node\Stmt\EnumCase $enumCaseNode) : ReflectionEnumCase {
            return ReflectionEnumCase::createFromNode($this->reflector, $enumCaseNode, $this);
        }, $enumCasesNodes));
    }

    public function isBacked(): bool
    {
        return $this->backingType !== null;
    }

    public function getBackingType(): ReflectionNamedType
    {
        if ($this->backingType === null) {
            throw new LogicException('This enum does not have a backing type available');
        }

        return $this->backingType;
    }

    private function createBackingType(EnumNode $node): ?\Roave\BetterReflection\Reflection\ReflectionNamedType
    {
        if ($node->scalarType === null) {
            return null;
        }

        $backingType = ReflectionNamedType::createFromNode($this->reflector, $this, $node->scalarType);
        assert($backingType instanceof ReflectionNamedType);

        return $backingType;
    }
}
