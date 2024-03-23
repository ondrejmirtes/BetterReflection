<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection\Exception;

use RuntimeException;

class CodeLocationMissing extends RuntimeException
{
    public static function create(): self
    {
        return new self('Code location is missing');
    }
}
