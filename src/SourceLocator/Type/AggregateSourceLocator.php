<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\SourceLocator\Type;

use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflector\Reflector;

use function array_map;
use function array_merge;

class AggregateSourceLocator implements SourceLocator
{
    /**
     * @var list<SourceLocator>
     */
    private $sourceLocators = [];
    /** @param list<SourceLocator> $sourceLocators */
    public function __construct(array $sourceLocators = [])
    {
        $this->sourceLocators = $sourceLocators;
    }

    public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?\PHPStan\BetterReflection\Reflection\Reflection
    {
        foreach ($this->sourceLocators as $sourceLocator) {
            $located = $sourceLocator->locateIdentifier($reflector, $identifier);

            if ($located) {
                return $located;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
    {
        return array_merge([], ...array_map(static function (SourceLocator $sourceLocator) use ($reflector, $identifierType) : array {
            return $sourceLocator->locateIdentifiersByType($reflector, $identifierType);
        }, $this->sourceLocators));
    }
}
