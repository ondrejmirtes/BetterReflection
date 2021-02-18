<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Util;

use PhpParser\Comment\Doc;
use PhpParser\NodeAbstract;
use function assert;
use function is_string;

/**
 * @internal
 */
final class GetLastDocComment
{
    public static function forNode(NodeAbstract $node) : string
    {
        $doc = null;
        foreach ($node->getComments() as $comment) {
            if (! $comment instanceof Doc) {
                continue;
            }

            $doc = $comment;
        }

        if ($doc !== null) {
            $text = $doc->getText();

            assert(is_string($text));

            return $text;
        }

        return '';
    }
}
