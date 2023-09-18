<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\Type;

use InvalidArgumentException;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Exception\InvalidFileLocation;
use Roave\BetterReflection\SourceLocator\Located\InternalLocatedSource;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\SourceStubber\SourceStubber;
use Roave\BetterReflection\SourceLocator\SourceStubber\StubData;

final class PhpInternalSourceLocator extends AbstractSourceLocator
{
    /**
     * @var \Roave\BetterReflection\SourceLocator\SourceStubber\SourceStubber
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
    protected function createLocatedSource(Identifier $identifier): ?\Roave\BetterReflection\SourceLocator\Located\LocatedSource
    {
        return $this->getClassSource($identifier)
            ?? $this->getFunctionSource($identifier)
            ?? $this->getConstantSource($identifier);
    }

    private function getClassSource(Identifier $identifier): ?\Roave\BetterReflection\SourceLocator\Located\InternalLocatedSource
    {
        if (! $identifier->isClass()) {
            return null;
        }

        /** @psalm-var class-string|trait-string $className */
        $className = $identifier->getName();

        return $this->createLocatedSourceFromStubData($identifier, $this->stubber->generateClassStub($className));
    }

    private function getFunctionSource(Identifier $identifier): ?\Roave\BetterReflection\SourceLocator\Located\InternalLocatedSource
    {
        if (! $identifier->isFunction()) {
            return null;
        }

        return $this->createLocatedSourceFromStubData($identifier, $this->stubber->generateFunctionStub($identifier->getName()));
    }

    private function getConstantSource(Identifier $identifier): ?\Roave\BetterReflection\SourceLocator\Located\InternalLocatedSource
    {
        if (! $identifier->isConstant()) {
            return null;
        }

        return $this->createLocatedSourceFromStubData($identifier, $this->stubber->generateConstantStub($identifier->getName()));
    }

    private function createLocatedSourceFromStubData(Identifier $identifier, ?\Roave\BetterReflection\SourceLocator\SourceStubber\StubData $stubData): ?\Roave\BetterReflection\SourceLocator\Located\InternalLocatedSource
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
