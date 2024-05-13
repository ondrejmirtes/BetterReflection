<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\SourceLocator\Type;

use InvalidArgumentException;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\Exception\InvalidFileLocation;
use PHPStan\BetterReflection\SourceLocator\Located\InternalLocatedSource;
use PHPStan\BetterReflection\SourceLocator\Located\LocatedSource;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\SourceStubber;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\StubData;

final class PhpInternalSourceLocator extends AbstractSourceLocator
{
    /**
     * @var \PHPStan\BetterReflection\SourceLocator\SourceStubber\SourceStubber
     */
    private $stubber;
    public function __construct(Locator $astLocator, SourceStubber $stubber)
    {
        $this->stubber = $stubber;
        parent::__construct($astLocator);
    }

    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException
     * @throws InvalidFileLocation
     */
    protected function createLocatedSource(Identifier $identifier): ?\PHPStan\BetterReflection\SourceLocator\Located\LocatedSource
    {
        return $this->getClassSource($identifier)
            ?? $this->getFunctionSource($identifier)
            ?? $this->getConstantSource($identifier);
    }

    private function getClassSource(Identifier $identifier): ?\PHPStan\BetterReflection\SourceLocator\Located\InternalLocatedSource
    {
        if (! $identifier->isClass()) {
            return null;
        }

        /** @psalm-var class-string|trait-string $className */
        $className = $identifier->getName();

        return $this->createLocatedSourceFromStubData($identifier, $this->stubber->generateClassStub($className));
    }

    private function getFunctionSource(Identifier $identifier): ?\PHPStan\BetterReflection\SourceLocator\Located\InternalLocatedSource
    {
        if (! $identifier->isFunction()) {
            return null;
        }

        return $this->createLocatedSourceFromStubData($identifier, $this->stubber->generateFunctionStub($identifier->getName()));
    }

    private function getConstantSource(Identifier $identifier): ?\PHPStan\BetterReflection\SourceLocator\Located\InternalLocatedSource
    {
        if (! $identifier->isConstant()) {
            return null;
        }

        return $this->createLocatedSourceFromStubData($identifier, $this->stubber->generateConstantStub($identifier->getName()));
    }

    private function createLocatedSourceFromStubData(Identifier $identifier, ?\PHPStan\BetterReflection\SourceLocator\SourceStubber\StubData $stubData): ?\PHPStan\BetterReflection\SourceLocator\Located\InternalLocatedSource
    {
        if ($stubData === null) {
            return null;
        }

        $extensionName = $stubData->getExtensionName();

        if ($extensionName === null) {
            // Not internal
            return null;
        }

        return new InternalLocatedSource($stubData->getStub(), $identifier->getName(), $extensionName, $stubData->getFileName());
    }
}
