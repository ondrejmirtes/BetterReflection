<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use PhpParser\Node\Expr;
use Roave\BetterReflection\BetterReflection;

final class ExprCacheHelper
{

    /**
     * @return array<string, mixed
     */
    public static function export(Expr $expr): array
    {
        $br = new BetterReflection();

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

        $br = new BetterReflection();
        $expr = $br->originalPhpParser()->parse('<?php ' . $code . ';')[0]->expr;
        foreach ($attributes as $key => $value) {
            $expr->setAttribute($key, $value);
        }

        return $expr;
    }

}
