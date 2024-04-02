<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection\StringCast;

use PHPStan\BetterReflection\Reflection\ReflectionEnumCase;

use function gettype;
use function sprintf;

/** @internal */
final class ReflectionEnumCaseStringCast
{
    /**
     * @return non-empty-string
     *
     * @psalm-pure
     */
    public static function toString(ReflectionEnumCase $enumCaseReflection): string
    {
        $enumReflection = $enumCaseReflection->getDeclaringEnum();

        $value = $enumReflection->isBacked() ? $enumCaseReflection->getValue() : 'Object';
        $type  = $enumReflection->isBacked() ? gettype($value) : $enumReflection->getName();

        return sprintf("Constant [ public %s %s ] { %s }\n", $type, $enumCaseReflection->getName(), $value);
    }
}
