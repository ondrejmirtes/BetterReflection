<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\SourceLocator\Located;

use PHPStan\BetterReflection\SourceLocator\FileChecker;

/**
 * @internal
 *
 * @psalm-immutable
 */
class EvaledLocatedSource extends LocatedSource
{
    public function isEvaled(): bool
    {
        return true;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function importFromCache(array $data): self
    {
        FileChecker::assertReadableFile($data['data']['filename']);
        $fileContents = file_get_contents($data['data']['filename']);
        assert($fileContents !== false);

        return new self($fileContents, $data['data']['name'], $data['data']['filename']);
    }
}
