<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use PropertyHookType as CoreReflectionPropertyHookType;

enum ReflectionPropertyHookType: string
{
    case Get = 'get';
    case Set = 'set';

    /** @psalm-suppress UndefinedClass */
    public static function fromCoreReflectionPropertyHookType(CoreReflectionPropertyHookType $hookType): self
    {
        /** @phpstan-ignore match.unhandled */
        return match ($hookType) {
            CoreReflectionPropertyHookType::Get => self::Get,
            CoreReflectionPropertyHookType::Set => self::Set,
        };
    }
}
