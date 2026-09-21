<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection\Adapter;

use PHPStan\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;

use const PHP_VERSION_ID;

final class ReflectionAttributeFactory
{
    /**
     * @return \PHPStan\BetterReflection\Reflection\Adapter\ReflectionAttribute|\PHPStan\BetterReflection\Reflection\Adapter\FakeReflectionAttribute
     */
    public static function create(BetterReflectionAttribute $betterReflectionAttribute)
    {
        if (PHP_VERSION_ID >= 80000 && PHP_VERSION_ID < 80012) {
            return new FakeReflectionAttribute($betterReflectionAttribute);
        }

        return new ReflectionAttribute($betterReflectionAttribute);
    }
}
