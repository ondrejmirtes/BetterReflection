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
    /** @var SourceStubber */
    private $stubber;

    public function __construct(Locator $astLocator, SourceStubber $stubber)
    {
        parent::__construct($astLocator);

        $this->stubber = $stubber;
    }

    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException
     * @throws InvalidFileLocation
     */
    protected function createLocatedSource(Identifier $identifier) : ?LocatedSource
    {
        return $this->getClassSource($identifier)
            ?? $this->getFunctionSource($identifier)
            ?? $this->getConstantSource($identifier);
    }

    private function getClassSource(Identifier $identifier) : ?InternalLocatedSource
    {
        if (! $identifier->isClass()) {
            return null;
        }

        return $this->createLocatedSourceFromStubData($this->stubber->generateClassStub($identifier->getName()));
    }

    private function getFunctionSource(Identifier $identifier) : ?InternalLocatedSource
    {
        if (! $identifier->isFunction()) {
            return null;
        }

        return $this->createLocatedSourceFromStubData($this->stubber->generateFunctionStub($identifier->getName()));
    }

    private function getConstantSource(Identifier $identifier) : ?InternalLocatedSource
    {
        if (! $identifier->isConstant()) {
            return null;
        }

        return $this->createLocatedSourceFromStubData($this->stubber->generateConstantStub($identifier->getName()));
    }

    private function createLocatedSourceFromStubData(?StubData $stubData) : ?InternalLocatedSource
    {
        if ($stubData === null) {
            return null;
        }

        if ($stubData->getExtensionName() === null) {
            // Not internal
            return null;
        }

        return new InternalLocatedSource(
            $stubData->getStub(),
            $stubData->getExtensionName(),
            $stubData->getFileName()
        );
    }
}
