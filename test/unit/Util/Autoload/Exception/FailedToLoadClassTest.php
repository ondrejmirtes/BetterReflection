<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Util\Autoload\Exception;

use PHPStan\BetterReflection\Util\Autoload\Exception\FailedToLoadClass;
use PHPUnit\Framework\TestCase;
use function sprintf;
use function uniqid;

/**
 * @covers \PHPStan\BetterReflection\Util\Autoload\Exception\FailedToLoadClass
 */
final class FailedToLoadClassTest extends TestCase
{
    public function testFromReflectionClass() : void
    {
        $className = uniqid('class name', true);

        $exception = FailedToLoadClass::fromClassName($className);

        self::assertInstanceOf(FailedToLoadClass::class, $exception);
        self::assertSame(
            sprintf('Unable to load class %s', $className),
            $exception->getMessage()
        );
    }
}
