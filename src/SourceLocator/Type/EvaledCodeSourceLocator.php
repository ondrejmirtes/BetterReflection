<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\SourceLocator\Type;

use InvalidArgumentException;
use ReflectionClass;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\Exception\InvalidFileLocation;
use PHPStan\BetterReflection\SourceLocator\Located\EvaledLocatedSource;
use PHPStan\BetterReflection\SourceLocator\Located\LocatedSource;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\SourceStubber;
use PHPStan\BetterReflection\Util\ClassExistenceChecker;

use function is_file;

final class EvaledCodeSourceLocator extends AbstractSourceLocator
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
        $classReflection = $this->getInternalReflectionClass($identifier);

        if ($classReflection === null) {
            return null;
        }

        $stubData = $this->stubber->generateClassStub($classReflection->getName());

        if ($stubData === null) {
            return null;
        }

        return new EvaledLocatedSource($stubData->getStub(), $classReflection->getName());
    }

    private function getInternalReflectionClass(Identifier $identifier): ?\ReflectionClass
    {
        if (! $identifier->isClass()) {
            return null;
        }

        /** @psalm-var class-string|trait-string $name */
        $name = $identifier->getName();

        if (! ClassExistenceChecker::exists($name, false)) {
            return null; // not an available internal class
        }

        $reflection = new ReflectionClass($name);
        $sourceFile = $reflection->getFileName();

        return $sourceFile && is_file($sourceFile)
            ? null : $reflection;
    }
}
