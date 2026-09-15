<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection\StringCast;

use PHPStan\BetterReflection\Reflection\ReflectionClassConstant;
use PHPStan\BetterReflection\Reflection\ReflectionEnumCase;
use PHPStan\BetterReflection\Reflection\ReflectionProperty;

use function assert;
use function preg_replace;

/** @internal */
final class ReflectionStringCastHelper
{
    /** @psalm-pure
     * @param \PHPStan\BetterReflection\Reflection\ReflectionProperty|\PHPStan\BetterReflection\Reflection\ReflectionClassConstant|\PHPStan\BetterReflection\Reflection\ReflectionEnumCase $reflection */
    public static function docCommentToString($reflection, bool $indent): string
    {
        $docComment = $reflection->getDocComment();

        if ($docComment === null) {
            return '';
        }

        $indentedDocComment = $indent ? preg_replace('/(\n)(?!\n)/', '\1    ', $docComment) : $docComment;
        assert($indentedDocComment !== null);

        return $indentedDocComment . "\n";
    }
}
