<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest;

use PHPStan\BetterReflection\BetterReflection;

abstract class BetterReflectionSingleton
{
    private static BetterReflection $betterReflection;

    final private function __construct()
    {
    }

    public static function instance(): BetterReflection
    {
        return self::$betterReflection ?? self::$betterReflection = new BetterReflection();
    }
}
