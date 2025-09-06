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
    private string $extensionName;
    private ?string $aliasName = null;
    /** @param non-empty-string $extensionName */
    public function __construct(string $source, string $name, string $extensionName, ?string $fileName = null, ?string $aliasName = null)
    {
        $this->extensionName = $extensionName;
        $this->aliasName = $aliasName;
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

    public function getAliasName(): ?string
    {
        return $this->aliasName;
    }
}
