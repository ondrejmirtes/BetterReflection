<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflector;

use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\BetterReflection\Reflector\ClassReflector;
use PHPStan\BetterReflection\Reflector\ConstantReflector;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\StringSourceLocator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use function assert;

/**
 * @covers \PHPStan\BetterReflection\Reflector\ConstantReflector
 */
class ConstantReflectorTest extends TestCase
{
    /** @var ClassReflector */
    private $classReflector;

    protected function setUp() : void
    {
        parent::setUp();

        $this->classReflector = BetterReflectionSingleton::instance()->classReflector();
    }

    public function testReflectProxiesToGenericReflectMethod() : void
    {
        $reflection = $this->createMock(ReflectionConstant::class);

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

        $reflector = new ConstantReflector($sourceLocator, $this->classReflector);
        self::assertSame($reflection, $reflector->reflect('FOO'));
    }

    public function testGetConstantsFromFile() : void
    {
        $constants = (new ConstantReflector(
            new SingleFileSourceLocator(__DIR__ . '/../Fixture/Constants.php', BetterReflectionSingleton::instance()->astLocator()),
            $this->classReflector
        ))->getAllConstants();

        self::assertContainsOnlyInstancesOf(ReflectionConstant::class, $constants);
        self::assertCount(5, $constants);
    }

    public function testThrowsExceptionWhenIdentifierNotFound() : void
    {
        $defaultReflector = BetterReflectionSingleton::instance()->constantReflector();

        $this->expectException(IdentifierNotFound::class);

        $defaultReflector->reflect('Something\That\Should\NOT_EXIST');
    }
}
