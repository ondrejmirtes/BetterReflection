<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type;

use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflector\ClassReflector;
use PHPStan\BetterReflection\SourceLocator\Type\FileIteratorSourceLocator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Roave\BetterReflectionTest\Assets\DirectoryScannerAssets;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use function array_map;
use function sort;

/**
 * @covers \PHPStan\BetterReflection\SourceLocator\Type\FileIteratorSourceLocator
 */
class FileIteratorSourceLocatorTest extends TestCase
{
    /** @var FileIteratorSourceLocator */
    private $sourceLocator;

    public function setUp() : void
    {
        parent::setUp();

        $this->sourceLocator = new FileIteratorSourceLocator(
            new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
                __DIR__ . '/../../Assets/DirectoryScannerAssets',
                RecursiveDirectoryIterator::SKIP_DOTS
            )),
            BetterReflectionSingleton::instance()->astLocator()
        );
    }

    public function testScanDirectoryClasses() : void
    {
        $classes = $this->sourceLocator->locateIdentifiersByType(
            new ClassReflector($this->sourceLocator),
            new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
        );

        self::assertCount(2, $classes);

        $classNames = array_map(
            static function (ReflectionClass $reflectionClass) : string {
                return $reflectionClass->getName();
            },
            $classes
        );

        sort($classNames);

        self::assertEquals(DirectoryScannerAssets\Bar\FooBar::class, $classNames[0]);
        self::assertEquals(DirectoryScannerAssets\Foo::class, $classNames[1]);
    }

    public function testLocateIdentifier() : void
    {
        $class = $this->sourceLocator->locateIdentifier(
            new ClassReflector($this->sourceLocator),
            new Identifier(
                DirectoryScannerAssets\Bar\FooBar::class,
                new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
            )
        );

        self::assertInstanceOf(ReflectionClass::class, $class);
        self::assertSame(DirectoryScannerAssets\Bar\FooBar::class, $class->getName());
    }
}
