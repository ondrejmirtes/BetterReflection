<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass as CoreReflectionClass;
use ReflectionException;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\BetterReflection\Reflection\ReflectionFunction;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Located\InternalLocatedSource;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\SourceStubber;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\StubData;
use PHPStan\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function array_values;
use function get_declared_classes;
use function get_declared_interfaces;
use function get_declared_traits;
use function get_defined_constants;
use function get_defined_functions;
use function sprintf;

use const ARRAY_FILTER_USE_KEY;

#[CoversClass(PhpInternalSourceLocator::class)]
class PhpInternalSourceLocatorTest extends TestCase
{
    /**
     * @var \PHPStan\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator
     */
    private $phpInternalSourceLocator;

    protected function setUp(): void
    {
        parent::setUp();

        $betterReflection = BetterReflectionSingleton::instance();

        $this->phpInternalSourceLocator = new PhpInternalSourceLocator($betterReflection->astLocator(), $betterReflection->sourceStubber());
    }

    /**
     * @return \PHPStan\BetterReflection\Reflector\Reflector|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getMockReflector()
    {
        return $this->createMock(Reflector::class);
    }

    #[DataProvider('internalClassesProvider')]
    public function testCanFetchInternalLocatedSourceForClasses(string $className): void
    {
        try {
            $reflection = $this->phpInternalSourceLocator->locateIdentifier($this->getMockReflector(), new Identifier($className, new IdentifierType(IdentifierType::IDENTIFIER_CLASS)));

            self::assertInstanceOf(ReflectionClass::class, $reflection);

            $source = $reflection->getLocatedSource();

            self::assertInstanceOf(InternalLocatedSource::class, $source);
            self::assertNotEmpty($source->getSource());
        } catch (ReflectionException $e) {
            self::markTestIncomplete(sprintf('Can\'t reflect class "%s" due to an internal reflection exception: "%s".', $className, $e->getMessage()));
        }
    }

    /** @return list<array{0: string}> */
    public static function internalClassesProvider(): array
    {
        $allSymbols = array_merge(get_declared_classes(), get_declared_interfaces(), get_declared_traits());

        return array_map(static function (string $symbol) : array {
            return [$symbol];
        }, array_filter($allSymbols, static function (string $symbol): bool {
            $reflection = new CoreReflectionClass($symbol);

            return $reflection->isInternal();
        }));
    }

    #[DataProvider('internalFunctionsProvider')]
    public function testCanFetchInternalLocatedSourceForFunctions(string $functionName): void
    {
        try {
            $reflection = $this->phpInternalSourceLocator->locateIdentifier($this->getMockReflector(), new Identifier($functionName, new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)));

            self::assertInstanceOf(ReflectionFunction::class, $reflection);

            $source = $reflection->getLocatedSource();

            self::assertInstanceOf(InternalLocatedSource::class, $source);
            self::assertNotEmpty($source->getSource());
        } catch (ReflectionException $e) {
            self::markTestIncomplete(sprintf('Can\'t reflect function "%s" due to an internal reflection exception: "%s".', $functionName, $e->getMessage()));
        }
    }

    /** @return list<array{0: string}> */
    public static function internalFunctionsProvider(): array
    {
        /** @var list<string> $allSymbols */
        $allSymbols = get_defined_functions()['internal'];

        return array_map(static function (string $symbol) : array {
            return [$symbol];
        }, $allSymbols);
    }

    #[DataProvider('internalConstantsProvider')]
    public function testCanFetchInternalLocatedSourceForConstants(string $constantName): void
    {
        $reflection = $this->phpInternalSourceLocator->locateIdentifier($this->getMockReflector(), new Identifier($constantName, new IdentifierType(IdentifierType::IDENTIFIER_CONSTANT)));

        self::assertInstanceOf(ReflectionConstant::class, $reflection);

        $source = $reflection->getLocatedSource();

        self::assertInstanceOf(InternalLocatedSource::class, $source);
        self::assertNotEmpty($source->getSource());
    }

    /** @return list<array{0: string}> */
    public static function internalConstantsProvider(): array
    {
        /** @var array<string, array<string, int|string|float|bool|mixed[]|resource|null>> $allSymbols */
        $allSymbols = get_defined_constants(true);

        return array_map(static function (string $symbol) : array {
            return [$symbol];
        }, array_keys(array_merge(...array_values(array_filter($allSymbols, static function (string $extensionName) : bool {
            return $extensionName !== 'user';
        }, ARRAY_FILTER_USE_KEY)))));
    }

    public function testReturnsNullForNonExistentClass(): void
    {
        self::assertNull($this->phpInternalSourceLocator->locateIdentifier($this->getMockReflector(), new Identifier('Foo\Bar', new IdentifierType(IdentifierType::IDENTIFIER_CLASS))));
    }

    public function testReturnsNullForNonExistentFunction(): void
    {
        self::assertNull($this->phpInternalSourceLocator->locateIdentifier($this->getMockReflector(), new Identifier('foo', new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION))));
    }

    public function testReturnsNullForNonExistentConstant(): void
    {
        self::assertNull($this->phpInternalSourceLocator->locateIdentifier($this->getMockReflector(), new Identifier('foo', new IdentifierType(IdentifierType::IDENTIFIER_CONSTANT))));
    }

    public function testReturnsNullForNonInternal(): void
    {
        $sourceStubber = $this->createMock(SourceStubber::class);
        $sourceStubber
            ->method('generateClassStub')
            ->with('Foo')
            ->willReturn(new StubData('stub', null, null));

        $phpInternalSourceLocator = new PhpInternalSourceLocator(BetterReflectionSingleton::instance()->astLocator(), $sourceStubber);

        self::assertNull($phpInternalSourceLocator->locateIdentifier($this->getMockReflector(), new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS))));
    }
}
