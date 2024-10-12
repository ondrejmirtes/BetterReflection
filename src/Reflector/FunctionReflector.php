<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflector;

use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\BetterReflection\Reflection\ReflectionFunction;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;

/**
 * @deprecated Use Roave\BetterReflection\Reflector\Reflector instead.
 */
class FunctionReflector implements Reflector
{
    /** @var Reflector */
    private $reflector;

    public function __construct(SourceLocator $sourceLocator)
    {
        $this->reflector = new DefaultReflector($sourceLocator);
    }

    /**
     * Create a ReflectionFunction for the specified $functionName.
     *
     * @throws IdentifierNotFound
     */
    public function reflect(string $functionName): ReflectionFunction
    {
        return $this->reflector->reflectFunction($functionName);
    }

    /**
     * Get all the classes available in the scope specified by the SourceLocator.
     *
     * @return ReflectionFunction[]
     */
    public function getAllFunctions(): array
    {
        return $this->reflector->reflectAllFunctions();
    }

    public function reflectClass(string $identifierName): ReflectionClass
    {
        return $this->reflector->reflectClass($identifierName);
    }

    /**
     * @return list<ReflectionClass>
     */
    public function reflectAllClasses(): iterable
    {
        return $this->reflector->reflectAllClasses();
    }

    public function reflectFunction(string $identifierName): ReflectionFunction
    {
        return $this->reflector->reflectFunction($identifierName);
    }

    /**
     * @return list<ReflectionFunction>
     */
    public function reflectAllFunctions(): iterable
    {
        return $this->reflector->reflectAllFunctions();
    }

    public function reflectConstant(string $identifierName): ReflectionConstant
    {
        return $this->reflector->reflectConstant($identifierName);
    }

    /**
     * @return list<ReflectionConstant>
     */
    public function reflectAllConstants(): iterable
    {
        return $this->reflector->reflectAllConstants();
    }
}
