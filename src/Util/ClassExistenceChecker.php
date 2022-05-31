<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Util;

use function class_exists;
use function interface_exists;
use function trait_exists;

/** @internal */
class ClassExistenceChecker
{
    /** @psalm-assert-if-true class-string $name */
    public static function exists(string $name, bool $autoload): bool
    {
        return self::classExists($name, $autoload) || self::interfaceExists($name, $autoload) || self::traitExists($name, $autoload);
    }

    public static function classExists(string $name, bool $autoload): bool
    {
        return class_exists($name, $autoload);
    }

    public static function interfaceExists(string $name, bool $autoload): bool
    {
        return interface_exists($name, $autoload);
    }

    public static function traitExists(string $name, bool $autoload): bool
    {
        return trait_exists($name, $autoload);
    }
}
