<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use LogicException;
use OutOfBoundsException;
use PhpParser\Node\Expr;
use ReflectionClass as CoreReflectionClass;
use ReflectionFunctionAbstract as CoreReflectionFunctionAbstract;
use ReflectionParameter as CoreReflectionParameter;
use ReturnTypeWillChange;
use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionIntersectionType as BetterReflectionIntersectionType;
use Roave\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionNamedType as BetterReflectionNamedType;
use Roave\BetterReflection\Reflection\ReflectionParameter as BetterReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionType as BetterReflectionType;
use Roave\BetterReflection\Reflection\ReflectionUnionType as BetterReflectionUnionType;
use ValueError;

use function array_map;
use function count;
use function sprintf;
use function strtolower;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 * @psalm-immutable
 */
final class ReflectionParameter extends CoreReflectionParameter
{
    /**
     * @var BetterReflectionParameter
     */
    private $betterReflectionParameter;
    public function __construct(BetterReflectionParameter $betterReflectionParameter)
    {
        $this->betterReflectionParameter = $betterReflectionParameter;
        unset($this->name);
    }

    /** @return non-empty-string */
    public function __toString(): string
    {
        return $this->betterReflectionParameter->__toString();
    }

    public function getName(): string
    {
        return $this->betterReflectionParameter->getName();
    }

    public function isPassedByReference(): bool
    {
        return $this->betterReflectionParameter->isPassedByReference();
    }

    public function canBePassedByValue(): bool
    {
        return $this->betterReflectionParameter->canBePassedByValue();
    }

    /**
     * @return ReflectionFunction|ReflectionMethod
     */
    public function getDeclaringFunction(): CoreReflectionFunctionAbstract
    {
        $function = $this->betterReflectionParameter->getDeclaringFunction();

        if ($function instanceof BetterReflectionMethod) {
            return new ReflectionMethod($function);
        }

        return new ReflectionFunction($function);
    }

    /** @return ReflectionClass|null */
    public function getDeclaringClass(): ?CoreReflectionClass
    {
        $declaringClass = $this->betterReflectionParameter->getDeclaringClass();

        if ($declaringClass === null) {
            return null;
        }

        return new ReflectionClass($declaringClass);
    }

    /** @return ReflectionClass|null */
    public function getClass(): ?CoreReflectionClass
    {
        $type = $this->betterReflectionParameter->getType();

        if ($type === null) {
            return null;
        }

        if ($type instanceof BetterReflectionIntersectionType) {
            return null;
        }

        if ($type instanceof BetterReflectionNamedType) {
            $classType = $type;
        } else {
            $unionTypes = $type->getTypes();

            if (count($unionTypes) !== 2) {
                return null;
            }

            if (! $type->allowsNull()) {
                return null;
            }

            foreach ($unionTypes as $unionInnerType) {
                if (! $unionInnerType instanceof BetterReflectionNamedType) {
                    return null;
                }

                if ($unionInnerType->allowsNull()) {
                    continue;
                }

                $classType = $unionInnerType;
                break;
            }
        }

        try {
            /** @phpstan-ignore-next-line */
            return new ReflectionClass($classType->getClass());
        } catch (LogicException $exception) {
            return null;
        }
    }

    public function isArray(): bool
    {
        return $this->isType($this->betterReflectionParameter->getType(), 'array');
    }

    public function isCallable(): bool
    {
        return $this->isType($this->betterReflectionParameter->getType(), 'callable');
    }

    /**
     * For isArray() and isCallable().
     * @param BetterReflectionNamedType|BetterReflectionUnionType|BetterReflectionIntersectionType|null $typeReflection
     */
    private function isType($typeReflection, string $type): bool
    {
        if ($typeReflection === null) {
            return false;
        }

        if ($typeReflection instanceof BetterReflectionIntersectionType) {
            return false;
        }

        $isOneOfAllowedTypes = static function (BetterReflectionType $namedType, string ...$types): bool {
            foreach ($types as $type) {
                if ($namedType instanceof BetterReflectionNamedType && strtolower($namedType->getName()) === $type) {
                    return true;
                }
            }

            return false;
        };

        if ($typeReflection instanceof BetterReflectionUnionType) {
            $unionTypes = $typeReflection->getTypes();

            foreach ($unionTypes as $unionType) {
                if (! $isOneOfAllowedTypes($unionType, $type, 'null')) {
                    return false;
                }
            }

            return true;
        }

        return $isOneOfAllowedTypes($typeReflection, $type);
    }

    public function allowsNull(): bool
    {
        return $this->betterReflectionParameter->allowsNull();
    }

    public function getPosition(): int
    {
        return $this->betterReflectionParameter->getPosition();
    }

    public function isOptional(): bool
    {
        return $this->betterReflectionParameter->isOptional();
    }

    public function isVariadic(): bool
    {
        return $this->betterReflectionParameter->isVariadic();
    }

    public function isDefaultValueAvailable(): bool
    {
        return $this->betterReflectionParameter->isDefaultValueAvailable();
    }

    /**
     * @deprecated Use getDefaultValueExpression()
     */
    #[ReturnTypeWillChange]
    public function getDefaultValue()
    {
        return $this->betterReflectionParameter->getDefaultValue();
    }

    /**
     * @deprecated Use getDefaultValueExpression()
     */
    public function getDefaultValueExpr(): Expr
    {
        return $this->betterReflectionParameter->getDefaultValueExpression();
    }

    public function getDefaultValueExpression(): Expr
    {
        return $this->betterReflectionParameter->getDefaultValueExpression();
    }

    public function isDefaultValueConstant(): bool
    {
        return $this->betterReflectionParameter->isDefaultValueConstant();
    }

    public function getDefaultValueConstantName(): string
    {
        return $this->betterReflectionParameter->getDefaultValueConstantName();
    }

    public function hasType(): bool
    {
        return $this->betterReflectionParameter->hasType();
    }

    /**
     * @return ReflectionUnionType|ReflectionNamedType|ReflectionIntersectionType|null
     */
    public function getType(): ?\ReflectionType
    {
        return ReflectionType::fromTypeOrNull($this->betterReflectionParameter->getType());
    }

    public function isPromoted(): bool
    {
        return $this->betterReflectionParameter->isPromoted();
    }

    /**
     * @param class-string|null $name
     *
     * @return list<ReflectionAttribute|FakeReflectionAttribute>
     */
    public function getAttributes(?string $name = null, int $flags = 0): array
    {
        if ($flags !== 0 && $flags !== ReflectionAttribute::IS_INSTANCEOF) {
            throw new ValueError('Argument #2 ($flags) must be a valid attribute filter flag');
        }

        if ($name !== null && $flags !== 0) {
            $attributes = $this->betterReflectionParameter->getAttributesByInstance($name);
        } elseif ($name !== null) {
            $attributes = $this->betterReflectionParameter->getAttributesByName($name);
        } else {
            $attributes = $this->betterReflectionParameter->getAttributes();
        }

        /** @psalm-suppress ImpureFunctionCall */
        return array_map(static function (BetterReflectionAttribute $betterReflectionAttribute) {
            return ReflectionAttributeFactory::create($betterReflectionAttribute);
        }, $attributes);
    }

    /**
     * @return mixed
     */
    public function __get(string $name)
    {
        if ($name === 'name') {
            return $this->betterReflectionParameter->getName();
        }

        throw new OutOfBoundsException(sprintf('Property %s::$%s does not exist.', self::class, $name));
    }
}
