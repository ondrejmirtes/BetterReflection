<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Exception;

use PHPStan\BetterReflection\Reflection\Exception\PropertyIsNotStatic;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PHPStan\BetterReflection\Reflection\Exception\PropertyIsNotStatic
 */
class PropertyIsNotStaticTest extends TestCase
{
    public function testFromName() : void
    {
        $exception = PropertyIsNotStatic::fromName('boo');

        self::assertInstanceOf(PropertyIsNotStatic::class, $exception);
        self::assertSame('Property "boo" is not static', $exception->getMessage());
    }
}
