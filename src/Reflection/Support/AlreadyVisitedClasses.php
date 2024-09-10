<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection\Support;

use PHPStan\BetterReflection\Reflection\Exception\CircularReference;

use function array_key_exists;

/** @internal */
final class AlreadyVisitedClasses
{
    /**
     * @var array<class-string, null>
     */
    private $classNames;
    /** @param array<class-string, null> $classNames */
    private function __construct(array $classNames)
    {
        $this->classNames = $classNames;
    }

    public static function createEmpty(): self
    {
        return new self([]);
    }

    /** @param class-string $className */
    public function push(string $className): void
    {
        if (array_key_exists($className, $this->classNames)) {
            throw CircularReference::fromClassName($className);
        }

        $this->classNames[$className] = null;
    }
}
