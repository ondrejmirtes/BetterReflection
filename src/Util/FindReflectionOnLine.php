<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Util;

use InvalidArgumentException;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionConstant;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Ast\Exception\ParseToAstFailure;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Exception\InvalidFileLocation;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

use function array_merge;

final class FindReflectionOnLine
{
    /**
     * @var \Roave\BetterReflection\SourceLocator\Type\SourceLocator
     */
    private $sourceLocator;
    /**
     * @var \Roave\BetterReflection\SourceLocator\Ast\Locator
     */
    private $astLocator;
    public function __construct(SourceLocator $sourceLocator, Locator $astLocator)
    {
        $this->sourceLocator = $sourceLocator;
        $this->astLocator = $astLocator;
    }

    /**
     * Find a reflection on the specified line number.
     *
     * Returns null if no reflections found on the line.
     *
     * @param non-empty-string $filename
     *
     * @throws InvalidFileLocation
     * @throws ParseToAstFailure
     * @throws InvalidArgumentException
     * @return \Roave\BetterReflection\Reflection\ReflectionMethod|\Roave\BetterReflection\Reflection\ReflectionClass|\Roave\BetterReflection\Reflection\ReflectionFunction|\Roave\BetterReflection\Reflection\ReflectionConstant|\Roave\BetterReflection\Reflection\Reflection|null
     */
    public function __invoke(string $filename, int $lineNumber)
    {
        $reflections = $this->computeReflections($filename);

        foreach ($reflections as $reflection) {
            if ($reflection instanceof ReflectionClass && $this->containsLine($reflection, $lineNumber)) {
                foreach ($reflection->getMethods() as $method) {
                    if ($this->containsLine($method, $lineNumber)) {
                        return $method;
                    }
                }

                return $reflection;
            }

            if ($reflection instanceof ReflectionFunction && $this->containsLine($reflection, $lineNumber)) {
                return $reflection;
            }

            if ($reflection instanceof ReflectionConstant && $this->containsLine($reflection, $lineNumber)) {
                return $reflection;
            }
        }

        return null;
    }

    /**
     * Find all class and function reflections in the specified file
     *
     * @param non-empty-string $filename
     *
     * @return list<Reflection>
     *
     * @throws ParseToAstFailure
     * @throws InvalidFileLocation
     */
    private function computeReflections(string $filename): array
    {
        $singleFileSourceLocator = new SingleFileSourceLocator($filename, $this->astLocator);
        $reflector               = new DefaultReflector(new AggregateSourceLocator([$singleFileSourceLocator, $this->sourceLocator]));

        return array_merge($singleFileSourceLocator->locateIdentifiersByType($reflector, new IdentifierType(IdentifierType::IDENTIFIER_CLASS)), $singleFileSourceLocator->locateIdentifiersByType($reflector, new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)), $singleFileSourceLocator->locateIdentifiersByType($reflector, new IdentifierType(IdentifierType::IDENTIFIER_CONSTANT)));
    }

    /**
     * Check to see if the line is within the boundaries of the reflection specified.
     * @param \Roave\BetterReflection\Reflection\ReflectionClass|\Roave\BetterReflection\Reflection\ReflectionMethod|\Roave\BetterReflection\Reflection\ReflectionFunction|\Roave\BetterReflection\Reflection\ReflectionConstant $reflection
     */
    private function containsLine($reflection, int $lineNumber): bool
    {
        return $lineNumber >= $reflection->getStartLine() && $lineNumber <= $reflection->getEndLine();
    }
}
