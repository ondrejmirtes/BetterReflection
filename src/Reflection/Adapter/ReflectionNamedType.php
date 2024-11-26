<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection\Adapter;

use ReflectionNamedType as CoreReflectionNamedType;
use PHPStan\BetterReflection\Reflection\ReflectionNamedType as BetterReflectionNamedType;

use function strtolower;

/** @psalm-immutable */
final class ReflectionNamedType extends CoreReflectionNamedType
{
    private bool $allowsNull;
    /** @var non-empty-string */
    private string $nameType;

    private bool $isBuiltin;

    /** @var non-empty-string */
    private string $toString;

    /** @param \PHPStan\BetterReflection\Reflection\ReflectionNamedType|non-empty-string $type */
    public function __construct($type, bool $allowsNull)
    {
        $this->allowsNull = $allowsNull;
        if ($type instanceof BetterReflectionNamedType) {
            $nameType        = $type->getName();
            $this->nameType  = $nameType;
            $this->isBuiltin = $this->computeIsBuiltin($nameType, $type->isBuiltin());
            $this->toString  = $type->__toString();
        } else {
            $this->nameType  = $type;
            $this->isBuiltin = true;
            $this->toString  = $type;
        }
    }

    /** @return non-empty-string */
    public function getName(): string
    {
        return $this->nameType;
    }

    /** @return non-empty-string */
    public function __toString(): string
    {
        $normalizedType = strtolower($this->nameType);

        if (
            ! $this->allowsNull
            || $normalizedType === 'mixed'
            || $normalizedType === 'null'
        ) {
            return $this->toString;
        }

        return '?' . $this->toString;
    }

    public function allowsNull(): bool
    {
        return $this->allowsNull;
    }

    public function isBuiltin(): bool
    {
        return $this->isBuiltin;
    }

    private function computeIsBuiltin(string $namedType, bool $isBuiltin): bool
    {
        $normalizedType = strtolower($namedType);

        if ($normalizedType === 'self' || $normalizedType === 'parent' || $normalizedType === 'static') {
            return false;
        }

        return $isBuiltin;
    }

    public function isIdentifier(): bool
    {
        return $this->betterReflectionType->isIdentifier();
    }
}
