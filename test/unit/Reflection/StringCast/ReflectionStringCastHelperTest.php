<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\StringCast;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\StringCast\ReflectionStringCastHelper;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

#[CoversClass(ReflectionStringCastHelper::class)]
class ReflectionStringCastHelperTest extends TestCase
{
    private Locator $astLocator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->astLocator = BetterReflectionSingleton::instance()->astLocator();
    }

    public function testNoDocComment(): void
    {
        $phpCode = <<<'PHP'
        <?php

        class Foo {
            const SOME_CONSTANT = 123;
        }

        PHP;

        $reflector               = new DefaultReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $classReflection         = $reflector->reflectClass('Foo');
        $classConstantReflection = $classReflection->getConstant('SOME_CONSTANT');

        self::assertSame('', ReflectionStringCastHelper::docCommentToString($classConstantReflection, false));
        self::assertSame('', ReflectionStringCastHelper::docCommentToString($classConstantReflection, true));
    }

    public function testNoDocCommentWithoutIdent(): void
    {
        $phpCode = <<<'PHP'
        <?php

        class Foo {
            /**
             * @var int
             */
            const SOME_CONSTANT = 123;
        }

        PHP;

        $reflector               = new DefaultReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $classReflection         = $reflector->reflectClass('Foo');
        $classConstantReflection = $classReflection->getConstant('SOME_CONSTANT');

        self::assertSame("/**\n * @var int\n */\n", ReflectionStringCastHelper::docCommentToString($classConstantReflection, false));
    }

    public function testNoDocCommentWitIdent(): void
    {
        $phpCode = <<<'PHP'
        <?php

        class Foo {
            /**
             * @var int
             */
            const SOME_CONSTANT = 123;
        }

        PHP;

        $reflector               = new DefaultReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $classReflection         = $reflector->reflectClass('Foo');
        $classConstantReflection = $classReflection->getConstant('SOME_CONSTANT');

        self::assertSame("/**\n     * @var int\n     */\n", ReflectionStringCastHelper::docCommentToString($classConstantReflection, true));
    }
}
