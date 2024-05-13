<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\SourceLocator\Type;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\Exception\InvalidDirectory;
use PHPStan\BetterReflection\SourceLocator\Exception\InvalidFileInfo;

use function array_map;
use function is_dir;

/**
 * This source locator recursively loads all php files in an entire directory or multiple directories.
 */
class DirectoriesSourceLocator implements SourceLocator
{
    /**
     * @var \PHPStan\BetterReflection\SourceLocator\Type\AggregateSourceLocator
     */
    private $aggregateSourceLocator;

    /**
     * @param list<string> $directories directories to scan
     *
     * @throws InvalidDirectory
     * @throws InvalidFileInfo
     */
    public function __construct(array $directories, Locator $astLocator)
    {
        $this->aggregateSourceLocator = new AggregateSourceLocator(array_map(static function (string $directory) use ($astLocator): FileIteratorSourceLocator {
            if (! is_dir($directory)) {
                throw InvalidDirectory::fromNonDirectory($directory);
            }

            return new FileIteratorSourceLocator(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)), $astLocator);
        }, $directories));
    }

    public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?\PHPStan\BetterReflection\Reflection\Reflection
    {
        return $this->aggregateSourceLocator->locateIdentifier($reflector, $identifier);
    }

    /**
     * {@inheritDoc}
     */
    public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
    {
        return $this->aggregateSourceLocator->locateIdentifiersByType($reflector, $identifierType);
    }
}
