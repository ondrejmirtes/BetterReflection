<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\StringCast;

use Roave\BetterReflection\Reflection\ReflectionClassConstant;

use function gettype;
use function is_array;
use function sprintf;

/** @internal */
final class ReflectionClassConstantStringCast
{
    /**
     * @return non-empty-string
     *
     * @psalm-pure
     */
    public static function toString(ReflectionClassConstant $constantReflection, bool $indentDocComment = true): string
    {
        /** @psalm-var scalar|array<scalar> $value */
        $value = $constantReflection->getValue();

        return sprintf(
            "%sConstant [ %s%s %s %s ] { %s }\n",
            ReflectionStringCastHelper::docCommentToString($constantReflection, $indentDocComment),
            $constantReflection->isFinal() ? 'final ' : '',
            self::visibilityToString($constantReflection),
            gettype($value),
            $constantReflection->getName(),
            is_array($value) ? 'Array' : (string) $value,
        );
    }

    /** @psalm-pure */
    private static function visibilityToString(ReflectionClassConstant $constantReflection): string
    {
        if ($constantReflection->isProtected()) {
            return 'protected';
        }

        if ($constantReflection->isPrivate()) {
            return 'private';
        }

        return 'public';
    }
}
