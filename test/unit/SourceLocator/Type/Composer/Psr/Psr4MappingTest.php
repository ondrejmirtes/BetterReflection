<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Type\Composer\Psr;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\SourceLocator\Type\Composer\Psr\Psr4Mapping;

#[CoversClass(Psr4Mapping::class)]
class Psr4MappingTest extends TestCase
{
    /**
     * @param array<string, list<string>> $mappings
     * @param list<string>                $expectedDirectories
     */
    #[DataProvider('mappings')]
    public function testExpectedDirectories(array $mappings, array $expectedDirectories): void
    {
        self::assertEquals($expectedDirectories, Psr4Mapping::fromArrayMappings($mappings)->directories());
    }

    /** @param array<string, list<string>> $mappings */
    #[DataProvider('mappings')]
    public function testIdempotentConstructor(array $mappings): void
    {
        self::assertEquals(Psr4Mapping::fromArrayMappings($mappings), Psr4Mapping::fromArrayMappings($mappings));
    }

    /** @return array<string, array{0: array<string, list<string>>, 1: list<string>}> */
    public static function mappings(): array
    {
        return [
            'one directory, one prefix'                  => [
                ['foo' => [__DIR__]],
                [__DIR__],
            ],
            'two directories, one prefix'                => [
                ['foo' => [__DIR__, __DIR__ . '/../..']],
                [__DIR__, __DIR__ . '/../..'],
            ],
            'two directories, one duplicate, one prefix' => [
                ['foo' => [__DIR__, __DIR__, __DIR__ . '/../..']],
                [__DIR__, __DIR__ . '/../..'],
            ],
            'two directories, two prefixes'              => [
                [
                    'foo' => [__DIR__],
                    'bar' => [__DIR__ . '/../..'],
                ],
                [__DIR__, __DIR__ . '/../..'],
            ],
            'trailing slash in directory is trimmed'     => [
                ['foo' => [__DIR__ . '/']],
                [__DIR__],
            ],
        ];
    }

    /**
     * @param array<string, list<string>> $mappings
     * @param list<string>                $expectedFiles
     */
    #[DataProvider('classLookupMappings')]
    public function testClassLookups(array $mappings, Identifier $identifier, array $expectedFiles): void
    {
        self::assertEquals($expectedFiles, Psr4Mapping::fromArrayMappings($mappings)->resolvePossibleFilePaths($identifier));
    }

    /** @return array<string, array{0: array<string, list<string>>, 1: Identifier, 2: list<string>}> */
    public static function classLookupMappings(): array
    {
        return [
            'empty mappings, no match' => [
                [],
                new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
                [],
            ],
            'one mapping, no match for function identifier' => [
                ['Foo\\' => [__DIR__]],
                new Identifier('Foo\\Bar', new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)),
                [],
            ],
            'one mapping, match' => [
                ['Foo\\' => [__DIR__]],
                new Identifier('Foo\\Bar', new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
                [__DIR__ . '/Bar.php'],
            ],
            'trailing and leading slash in mapping is trimmed' => [
                ['Foo' => [__DIR__ . '/']],
                new Identifier('Foo\\Bar', new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
                [__DIR__ . '/Bar.php'],
            ],
            'one mapping, no match if class === prefix' => [
                ['Foo' => [__DIR__]],
                new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
                [],
            ],
            'multiple mappings, match when class !== prefix' => [
                [
                    'Foo\\Bar' => [__DIR__ . '/../..'],
                    'Foo' => [__DIR__ . '/..'],
                ],
                new Identifier('Foo\\Bar', new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
                [__DIR__ . '/../Bar.php'],
            ],
            'multiple mappings, multiple possible matches (but not all)' => [
                [
                    'Foo\\Bar' => [__DIR__ . '/../..'],
                    'Boo' => [__DIR__],
                    'Foo' => [__DIR__ . '/..'],
                ],
                new Identifier('Foo\\Bar\\Boo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
                [__DIR__ . '/../../Boo.php', __DIR__ . '/../Bar/Boo.php'],
            ],
        ];
    }
}
