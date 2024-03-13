<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection\Exception;

use PhpParser\Node;
use PHPStan\BetterReflection\BetterReflection;
use RuntimeException;

use function sprintf;
use function substr;

class InvalidConstantNode extends RuntimeException
{
    public static function create(Node $node): self
    {
        $printer = (new BetterReflection())->printer();
        return new self(sprintf('Invalid constant node (first 50 characters: %s)', substr($printer->prettyPrint([$node]), 0, 50)));
    }
}
