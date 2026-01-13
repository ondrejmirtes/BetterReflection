<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection\Exception;

use InvalidArgumentException;

use function gettype;
use function sprintf;

class NotAnObject extends InvalidArgumentException
{
    /**
     * @param mixed $nonObject
     */
    public static function fromNonObject($nonObject): self
    {
        return new self(sprintf('Provided "%s" is not an object', gettype($nonObject)));
    }
}
