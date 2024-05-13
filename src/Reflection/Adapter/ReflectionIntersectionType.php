<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection\Adapter;

use ReflectionIntersectionType as CoreReflectionIntersectionType;
use PHPStan\BetterReflection\Reflection\ReflectionIntersectionType as BetterReflectionIntersectionType;
use PHPStan\BetterReflection\Reflection\ReflectionNamedType as BetterReflectionNamedType;

use function array_map;
use function assert;

/** @psalm-immutable */
class ReflectionIntersectionType extends CoreReflectionIntersectionType
{
    /**
     * @var BetterReflectionIntersectionType
     */
    private $betterReflectionType;
    public function __construct(BetterReflectionIntersectionType $betterReflectionType)
    {
        $this->betterReflectionType = $betterReflectionType;
    }

    /** @return non-empty-list<ReflectionNamedType> */
    public function getTypes(): array
    {
        return array_map(static function (BetterReflectionNamedType $type): ReflectionNamedType {
            $adapterType = ReflectionType::fromType($type);
            assert($adapterType instanceof ReflectionNamedType);

            return $adapterType;
        }, $this->betterReflectionType->getTypes());
    }

    /** @return non-empty-string */
    public function __toString(): string
    {
        return $this->betterReflectionType->__toString();
    }

    /** @return false */
    public function allowsNull(): bool
    {
        return $this->betterReflectionType->allowsNull();
    }
}
