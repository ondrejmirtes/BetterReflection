<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\SourceLocator\Located;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PHPStan\BetterReflection\SourceLocator\Located\AliasLocatedSource;
use PHPStan\BetterReflection\Util\FileHelper;

#[CoversClass(AliasLocatedSource::class)]
class AliasLocatedSourceTest extends TestCase
{
    public function testInternalsLocatedSource(): void
    {
        $fileName = FileHelper::normalizeWindowsPath(__FILE__);

        $locatedSource = new AliasLocatedSource('foo', 'name', $fileName, 'aliasName');

        self::assertSame('foo', $locatedSource->getSource());
        self::assertSame('name', $locatedSource->getName());
        self::assertSame($fileName, $locatedSource->getFileName());
        self::assertSame('aliasName', $locatedSource->getAliasName());
    }
}
