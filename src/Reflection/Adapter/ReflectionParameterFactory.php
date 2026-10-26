<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection\Adapter;

use PHPStan\BetterReflection\Reflection\ReflectionParameter as BetterReflectionParameter;

use const PHP_VERSION_ID;

final class ReflectionParameterFactory
{
    public static function create(BetterReflectionParameter $betterReflectionParameter): ReflectionParameter
    {
        if (PHP_VERSION_ID >= 80600) {
            return new ReflectionParameterWithDocComment($betterReflectionParameter);
        }

        return new ReflectionParameter($betterReflectionParameter);
    }
}
