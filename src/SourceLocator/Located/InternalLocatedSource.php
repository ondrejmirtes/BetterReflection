<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Located;

use Roave\BetterReflection\SourceLocator\FileChecker;

/**
 * @internal
 *
 * @psalm-immutable
 */
class InternalLocatedSource extends LocatedSource
{
    /** @param non-empty-string $extensionName */
    public function __construct(string $source, string $name, private string $extensionName, ?string $fileName = null, private ?string $aliasName = null)
    {
        parent::__construct($source, $name, $fileName);
    }

    public function isInternal(): bool
    {
        return true;
    }

    /** @return non-empty-string|null */
    public function getExtensionName(): string|null
    {
        return $this->extensionName;
    }

    public function getAliasName(): string|null
    {
        return $this->aliasName;
    }

    /**
     * @return array<string, mixed>
     */
    public function exportToCache(): array
    {
        $data = parent::exportToCache();
        $data['data']['extensionName'] = $this->extensionName;
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

        return new self($fileContents, $data['data']['name'], $data['data']['extensionName'], $data['data']['filename'], $data['data']['aliasName']);
    }
}
