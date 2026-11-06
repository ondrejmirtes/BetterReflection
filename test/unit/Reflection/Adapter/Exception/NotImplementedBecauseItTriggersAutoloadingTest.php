<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Adapter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PHPStan\BetterReflection\Reflection\Adapter\Exception\NotImplementedBecauseItTriggersAutoloading;

#[CoversClass(NotImplementedBecauseItTriggersAutoloading::class)]
class NotImplementedBecauseItTriggersAutoloadingTest extends TestCase
{
    public function testCreate(): void
    {
        $exception = NotImplementedBecauseItTriggersAutoloading::create();

        self::assertInstanceOf(NotImplementedBecauseItTriggersAutoloading::class, $exception);
        self::assertSame('Not implemented because it triggers autoloading', $exception->getMessage());
    }
}
