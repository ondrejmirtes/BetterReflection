<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflector;

use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflector\ClassReflector;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\StringSourceLocator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use function assert;

/**
 * @covers \PHPStan\BetterReflection\Reflector\ClassReflector
 */
class ClassReflectorTest extends TestCase
{
    public function testGetClassesFromFile() : void
    {
        $classes = (new ClassReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/ExampleClass.php', BetterReflectionSingleton::instance()->astLocator())
        ))->getAllClasses();

        self::assertContainsOnlyInstancesOf(ReflectionClass::class, $classes);
        self::assertCount(10, $classes);
    }

    public function testReflectProxiesToSourceLocator() : void
    {
        $reflection = $this->createMock(ReflectionClass::class);

        $sourceLocator = $this
            ->getMockBuilder(StringSourceLocator::class)
            ->disableOriginalConstructor()
            ->setMethods(['locateIdentifier'])
            ->getMock();
        assert($sourceLocator instanceof StringSourceLocator && $sourceLocator instanceof MockObject);

        $sourceLocator
            ->expects($this->once())
            ->method('locateIdentifier')
            ->will($this->returnValue($reflection));

        $reflector = new ClassReflector($sourceLocator);

        self::assertSame($reflection, $reflector->reflect('MyClass'));
    }

    public function testThrowsExceptionWhenIdentifierNotFound() : void
    {
        $defaultReflector = BetterReflectionSingleton::instance()->classReflector();

        $this->expectException(IdentifierNotFound::class);

        $defaultReflector->reflect('Something\That\Should\Not\Exist');
    }
}
