<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Located;

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
}
