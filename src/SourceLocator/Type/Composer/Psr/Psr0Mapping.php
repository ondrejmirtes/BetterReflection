<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type\Composer\Psr;

use Roave\BetterReflection\Identifier\Identifier;

use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function rtrim;
use function str_replace;
use function strpos;

final class Psr0Mapping implements PsrAutoloaderMapping
{
    /** @var array<string, list<string>> */
    private array $mappings = [];

    private function __construct()
    {
    }

    /** @param array<string, list<string>> $mappings */
    public static function fromArrayMappings(array $mappings): self
    {
        $instance = new self();

        $instance->mappings = array_map(
            static function (array $directories): array {
                return array_map(static fn (string $directory): string => rtrim($directory, '/'), $directories);
            },
            $mappings,
        );

        return $instance;
    }

    /** {@inheritDoc} */
    public function resolvePossibleFilePaths(Identifier $identifier): array
    {
        if (! $identifier->isClass()) {
            return [];
        }

        $className = $identifier->getName();

        foreach ($this->mappings as $prefix => $paths) {
            if ($prefix === '') {
                continue;
            }

            if (strpos($className, $prefix) === 0) {
                return array_map(
                    static fn (string $path): string => $path . '/' . str_replace(['\\', '_'], '/', $className) . '.php',
                    $paths,
                );
            }
        }

        return [];
    }

    /** {@inheritDoc} */
    public function directories(): array
    {
        return array_values(array_unique(array_merge([], ...array_values($this->mappings))));
    }
}
