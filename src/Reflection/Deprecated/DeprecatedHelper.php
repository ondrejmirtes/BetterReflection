<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection\Deprecated;

use PHPStan\BetterReflection\Reflection\Annotation\AnnotationHelper;
use PHPStan\BetterReflection\Reflection\Attribute\ReflectionAttributeHelper;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionClassConstant;
use PHPStan\BetterReflection\Reflection\ReflectionEnumCase;
use PHPStan\BetterReflection\Reflection\ReflectionFunction;
use PHPStan\BetterReflection\Reflection\ReflectionMethod;
use PHPStan\BetterReflection\Reflection\ReflectionProperty;

/** @internal */
final class DeprecatedHelper
{
    /** @psalm-pure
     * @param \PHPStan\BetterReflection\Reflection\ReflectionClass|\PHPStan\BetterReflection\Reflection\ReflectionMethod|\PHPStan\BetterReflection\Reflection\ReflectionFunction|\PHPStan\BetterReflection\Reflection\ReflectionClassConstant|\PHPStan\BetterReflection\Reflection\ReflectionEnumCase|\PHPStan\BetterReflection\Reflection\ReflectionProperty $reflection */
    public static function isDeprecated($reflection): bool
    {
        // We don't use Deprecated::class because the class is currently missing in stubs
        if (ReflectionAttributeHelper::filterAttributesByName($reflection->getAttributes(), 'Deprecated') !== []) {
            return true;
        }

        return AnnotationHelper::isDeprecated($reflection->getDocComment());
    }
}
