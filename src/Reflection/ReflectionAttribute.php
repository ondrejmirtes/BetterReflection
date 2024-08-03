<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection;

use Attribute;
use LogicException;
use PhpParser\Node;
use PHPStan\BetterReflection\NodeCompiler\CompileNodeToValue;
use PHPStan\BetterReflection\NodeCompiler\CompilerContext;
use PHPStan\BetterReflection\Reflection\StringCast\ReflectionAttributeStringCast;
use PHPStan\BetterReflection\Reflector\Reflector;

use function array_map;
use function assert;

/** @psalm-immutable */
class ReflectionAttribute
{
    /** @var non-empty-string */
    private $name;

    /** @var array<int|string, Node\Expr> */
    private $arguments = [];
    /**
     * @var \PHPStan\BetterReflection\Reflector\Reflector
     */
    private $reflector;
    /**
     * @var \PHPStan\BetterReflection\Reflection\ReflectionClass|\PHPStan\BetterReflection\Reflection\ReflectionMethod|\PHPStan\BetterReflection\Reflection\ReflectionFunction|\PHPStan\BetterReflection\Reflection\ReflectionClassConstant|\PHPStan\BetterReflection\Reflection\ReflectionEnumCase|\PHPStan\BetterReflection\Reflection\ReflectionProperty|\PHPStan\BetterReflection\Reflection\ReflectionParameter
     */
    private $owner;
    /**
     * @var bool
     */
    private $isRepeated;
    /** @internal
     * @param \PHPStan\BetterReflection\Reflection\ReflectionClass|\PHPStan\BetterReflection\Reflection\ReflectionMethod|\PHPStan\BetterReflection\Reflection\ReflectionFunction|\PHPStan\BetterReflection\Reflection\ReflectionClassConstant|\PHPStan\BetterReflection\Reflection\ReflectionEnumCase|\PHPStan\BetterReflection\Reflection\ReflectionProperty|\PHPStan\BetterReflection\Reflection\ReflectionParameter $owner */
    public function __construct(Reflector $reflector, Node\Attribute $node, $owner, bool $isRepeated)
    {
        $this->reflector = $reflector;
        $this->owner = $owner;
        $this->isRepeated = $isRepeated;
        $name = $node->name->toString();
        assert($name !== '');
        $this->name = $name;
        foreach ($node->args as $argNo => $arg) {
            $this->arguments[(($argName = $arg->name) ? $argName->toString() : null) ?? $argNo] = $arg->value;
        }
    }

    /** @internal
     * @param \PHPStan\BetterReflection\Reflection\ReflectionClass|\PHPStan\BetterReflection\Reflection\ReflectionMethod|\PHPStan\BetterReflection\Reflection\ReflectionFunction|\PHPStan\BetterReflection\Reflection\ReflectionClassConstant|\PHPStan\BetterReflection\Reflection\ReflectionEnumCase|\PHPStan\BetterReflection\Reflection\ReflectionProperty|\PHPStan\BetterReflection\Reflection\ReflectionParameter $owner */
    public function withOwner($owner): self
    {
        $clone        = clone $this;
        $clone->owner = $owner;

        return $clone;
    }

    /** @return non-empty-string */
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
     * @deprecated Use getArgumentsExpressions()
     * @return array<int|string, mixed>
     */
    public function getArguments(): array
    {
        $compiler = new CompileNodeToValue();
        $context  = new CompilerContext($this->reflector, $this->owner);

        return array_map(static function (Node\Expr $value) use ($compiler, $context) {
            return $compiler->__invoke($value, $context)->value;
        }, $this->arguments);
    }

    /** @return int-mask-of<Attribute::TARGET_*> */
    public function getTarget(): int
    {
        switch (true) {
            case $this->owner instanceof ReflectionClass:
                return Attribute::TARGET_CLASS;
            case $this->owner instanceof ReflectionFunction:
                return Attribute::TARGET_FUNCTION;
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
