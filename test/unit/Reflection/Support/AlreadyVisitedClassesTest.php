<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\Support;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PHPStan\BetterReflection\Reflection\Exception\CircularReference;
use PHPStan\BetterReflection\Reflection\Support\AlreadyVisitedClasses;

#[CoversClass(AlreadyVisitedClasses::class)]
class AlreadyVisitedClassesTest extends TestCase
{
    public function testPushFailsWithCircularReference(): void
    {
        $alreadyVisitedClasses = AlreadyVisitedClasses::createEmpty();

        $alreadyVisitedClasses->push('foo');
        $alreadyVisitedClasses->push('bar');

        $this->expectException(CircularReference::class);
        $alreadyVisitedClasses->push('foo');
    }
}
