<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflector;

use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\BetterReflection\Reflection\ReflectionFunction;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;

use function assert;

final class DefaultReflector implements Reflector
{
    /**
     * @var \PHPStan\BetterReflection\SourceLocator\Type\SourceLocator
     */
    private $sourceLocator;
    public function __construct(SourceLocator $sourceLocator)
    {
        $this->sourceLocator = $sourceLocator;
    }

    /**
     * Create a ReflectionClass for the specified $className.
     *
     * @throws IdentifierNotFound
     */
    public function reflectClass(string $identifierName): ReflectionClass
    {
        $identifier = new Identifier($identifierName, new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        $classInfo = $this->sourceLocator->locateIdentifier($this, $identifier);

        if ($classInfo === null) {
            throw Exception\IdentifierNotFound::fromIdentifier($identifier);
        }

        assert($classInfo instanceof ReflectionClass);

        return $classInfo;
    }

    /**
     * Get all the classes available in the scope specified by the SourceLocator.
     *
     * @return list<ReflectionClass>
     */
    public function reflectAllClasses(): iterable
    {
        /** @var list<ReflectionClass> $allClasses */
        $allClasses = $this->sourceLocator->locateIdentifiersByType($this, new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        return $allClasses;
    }

    /**
     * Create a ReflectionFunction for the specified $functionName.
     *
     * @throws IdentifierNotFound
     */
    public function reflectFunction(string $identifierName): ReflectionFunction
    {
        $identifier = new Identifier($identifierName, new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION));

        $functionInfo = $this->sourceLocator->locateIdentifier($this, $identifier);

        if ($functionInfo === null) {
            throw Exception\IdentifierNotFound::fromIdentifier($identifier);
        }

        assert($functionInfo instanceof ReflectionFunction);

        return $functionInfo;
    }

    /**
     * Get all the functions available in the scope specified by the SourceLocator.
     *
     * @return list<ReflectionFunction>
     */
    public function reflectAllFunctions(): iterable
    {
        /** @var list<ReflectionFunction> $allFunctions */
        $allFunctions = $this->sourceLocator->locateIdentifiersByType($this, new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION));

        return $allFunctions;
    }

    /**
     * Create a ReflectionConstant for the specified $constantName.
     *
     * @throws IdentifierNotFound
     */
    public function reflectConstant(string $identifierName): ReflectionConstant
    {
        $identifier = new Identifier($identifierName, new IdentifierType(IdentifierType::IDENTIFIER_CONSTANT));

        $constantInfo = $this->sourceLocator->locateIdentifier($this, $identifier);

        if ($constantInfo === null) {
            throw Exception\IdentifierNotFound::fromIdentifier($identifier);
        }

        assert($constantInfo instanceof ReflectionConstant);

        return $constantInfo;
    }

    /**
     * Get all the constants available in the scope specified by the SourceLocator.
     *
     * @return list<ReflectionConstant>
     */
    public function reflectAllConstants(): iterable
    {
        /** @var list<ReflectionConstant> $allConstants */
        $allConstants = $this->sourceLocator->locateIdentifiersByType($this, new IdentifierType(IdentifierType::IDENTIFIER_CONSTANT));

        return $allConstants;
    }
}
