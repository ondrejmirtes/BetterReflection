<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\Exception\InvalidFileLocation;
use PHPStan\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

#[CoversClass(SingleFileSourceLocator::class)]
class SingleFileSourceLocatorTest extends TestCase
{
    /**
     * @var \PHPStan\BetterReflection\SourceLocator\Ast\Locator
     */
    private $astLocator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->astLocator = BetterReflectionSingleton::instance()->astLocator();
    }

    /**
     * @return \PHPStan\BetterReflection\Reflector\Reflector|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getMockReflector()
    {
        return $this->createMock(Reflector::class);
    }

    public function testReturnsNullWhenSourceDoesNotContainClass(): void
    {
        $fileName = __DIR__ . '/../../Fixture/NoNamespace.php';

        $locator = new SingleFileSourceLocator($fileName, $this->astLocator);

        self::assertNull($locator->locateIdentifier($this->getMockReflector(), new Identifier('does not matter what the class name is', new IdentifierType(IdentifierType::IDENTIFIER_CLASS))));
    }

    public function testReturnsReflectionWhenSourceHasClass(): void
    {
        $fileName = __DIR__ . '/../../Fixture/NoNamespace.php';

        $locator = new SingleFileSourceLocator($fileName, $this->astLocator);

        $reflectionClass = $locator->locateIdentifier($this->getMockReflector(), new Identifier('ClassWithNoNamespace', new IdentifierType(IdentifierType::IDENTIFIER_CLASS)));

        self::assertSame('ClassWithNoNamespace', $reflectionClass->getName());
    }

    public function testThrowsExceptionIfFileIsNotReadable(): void
    {
        $this->expectException(InvalidFileLocation::class);
        new SingleFileSourceLocator('not-readable', $this->astLocator);
    }
}
