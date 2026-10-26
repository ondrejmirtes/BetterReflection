<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflector;

use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\BetterReflection\Reflection\ReflectionFunction;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;

interface Reflector
{
    /**
     * Create a ReflectionClass for the specified $className.
     *
     * @throws IdentifierNotFound
     */
    public function reflectClass(string $identifierName): ReflectionClass;

    /**
     * Get all the classes available in the scope specified by the SourceLocator.
     *
     * @return list<ReflectionClass>
     */
    public function reflectAllClasses(): iterable;

    /**
     * Create a ReflectionFunction for the specified $functionName.
     *
     * @throws IdentifierNotFound
     */
    public function reflectFunction(string $identifierName): ReflectionFunction;

    /**
     * Get all the functions available in the scope specified by the SourceLocator.
     *
     * @return list<ReflectionFunction>
     */
    public function reflectAllFunctions(): iterable;

    /**
     * Create a ReflectionConstant for the specified $constantName.
     *
     * @throws IdentifierNotFound
     */
    public function reflectConstant(string $identifierName): ReflectionConstant;

    /**
     * Get all the constants available in the scope specified by the SourceLocator.
     *
     * @return list<ReflectionConstant>
     */
    public function reflectAllConstants(): iterable;
}
