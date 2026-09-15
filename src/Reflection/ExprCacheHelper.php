<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use PhpParser\Node\Expr;
use Roave\BetterReflection\BetterReflection;

final class ExprCacheHelper
{

    /**
     * One instance for every export and import: a BetterReflection memoizes
     * its php-parser and printer, and building a Php8 parser (token map plus
     * every reduce callback) per imported expression dominated cache hydration.
     */
    private static BetterReflection|null $betterReflection = null;

    private static function betterReflection(): BetterReflection
    {
        return self::$betterReflection ??= new BetterReflection();
    }

    /**
     * @return array<string, mixed
     */
    public static function export(Expr $expr): array
    {
        $br = self::betterReflection();

        $attributes = [];
        foreach (['startLine', 'endLine', 'startTokenPos', 'startFilePos', 'endTokenPos', 'endFilePos'] as $key) {
            $attributes[$key] = $expr->getAttribute($key);
        }

        return [
            'code' => $br->printer()->prettyPrintExpr($expr),
            'attributes' => $attributes,
        ];
    }

    public static function import(array $data): Expr
    {
        $code = $data['code'];
        $attributes = $data['attributes'];

        $expr = self::betterReflection()->originalPhpParser()->parse('<?php ' . $code . ';')[0]->expr;
        foreach ($attributes as $key => $value) {
            $expr->setAttribute($key, $value);
        }

        return $expr;
    }

}
