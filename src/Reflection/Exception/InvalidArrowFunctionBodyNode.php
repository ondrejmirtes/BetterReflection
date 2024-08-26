<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Exception;

use PhpParser\Node\Stmt;
use Roave\BetterReflection\BetterReflection;
use RuntimeException;

use function sprintf;
use function substr;

class InvalidArrowFunctionBodyNode extends RuntimeException
{
    public static function create(Stmt $node): self
    {
        $printer = (new BetterReflection)->printer();
        return new self(sprintf('Invalid arrow function body node (first 50 characters: %s)', substr($printer->prettyPrint([$node]), 0, 50)));
    }
}
