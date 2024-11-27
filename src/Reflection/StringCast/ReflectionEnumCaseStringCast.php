<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\StringCast;

use Roave\BetterReflection\Reflection\ReflectionEnumCase;

use function gettype;
use function preg_replace;
use function sprintf;

/** @internal */
final class ReflectionEnumCaseStringCast
{
    /**
     * @return non-empty-string
     *
     * @psalm-pure
     */
    public static function toString(ReflectionEnumCase $enumCaseReflection, bool $indentDocComment = true): string
    {
        $enumReflection = $enumCaseReflection->getDeclaringEnum();

        $value = $enumReflection->isBacked() ? $enumCaseReflection->getValue() : 'Object';
        $type  = $enumReflection->isBacked() ? gettype($value) : $enumReflection->getName();

        return sprintf(
            "%sConstant [ public %s %s ] { %s }\n",
            self::docCommentToString($enumCaseReflection, $indentDocComment),
            $type,
            $enumCaseReflection->getName(),
            $value,
        );
    }

    /** @psalm-pure */
    private static function docCommentToString(ReflectionEnumCase $enumCaseReflection, bool $indent): string
    {
        $docComment = $enumCaseReflection->getDocComment();

        if ($docComment === null) {
            return '';
        }

        return ($indent ? preg_replace('/(\n)(?!\n)/', '\1    ', $docComment) : $docComment) . "\n";
    }
}
