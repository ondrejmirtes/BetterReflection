<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection;

use ReflectionProperty as CoreReflectionProperty;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionProperty;

final class AttributesResetter
{

    public static function resetClass(ReflectionClass $class): void
    {
        $rmDeclaringClass = new CoreReflectionProperty(ReflectionMethod::class, 'declaringClass');
        $rmDeclaringClass->setAccessible(true);
        $rmImplementingClass = new CoreReflectionProperty(ReflectionMethod::class, 'implementingClass');
        $rmImplementingClass->setAccessible(true);
        $rmCurrentClass = new CoreReflectionProperty(ReflectionMethod::class, 'currentClass');
        $rmCurrentClass->setAccessible(true);
        foreach ($class->getImmediateMethods() as $method) {
            $rmDeclaringClass->setValue($method, null);
            $rmImplementingClass->setValue($method, null);
            $rmCurrentClass->setValue($method, null);
        }

        $rpDeclaringClass = new CoreReflectionProperty(ReflectionProperty::class, 'declaringClass');
        $rpDeclaringClass->setAccessible(true);
        $rpImplementingClass = new CoreReflectionProperty(ReflectionProperty::class, 'implementingClass');
        $rpImplementingClass->setAccessible(true);
        foreach ($class->getImmediateProperties() as $property) {
            if ($property->getDefaultValueExpression() === null) {
                continue;
            }
            $rmDeclaringClass->setValue($property, null);
            $rmImplementingClass->setValue($property, null);
        }

        $rcDeclaringClass = new CoreReflectionProperty(ReflectionClassConstant::class, 'declaringClass');
        $rcDeclaringClass->setAccessible(true);
        $rcImplementingClass = new CoreReflectionProperty(ReflectionClassConstant::class, 'implementingClass');
        $rcImplementingClass->setAccessible(true);
        foreach ($class->getImmediateConstants() as $constant) {
            $rcDeclaringClass->setValue($constant, null);
            $rcImplementingClass->setValue($constant, null);
        }
    }

}
