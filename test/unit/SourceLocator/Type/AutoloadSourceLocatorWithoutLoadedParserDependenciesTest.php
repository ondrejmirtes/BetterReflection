<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type;

use PhpParser\Lexer\Emulative;
use PhpParser\ParserFactory;
use PHPStan\BetterReflection\Reflector\ClassReflector;
use PHPStan\BetterReflection\Reflector\FunctionReflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\Ast\Parser\MemoizingParser;
use PHPStan\BetterReflection\SourceLocator\Type\AutoloadSourceLocator;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflectionTest\Fixture\ExampleClass;
use function class_exists;

/** @covers \PHPStan\BetterReflection\SourceLocator\Type\AutoloadSourceLocator */
class AutoloadSourceLocatorWithoutLoadedParserDependenciesTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testCanFindClassEvenWhenParserIsNotLoadedInMemory() : void
    {
        self::assertFalse(
            class_exists(MemoizingParser::class, false),
            MemoizingParser::class . ' was not loaded into memory'
        );

        $parser            = (new ParserFactory())->create(ParserFactory::PREFER_PHP7, new Emulative([
            'usedAttributes' => ['comments', 'startLine', 'endLine', 'startFilePos', 'endFilePos'],
        ]));
        $functionReflector = null;
        $sourceLocator     = new AutoloadSourceLocator(
            new Locator($parser, static function () use (&$functionReflector) : FunctionReflector {
                return $functionReflector;
            }),
            $parser
        );
        $classReflector    = new ClassReflector($sourceLocator);
        $functionReflector = new FunctionReflector($sourceLocator, $classReflector);
        $reflection        = $classReflector->reflect(ExampleClass::class);

        self::assertSame(ExampleClass::class, $reflection->getName());
        self::assertFalse(
            class_exists(MemoizingParser::class, false),
            MemoizingParser::class . ' was not implicitly loaded'
        );
    }
}
