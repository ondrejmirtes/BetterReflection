<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection;

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionEnum;
use Roave\BetterReflection\Reflection\ReflectionEnumCase;
use Roave\BetterReflection\Reflection\ReflectionNamedType;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;
use Roave\BetterReflectionTest\Fixture\IntEnum;
use Roave\BetterReflectionTest\Fixture\PureEnum;
use Roave\BetterReflectionTest\Fixture\StringEnum;

use function var_export;

#[CoversClass(ReflectionEnum::class)]
class ReflectionEnumTest extends TestCase
{
    private Locator $astLocator;

    private Reflector $reflector;

    public function setUp(): void
    {
        parent::setUp();

        $this->astLocator = BetterReflectionSingleton::instance()->astLocator();
        $this->reflector  = new DefaultReflector(new SingleFileSourceLocator(__DIR__ . '/../Fixture/Enums.php', $this->astLocator));
    }

    /** @return list<array{0: class-string}> */
    public static function dataCanReflect(): array
    {
        return [
            [PureEnum::class],
            [IntEnum::class],
            [StringEnum::class],
        ];
    }

    #[DataProvider('dataCanReflect')]
    public function testCanReflect(string $enumName): void
    {
        $enumReflection = $this->reflector->reflectClass($enumName);

        self::assertInstanceOf(ReflectionEnum::class, $enumReflection);
    }

    /** @return list<array{0: non-empty-string, 1: bool}> */
    public static function dataHasAndGetCase(): array
    {
        return [
            ['ONE', true],
            ['TWO', true],
            ['THREE', true],
            ['FOUR', false],
        ];
    }

    /** @param non-empty-string $caseName */
    #[DataProvider('dataHasAndGetCase')]
    public function testHasAndGetCase(string $caseName, bool $exists): void
    {
        $enumReflection = $this->reflector->reflectClass(PureEnum::class);

        self::assertInstanceOf(ReflectionEnum::class, $enumReflection);

        self::assertSame($exists, $enumReflection->hasCase($caseName));

        $case = $enumReflection->getCase($caseName);

        if ($exists) {
            self::assertInstanceOf(ReflectionEnumCase::class, $case);
        } else {
            self::assertNull($case);
        }
    }

    /** @return list<array{0: class-string, 1: int}> */
    public static function dataGetCases(): array
    {
        return [
            [PureEnum::class, 3],
            [IntEnum::class, 4],
            [StringEnum::class, 5],
        ];
    }

    #[DataProvider('dataGetCases')]
    public function testGetCases(string $enumName, int $casesCount): void
    {
        $enumReflection = $this->reflector->reflectClass($enumName);

        self::assertInstanceOf(ReflectionEnum::class, $enumReflection);

        $cases = $enumReflection->getCases();

        self::assertCount($casesCount, $cases);
        self::assertContainsOnlyInstancesOf(ReflectionEnumCase::class, $cases);
    }

    /** @return list<array{0: class-string, 1: bool}> */
    public static function dataIsBacked(): array
    {
        return [
            [PureEnum::class, false],
            [IntEnum::class, true],
            [StringEnum::class, true],
        ];
    }

    #[DataProvider('dataIsBacked')]
    public function testIsBacked(string $enumName, bool $isBacked): void
    {
        $enumReflection = $this->reflector->reflectClass($enumName);

        self::assertInstanceOf(ReflectionEnum::class, $enumReflection);
        self::assertSame($isBacked, $enumReflection->isBacked());
    }

    /** @return list<array{0: class-string, 1: string}> */
    public static function dataGetBackingType(): array
    {
        return [
            [IntEnum::class, 'int'],
            [StringEnum::class, 'string'],
        ];
    }

    #[DataProvider('dataGetBackingType')]
    public function testGetBackingType(string $enumName, string $expectedBackingType): void
    {
        $enumReflection = $this->reflector->reflectClass($enumName);

        self::assertInstanceOf(ReflectionEnum::class, $enumReflection);

        $backingType = $enumReflection->getBackingType();

        self::assertInstanceOf(ReflectionNamedType::class, $backingType);
        self::assertSame($expectedBackingType, $backingType->__toString());
    }

    public function testGetBackingTypeExceptionForPureEnum(): void
    {
        $enumReflection = $this->reflector->reflectClass(PureEnum::class);

        self::assertInstanceOf(ReflectionEnum::class, $enumReflection);

        $this->expectException(LogicException::class);
        $enumReflection->getBackingType();
    }

    public function testCaching(): void
    {
        $reflection = $this->reflector->reflectClass(IntEnum::class);
        $data       = $reflection->exportToCache();

        $importedReflection = ReflectionEnum::importFromCache($this->reflector, eval('return ' . var_export($data, true) . ';'));

        // The imported reflection may hold lazily-restored state, so it is compared through what it exports
        self::assertSame($data, $importedReflection->exportToCache());
    }

    /** @return list<array{class-string, list<non-empty-string>}> */
    public static function dataEngineProvidedMethodsStayInternalThroughCache(): array
    {
        return [
            [PureEnum::class, ['cases']],
            [IntEnum::class, ['cases', 'from', 'tryFrom']],
            [StringEnum::class, ['cases', 'from', 'tryFrom']],
        ];
    }

    /** @param list<non-empty-string> $engineProvidedMethodNames */
    #[DataProvider('dataEngineProvidedMethodsStayInternalThroughCache')]
    public function testEngineProvidedMethodsStayInternalThroughCache(string $enumName, array $engineProvidedMethodNames): void
    {
        $reflection = $this->reflector->reflectClass($enumName);
        self::assertInstanceOf(ReflectionEnum::class, $reflection);
        $data               = $reflection->exportToCache();
        $readData           = eval('return ' . var_export($data, true) . ';');
        $importedReflection = ReflectionEnum::importFromCache($this->reflector, $readData);

        foreach ($engineProvidedMethodNames as $methodName) {
            $method = $importedReflection->getMethod($methodName);
            self::assertNotNull($method);
            self::assertTrue($method->isInternal(), $methodName);
            self::assertSame('Core', $method->getExtensionName(), $methodName);
        }

        $someMethod = $importedReflection->getMethod('someMethod');
        self::assertNotNull($someMethod);
        self::assertFalse($someMethod->isInternal());
        self::assertSame($importedReflection->getLocatedSource(), $someMethod->getLocatedSource());
    }
}
