<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Util\Exception;

use PhpParser\Lexer;
use PhpParser\Node\Scalar\LNumber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PHPStan\BetterReflection\Util\Exception\NoNodePosition;

use function sprintf;

#[CoversClass(NoNodePosition::class)]
class NoNodePositionTest extends TestCase
{
    public function testFromPosition(): void
    {
        $node = new LNumber(123);

        $exception = NoNodePosition::fromNode($node);

        self::assertInstanceOf(NoNodePosition::class, $exception);
        self::assertSame(sprintf('%s doesn\'t contain position. Your %s is not configured properly', get_class($node), Lexer::class), $exception->getMessage());
    }
}
