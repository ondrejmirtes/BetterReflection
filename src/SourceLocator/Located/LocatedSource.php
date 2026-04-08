<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Located;

use InvalidArgumentException;
use Roave\BetterReflection\SourceLocator\Exception\InvalidFileLocation;
use Roave\BetterReflection\SourceLocator\FileChecker;
use Roave\BetterReflection\Util\FileHelper;

use function assert;
use function get_class;

/**
 * Value object containing source code that has been located.
 *
 * @internal
 *
 * @psalm-immutable
 */
class LocatedSource
{
    private string $source;
    /**
     * @var string|null
     */
    private $name;
    /** @var non-empty-string|null */
    private $filename;

    /**
     * @throws InvalidArgumentException
     * @throws InvalidFileLocation
     */
    public function __construct(string $source, ?string $name, ?string $filename = null)
    {
        $this->source = $source;
        $this->name = $name;
        if ($filename !== null) {
            assert($filename !== '');

            $filename = FileHelper::normalizeWindowsPath($filename);
        }

        $this->filename = $filename;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /** @return non-empty-string|null */
    public function getFileName(): ?string
    {
        return $this->filename;
    }

    /**
     * Is the located source in PHP internals?
     */
    public function isInternal(): bool
    {
        return false;
    }

    /** @return non-empty-string|null */
    public function getExtensionName(): ?string
    {
        return null;
    }

    /**
     * Is the located source produced by eval() or \function_create()?
     */
    public function isEvaled(): bool
    {
        return false;
    }

    public function getAliasName(): ?string
    {
        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function exportToCache(): array
    {
        assert($this->filename !== null);

        return [
            'class' => get_class($this),
            'data' => [
                'name' => $this->name,
                'filename' => $this->filename,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function importFromCache(array $data): self
    {
        $class = $data['class'];
        if ($class === self::class) {
            FileChecker::assertReadableFile($data['data']['filename']);
            $fileContents = file_get_contents($data['data']['filename']);
            assert($fileContents !== false);
            return new self($fileContents, $data['data']['name'], $data['data']['filename']);
        }

        return $class::importFromCache($data);
    }
}
