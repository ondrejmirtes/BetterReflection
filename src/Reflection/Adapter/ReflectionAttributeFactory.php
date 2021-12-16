<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;

use const PHP_VERSION_ID;

final class ReflectionAttributeFactory
{
    public static function create(BetterReflectionAttribute $betterReflectionAttribute): ReflectionAttribute|FakeReflectionAttribute
    {
        if (PHP_VERSION_ID >= 80000 && PHP_VERSION_ID < 80012) {
            return new FakeReflectionAttribute($betterReflectionAttribute);
        }

        return new ReflectionAttribute($betterReflectionAttribute);
    }
}
