<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Util;

use PhpParser\Node;
use PHPStan\BetterReflection\Util\Exception\InvalidNodePosition;
use PHPStan\BetterReflection\Util\Exception\NoNodePosition;

use function strlen;
use function strrpos;

/** @internal */
final class CalculateReflectionColumn
{
    /**
     * @throws InvalidNodePosition
     * @throws NoNodePosition
     *
     * @psalm-pure
     */
    public static function getStartColumn(string $source, Node $node): int
    {
        if (! $node->hasAttribute('startFilePos')) {
            return -1;
        }

        return self::calculateColumn($source, $node->getStartFilePos());
    }

    /**
     * @throws InvalidNodePosition
     * @throws NoNodePosition
     *
     * @psalm-pure
     */
    public static function getEndColumn(string $source, Node $node): int
    {
        if (! $node->hasAttribute('endFilePos')) {
            return -1;
        }

        return self::calculateColumn($source, $node->getEndFilePos());
    }

    /**
     * @throws InvalidNodePosition
     *
     * @psalm-pure
     */
    private static function calculateColumn(string $source, int $position): int
    {
        $sourceLength = strlen($source);

        if ($position >= $sourceLength) {
            return -1;
        }

        $lineStartPosition = strrpos($source, "\n", $position - $sourceLength);

        /** @psalm-var positive-int */
        return $lineStartPosition === false ? $position + 1 : $position - $lineStartPosition;
    }
}
