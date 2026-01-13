<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection;

use PropertyHookType as CoreReflectionPropertyHookType;

/**
 * It's a copy of native PropertyHookType enum.
 *
 * It's used to avoid dependency on native enum in better ReflectionProperty class.
 * Thanks to this we can support PHP version < 8.4.
 *
 * @see CoreReflectionPropertyHookType
 */
class ReflectionPropertyHookType
{
    const Get = 'get';
    const Set = 'set';

    public static function fromCoreReflectionPropertyHookType(CoreReflectionPropertyHookType $hookType): string
    {
        if ($hookType === CoreReflectionPropertyHookType::Get) {
            return self::Get;
        }

        if ($hookType === CoreReflectionPropertyHookType::Set) {
            return self::Set;
        }

        throw new \LogicException('Unknown hook type');
    }
}
