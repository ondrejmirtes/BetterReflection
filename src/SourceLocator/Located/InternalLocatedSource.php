<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\SourceLocator\Located;

/**
 * @internal
 *
 * @psalm-immutable
 */
class InternalLocatedSource extends LocatedSource
{
    /**
     * @var non-empty-string
     */
    private $extensionName;
    /** @param non-empty-string $extensionName */
    public function __construct(string $source, string $name, string $extensionName, ?string $fileName = null)
    {
        $this->extensionName = $extensionName;
        parent::__construct($source, $name, $fileName);
    }

    public function isInternal(): bool
    {
        return true;
    }

    /** @return non-empty-string|null */
    public function getExtensionName(): ?string
    {
        return $this->extensionName;
    }
}
