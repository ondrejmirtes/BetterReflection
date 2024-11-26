<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use PropertyHookType as CoreReflectionPropertyHookType;

class ReflectionPropertyHookType
{
    const Get = 'get';
    const Set = 'set';

    /** @psalm-suppress UndefinedClass */
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
