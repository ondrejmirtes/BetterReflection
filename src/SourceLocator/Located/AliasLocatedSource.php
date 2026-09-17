<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Located;

use Roave\BetterReflection\SourceLocator\FileChecker;

/**
 * @internal
 *
 * @psalm-immutable
 */
class AliasLocatedSource extends LocatedSource
{
    private string $aliasName;
    public function __construct(string $source, string $name, ?string $filename, string $aliasName)
    {
        $this->aliasName = $aliasName;
        parent::__construct($source, $name, $filename);
    }

    public function getAliasName(): ?string
    {
        return $this->aliasName;
    }

    /**
     * @return array<string, mixed>
     */
    public function exportToCache(): array
    {
        $data = parent::exportToCache();
        $data['data']['aliasName'] = $this->aliasName;

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function importFromCache(array $data): self
    {
        FileChecker::assertReadableFile($data['data']['filename']);
        $fileContents = file_get_contents($data['data']['filename']);
        assert($fileContents !== false);

        return new self($fileContents, $data['data']['name'], $data['data']['filename'], $data['data']['aliasName']);
    }
}
