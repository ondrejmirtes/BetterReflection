<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\SourceLocator\SourceStubber;

/** @internal */
interface SourceStubber
{
    /**
     * Generates stub for given class. Returns null when it cannot generate the stub.
     *
     * @param class-string|trait-string $className
     */
    public function generateClassStub(string $className): ?\PHPStan\BetterReflection\SourceLocator\SourceStubber\StubData;

    /**
     * Generates stub for given function. Returns null when it cannot generate the stub.
     */
    public function generateFunctionStub(string $functionName): ?\PHPStan\BetterReflection\SourceLocator\SourceStubber\StubData;

    /**
     * Generates stub for given constant. Returns null when it cannot generate the stub.
     */
    public function generateConstantStub(string $constantName): ?\PHPStan\BetterReflection\SourceLocator\SourceStubber\StubData;
}
