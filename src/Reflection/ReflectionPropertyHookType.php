<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use LogicException;
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
    // the names match the cases of the native PropertyHookType enum
    // phpcs:disable Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase
    public const Get = 'get';
    public const Set = 'set';
    // phpcs:enable
    /**
     * @param \PropertyHookType::* $hookType
     */
    public static function fromCoreReflectionPropertyHookType($hookType): string
    {
        if ($hookType === CoreReflectionPropertyHookType::Get) {
            return self::Get;
        }

        if ($hookType === CoreReflectionPropertyHookType::Set) {
            return self::Set;
        }

        throw new LogicException('Unknown hook type');
    }
}
