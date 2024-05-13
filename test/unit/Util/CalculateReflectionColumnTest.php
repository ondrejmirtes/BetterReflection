<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Util;

use PhpParser\Node;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PHPStan\BetterReflection\Util\CalculateReflectionColumn;

#[CoversClass(CalculateReflectionColumn::class)]
class CalculateReflectionColumnTest extends TestCase
{
    public function testGetStartColumn(): void
    {
        $source = "<?php\n    class Foo {}";

        $node = $this->createMock(Node::class);
        $node
            ->method('hasAttribute')
            ->with('startFilePos')
            ->willReturn(true);
        $node
            ->method('getStartFilePos')
            ->willReturn(10);

        self::assertSame(5, CalculateReflectionColumn::getStartColumn($source, $node));
    }

    public function testGetStartColumnIfAtTheBeginningOfLine(): void
    {
        $source = "<?php\nclass Foo {}";

        $node = $this->createMock(Node::class);
        $node
            ->method('hasAttribute')
            ->with('startFilePos')
            ->willReturn(true);
        $node
            ->method('getStartFilePos')
            ->willReturn(6);

        self::assertSame(1, CalculateReflectionColumn::getStartColumn($source, $node));
    }

    public function testGetStartColumnIfOneLineSource(): void
    {
        $source = '<?php class Foo {}';

        $node = $this->createMock(Node::class);
        $node
            ->method('hasAttribute')
            ->with('startFilePos')
            ->willReturn(true);
        $node
            ->method('getStartFilePos')
            ->willReturn(6);

        self::assertSame(7, CalculateReflectionColumn::getStartColumn($source, $node));
    }

    public function testGetEndColumn(): void
    {
        $source = "<?php\n    class Foo {}";

        $node = $this->createMock(Node::class);
        $node
            ->method('hasAttribute')
            ->with('endFilePos')
            ->willReturn(true);
        $node
            ->method('getEndFilePos')
            ->willReturn(21);

        self::assertSame(16, CalculateReflectionColumn::getEndColumn($source, $node));
    }

    public function testGetEndColumnIfAtTheEndOfLine(): void
    {
        $source = "<?php\nclass Foo {}";

        $node = $this->createMock(Node::class);
        $node
            ->method('hasAttribute')
            ->with('endFilePos')
            ->willReturn(true);
        $node
            ->method('getEndFilePos')
            ->willReturn(17);

        self::assertSame(12, CalculateReflectionColumn::getEndColumn($source, $node));
    }

    public function testGetEndColumnIfOneLineSource(): void
    {
        $source = '<?php class Foo {}';

        $node = $this->createMock(Node::class);
        $node
            ->method('hasAttribute')
            ->with('endFilePos')
            ->willReturn(true);
        $node
            ->method('getEndFilePos')
            ->willReturn(17);

        self::assertSame(18, CalculateReflectionColumn::getEndColumn($source, $node));
    }
}
