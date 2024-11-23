<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Deprecated;

use Roave\BetterReflection\Reflection\Annotation\AnnotationHelper;
use Roave\BetterReflection\Reflection\Attribute\ReflectionAttributeHelper;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;
use Roave\BetterReflection\Reflection\ReflectionEnumCase;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionProperty;

/** @internal */
final class DeprecatedHelper
{
    /** @psalm-pure */
    public static function isDeprecated(ReflectionClass|ReflectionMethod|ReflectionFunction|ReflectionClassConstant|ReflectionEnumCase|ReflectionProperty $reflection): bool
    {
        // We don't use Deprecated::class because the class is currently missing in stubs
        if (ReflectionAttributeHelper::filterAttributesByName($reflection->getAttributes(), 'Deprecated') !== []) {
            return true;
        }

        return AnnotationHelper::isDeprecated($reflection->getDocComment());
    }
}
