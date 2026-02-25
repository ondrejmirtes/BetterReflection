<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use PhpParser\Node\Expr;
use Roave\BetterReflection\BetterReflection;

final class ExprCacheHelper
{
    private static ?BetterReflection $betterReflection = null;

    /**
     * @return array<string, mixed>
     */
    public static function export(Expr $expr): array
    {
        $br = self::$betterReflection ??= new BetterReflection();

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

        $br = self::$betterReflection ??= new BetterReflection();
        // cached parser implementations might re-use nodes. clone so we don't mix up attributes.
        $expr = clone $br->phpParser()->parse('<?php ' . $code . ';')[0]->expr;
        foreach ($attributes as $key => $value) {
            $expr->setAttribute($key, $value);
        }

        return $expr;
    }

}
