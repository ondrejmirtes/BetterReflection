<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\SourceLocator\SourceStubber;

use function array_merge;
use function array_reduce;
use function array_values;

class AggregateSourceStubber implements SourceStubber
{
    /** @var list<SourceStubber> */
    private $sourceStubbers;

    public function __construct(SourceStubber $sourceStubber, SourceStubber ...$otherSourceStubbers)
    {
        $this->sourceStubbers = array_values(array_merge([$sourceStubber], $otherSourceStubbers));
    }

    /** @param class-string|trait-string $className */
    public function generateClassStub(string $className): ?\PHPStan\BetterReflection\SourceLocator\SourceStubber\StubData
    {
        foreach ($this->sourceStubbers as $sourceStubber) {
            $stubData = $sourceStubber->generateClassStub($className);

            if ($stubData !== null) {
                return $stubData;
            }
        }

        return null;
    }

    public function generateFunctionStub(string $functionName): ?\PHPStan\BetterReflection\SourceLocator\SourceStubber\StubData
    {
        foreach ($this->sourceStubbers as $sourceStubber) {
            $stubData = $sourceStubber->generateFunctionStub($functionName);

            if ($stubData !== null) {
                return $stubData;
            }
        }

        return null;
    }

    public function generateConstantStub(string $constantName): ?\PHPStan\BetterReflection\SourceLocator\SourceStubber\StubData
    {
        return array_reduce($this->sourceStubbers, static function (?\PHPStan\BetterReflection\SourceLocator\SourceStubber\StubData $stubData, SourceStubber $sourceStubber) use ($constantName) : ?\PHPStan\BetterReflection\SourceLocator\SourceStubber\StubData {
            return $stubData ?? $sourceStubber->generateConstantStub($constantName);
        }, null);
    }
}
