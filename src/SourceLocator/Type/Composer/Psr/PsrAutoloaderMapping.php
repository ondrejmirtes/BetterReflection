<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\SourceLocator\Type\Composer\Psr;

use PHPStan\BetterReflection\Identifier\Identifier;

interface PsrAutoloaderMapping
{
    /** @return list<string> */
    public function resolvePossibleFilePaths(Identifier $identifier): array;

    /** @return list<string> */
    public function directories(): array;
}
