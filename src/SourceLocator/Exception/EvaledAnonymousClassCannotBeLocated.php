<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\SourceLocator\Exception;

use LogicException;

class EvaledAnonymousClassCannotBeLocated extends LogicException
{
    public static function create(): self
    {
        return new self('Evaled anonymous class cannot be located');
    }
}
