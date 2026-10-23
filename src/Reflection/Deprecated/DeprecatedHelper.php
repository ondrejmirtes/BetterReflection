<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Deprecated;

use Deprecated;
use Roave\BetterReflection\Reflection\Annotation\AnnotationHelper;
use Roave\BetterReflection\Reflection\Attribute\ReflectionAttributeHelper;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;
use Roave\BetterReflection\Reflection\ReflectionConstant;
use Roave\BetterReflection\Reflection\ReflectionEnumCase;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionProperty;

/** @internal */
final class DeprecatedHelper
{
    /** @psalm-pure
     * @param \Roave\BetterReflection\Reflection\ReflectionClass|\Roave\BetterReflection\Reflection\ReflectionMethod|\Roave\BetterReflection\Reflection\ReflectionFunction|\Roave\BetterReflection\Reflection\ReflectionConstant|\Roave\BetterReflection\Reflection\ReflectionClassConstant|\Roave\BetterReflection\Reflection\ReflectionEnumCase|\Roave\BetterReflection\Reflection\ReflectionProperty $reflection */
    public static function isDeprecated($reflection): bool
    {
        if (ReflectionAttributeHelper::filterAttributesByName($reflection->getAttributes(), Deprecated::class) !== []) {
            return true;
        }

        return AnnotationHelper::isDeprecated($reflection->getDocComment());
    }
}
