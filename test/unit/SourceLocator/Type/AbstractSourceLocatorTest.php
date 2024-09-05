<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator as AstLocator;
use PHPStan\BetterReflection\SourceLocator\Located\LocatedSource;
use PHPStan\BetterReflection\SourceLocator\Type\AbstractSourceLocator;

#[CoversClass(AbstractSourceLocator::class)]
class AbstractSourceLocatorTest extends TestCase
{
    public function testLocateIdentifierCallsFindReflection(): void
    {
        $mockReflector = $this->createMock(Reflector::class);

        $locatedSource = new LocatedSource('<?php class Foo{}', 'Foo');

        $identifier = new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        $mockReflection = $this->createMock(ReflectionClass::class);

        $astLocator = $this->createMock(AstLocator::class);

        $astLocator->expects($this->once())
            ->method('findReflection')
            ->with($mockReflector, $locatedSource, $identifier)
            ->will($this->returnValue($mockReflection));

        $sourceLocator = $this->getMockBuilder(AbstractSourceLocator::class)
            ->setConstructorArgs([$astLocator])
            ->onlyMethods(['createLocatedSource'])
            ->getMock();

        $sourceLocator->expects($this->once())
            ->method('createLocatedSource')
            ->with($identifier)
            ->will($this->returnValue($locatedSource));

        self::assertSame($mockReflection, $sourceLocator->locateIdentifier($mockReflector, $identifier));
    }

    public function testLocateIdentifierReturnsNullWithoutTryingToFindReflectionWhenUnableToLocateSource(): void
    {
        $mockReflector = $this->createMock(Reflector::class);

        $identifier = new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        $astLocator = $this->createMock(AstLocator::class);

        $astLocator->expects($this->never())
            ->method('findReflection');

        $sourceLocator = $this->getMockBuilder(AbstractSourceLocator::class)
            ->setConstructorArgs([$astLocator])
            ->onlyMethods(['createLocatedSource'])
            ->getMock();

        $sourceLocator->expects($this->once())
            ->method('createLocatedSource')
            ->with($identifier)
            ->will($this->returnValue(null));

        self::assertNull($sourceLocator->locateIdentifier($mockReflector, $identifier));
    }

    public function testLocateIdentifierReturnsNullWhenFindLocatorThrowsException(): void
    {
        $mockReflector = $this->createMock(Reflector::class);

        $locatedSource = new LocatedSource('<?php class Foo{}', 'Foo');

        $identifier = new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        $astLocator = $this->createMock(AstLocator::class);

        $astLocator->expects($this->once())
            ->method('findReflection')
            ->with($mockReflector, $locatedSource, $identifier)
            ->will($this->throwException(new IdentifierNotFound('Foo', $identifier)));

        $sourceLocator = $this->getMockBuilder(AbstractSourceLocator::class)
            ->setConstructorArgs([$astLocator])
            ->onlyMethods(['createLocatedSource'])
            ->getMock();

        $sourceLocator->expects($this->once())
            ->method('createLocatedSource')
            ->with($identifier)
            ->will($this->returnValue($locatedSource));

        self::assertNull($sourceLocator->locateIdentifier($mockReflector, $identifier));
    }

    public function testLocateIdentifiersByTypeCallsFindReflectionsOfType(): void
    {
        $mockReflector = $this->createMock(Reflector::class);

        $locatedSource = new LocatedSource('<?php class Foo{}', 'Foo');

        $identifierType = new IdentifierType(IdentifierType::IDENTIFIER_CLASS);

        $mockReflection = $this->createMock(ReflectionClass::class);

        $astLocator = $this->createMock(AstLocator::class);

        $astLocator->expects($this->once())
            ->method('findReflectionsOfType')
            ->with($mockReflector, $locatedSource, $identifierType)
            ->will($this->returnValue([$mockReflection]));

        $sourceLocator = $this->getMockBuilder(AbstractSourceLocator::class)
            ->setConstructorArgs([$astLocator])
            ->onlyMethods(['createLocatedSource'])
            ->getMock();

        $sourceLocator->expects($this->once())
            ->method('createLocatedSource')
            ->will($this->returnValue($locatedSource));

        self::assertSame([$mockReflection], $sourceLocator->locateIdentifiersByType($mockReflector, $identifierType));
    }

    public function testLocateIdentifiersByTypeReturnsEmptyArrayWithoutTryingToFindReflectionsWhenUnableToLocateSource(): void
    {
        $mockReflector = $this->createMock(Reflector::class);

        $identifierType = new IdentifierType(IdentifierType::IDENTIFIER_CLASS);

        $astLocator = $this->createMock(AstLocator::class);

        $astLocator->expects($this->never())
            ->method('findReflectionsOfType');

        $sourceLocator = $this->getMockBuilder(AbstractSourceLocator::class)
            ->setConstructorArgs([$astLocator])
            ->onlyMethods(['createLocatedSource'])
            ->getMock();

        $sourceLocator->expects($this->once())
            ->method('createLocatedSource')
            ->will($this->returnValue(null));

        self::assertSame([], $sourceLocator->locateIdentifiersByType($mockReflector, $identifierType));
    }
}
