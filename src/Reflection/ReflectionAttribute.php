<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use Attribute;
use LogicException;
use PhpParser\Node;
use Roave\BetterReflection\NodeCompiler\CompileNodeToValue;
use Roave\BetterReflection\NodeCompiler\CompilerContext;
use Roave\BetterReflection\Reflection\Adapter\ReflectionAttribute as ReflectionAttributeAdapter;
use Roave\BetterReflection\Reflection\StringCast\ReflectionAttributeStringCast;
use Roave\BetterReflection\Reflector\Reflector;

use function array_map;

/** @psalm-immutable */
class ReflectionAttribute
{
    private Reflector $reflector;
    /**
     * @var \Roave\BetterReflection\Reflection\ReflectionClass|\Roave\BetterReflection\Reflection\ReflectionMethod|\Roave\BetterReflection\Reflection\ReflectionFunction|\Roave\BetterReflection\Reflection\ReflectionConstant|\Roave\BetterReflection\Reflection\ReflectionClassConstant|\Roave\BetterReflection\Reflection\ReflectionEnumCase|\Roave\BetterReflection\Reflection\ReflectionProperty|\Roave\BetterReflection\Reflection\ReflectionParameter
     */
    private $owner;
    private bool $isRepeated;
    /** @var class-string */
    private string $name;

    /** @var array<int|string, Node\Expr> */
    private array $arguments;

    /** @internal
     * @param \Roave\BetterReflection\Reflection\ReflectionClass|\Roave\BetterReflection\Reflection\ReflectionMethod|\Roave\BetterReflection\Reflection\ReflectionFunction|\Roave\BetterReflection\Reflection\ReflectionConstant|\Roave\BetterReflection\Reflection\ReflectionClassConstant|\Roave\BetterReflection\Reflection\ReflectionEnumCase|\Roave\BetterReflection\Reflection\ReflectionProperty|\Roave\BetterReflection\Reflection\ReflectionParameter $owner */
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

    /** @internal
     * @param \Roave\BetterReflection\Reflection\ReflectionClass|\Roave\BetterReflection\Reflection\ReflectionMethod|\Roave\BetterReflection\Reflection\ReflectionFunction|\Roave\BetterReflection\Reflection\ReflectionConstant|\Roave\BetterReflection\Reflection\ReflectionClassConstant|\Roave\BetterReflection\Reflection\ReflectionEnumCase|\Roave\BetterReflection\Reflection\ReflectionProperty|\Roave\BetterReflection\Reflection\ReflectionParameter $owner */
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
