<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection;

use Attribute;
use LogicException;
use PhpParser\Node;
use PhpParser\Node\Expr;
use ReflectionClass as CoreReflectionClass;
use PHPStan\BetterReflection\NodeCompiler\CompileNodeToValue;
use PHPStan\BetterReflection\NodeCompiler\CompilerContext;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionAttribute as ReflectionAttributeAdapter;
use PHPStan\BetterReflection\Reflection\StringCast\ReflectionAttributeStringCast;
use PHPStan\BetterReflection\Reflector\Reflector;

use function array_map;

/** @psalm-immutable */
class ReflectionAttribute
{
    private Reflector $reflector;
    /**
     * @var \PHPStan\BetterReflection\Reflection\ReflectionClass|\PHPStan\BetterReflection\Reflection\ReflectionMethod|\PHPStan\BetterReflection\Reflection\ReflectionFunction|\PHPStan\BetterReflection\Reflection\ReflectionConstant|\PHPStan\BetterReflection\Reflection\ReflectionClassConstant|\PHPStan\BetterReflection\Reflection\ReflectionEnumCase|\PHPStan\BetterReflection\Reflection\ReflectionProperty|\PHPStan\BetterReflection\Reflection\ReflectionParameter
     */
    private $owner;
    private bool $isRepeated;
    /** @var class-string */
    private string $name;

    /** @var array<int|string, Node\Expr> */
    private array $arguments;

    /** @internal
     * @param \PHPStan\BetterReflection\Reflection\ReflectionClass|\PHPStan\BetterReflection\Reflection\ReflectionMethod|\PHPStan\BetterReflection\Reflection\ReflectionFunction|\PHPStan\BetterReflection\Reflection\ReflectionConstant|\PHPStan\BetterReflection\Reflection\ReflectionClassConstant|\PHPStan\BetterReflection\Reflection\ReflectionEnumCase|\PHPStan\BetterReflection\Reflection\ReflectionProperty|\PHPStan\BetterReflection\Reflection\ReflectionParameter $owner */
    public function __construct(Reflector $reflector, Node\Attribute $node, $owner, bool $isRepeated)
    {
        $this->reflector = $reflector;
        $this->owner = $owner;
        $this->isRepeated = $isRepeated;
        /** @var class-string $name */
        $name = $node->name->toString();
        $this->name = $name;
        $arguments = [];
        foreach ($node->args as $argNo => $arg) {
            $arguments[(($nullsafeVariable1 = $arg->name) ? $nullsafeVariable1->toString() : null) ?? $argNo] = $arg->value;
        }
        $this->arguments = $arguments;
    }

    /**
     * @return array<string, mixed>
     */
    public function exportToCache(): array
    {
        return [
            'name' => $this->name,
            'isRepeated' => $this->isRepeated,
            'arguments' => array_map(
                static fn (Expr $expr) => ExprCacheHelper::export($expr),
                $this->arguments,
            ),
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @param ReflectionClass|ReflectionMethod|ReflectionFunction|ReflectionConstant|ReflectionClassConstant|ReflectionEnumCase|ReflectionProperty|ReflectionParameter $owner
     */
    public static function importFromCache(Reflector $reflector, array $data, $owner): self
    {
        $reflection = new CoreReflectionClass(self::class);
        /** @var self $ref */
        $ref = $reflection->newInstanceWithoutConstructor();
        $ref->reflector = $reflector;

        $ref->owner = $owner;
        $ref->name = $data['name'];
        $ref->isRepeated = $data['isRepeated'];

        $ref->arguments = array_map(
            static fn (array $exprData) => ExprCacheHelper::import($exprData),
            $data['arguments'],
        );

        return $ref;
    }

    /** @internal
     * @param \PHPStan\BetterReflection\Reflection\ReflectionClass|\PHPStan\BetterReflection\Reflection\ReflectionMethod|\PHPStan\BetterReflection\Reflection\ReflectionFunction|\PHPStan\BetterReflection\Reflection\ReflectionConstant|\PHPStan\BetterReflection\Reflection\ReflectionClassConstant|\PHPStan\BetterReflection\Reflection\ReflectionEnumCase|\PHPStan\BetterReflection\Reflection\ReflectionProperty|\PHPStan\BetterReflection\Reflection\ReflectionParameter $owner */
    public function withOwner($owner): self
    {
        $clone        = clone $this;
        $clone->owner = $owner;

        return $clone;
    }

    /** @return class-string */
    public function getName(): string
    {
        return $this->name;
    }

    public function getClass(): ReflectionClass
    {
        return $this->reflector->reflectClass($this->getName());
    }

    /** @return array<int|string, Node\Expr> */
    public function getArgumentsExpressions(): array
    {
        return $this->arguments;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getArguments(): array
    {
        $compiler = new CompileNodeToValue();
        $context  = new CompilerContext($this->reflector, $this->owner);

        return array_map(static fn (Node\Expr $value) => $compiler->__invoke($value, $context)->value, $this->arguments);
    }

    /** @return int-mask-of<Attribute::TARGET_*>|ReflectionAttributeAdapter::TARGET_CONSTANT_COMPATIBILITY */
    public function getTarget(): int
    {
        switch (true) {
            case $this->owner instanceof ReflectionClass:
                return Attribute::TARGET_CLASS;
            case $this->owner instanceof ReflectionFunction:
                return Attribute::TARGET_FUNCTION;
            case $this->owner instanceof ReflectionConstant:
                return ReflectionAttributeAdapter::TARGET_CONSTANT_COMPATIBILITY;
            case $this->owner instanceof ReflectionMethod:
                return Attribute::TARGET_METHOD;
            case $this->owner instanceof ReflectionProperty:
                return Attribute::TARGET_PROPERTY;
            case $this->owner instanceof ReflectionClassConstant:
                return Attribute::TARGET_CLASS_CONSTANT;
            case $this->owner instanceof ReflectionEnumCase:
                return Attribute::TARGET_CLASS_CONSTANT;
            case $this->owner instanceof ReflectionParameter:
                return Attribute::TARGET_PARAMETER;
            default:
                throw new LogicException('unknown owner');
        }
    }

    public function isRepeated(): bool
    {
        return $this->isRepeated;
    }

    /** @return non-empty-string */
    public function __toString(): string
    {
        return ReflectionAttributeStringCast::toString($this);
    }
}
