<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\NodeCompiler;

use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PHPStan\BetterReflection\NodeCompiler\CompilerContext;
use PHPStan\BetterReflection\Reflection\ReflectionEnum;
use PHPStan\BetterReflection\Reflector\DefaultReflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

#[CoversClass(CompilerContext::class)]
class CompilerContextTest extends TestCase
{
    /**
     * @var \PHPStan\BetterReflection\SourceLocator\Ast\Locator
     */
    private $astLocator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->astLocator = BetterReflectionSingleton::instance()->astLocator();
    }

    public function testCreatingContextFromClass(): void
    {
        $phpCode = <<<'PHP'
<?php

namespace Foo;

class Boo
{
    public function baz($parameter = __CLASS__)
    {
    }
}
PHP;

        $reflector = new DefaultReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $class     = $reflector->reflectClass('Foo\Boo');

        $context = new CompilerContext($reflector, $class);

        self::assertSame($reflector, $context->getReflector());
        self::assertNull($context->getFileName());
        self::assertSame('Foo', $context->getNamespace());
        self::assertSame($class, $context->getClass());
        self::assertNull($context->getFunction());
    }

    public function testCreatingContextFromProperty(): void
    {
        $phpCode = <<<'PHP'
<?php

namespace Foo;

class Boo
{
    public $baz;
}
PHP;

        $reflector = new DefaultReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $class     = $reflector->reflectClass('Foo\Boo');
        $property  = $class->getProperty('baz');

        $context = new CompilerContext($reflector, $property);

        self::assertSame($reflector, $context->getReflector());
        self::assertNull($context->getFileName());
        self::assertSame('Foo', $context->getNamespace());
        self::assertSame($class, $context->getClass());
        self::assertNull($context->getFunction());
    }

    public function testCreatingContextFromClassConstant(): void
    {
        $phpCode = <<<'PHP'
<?php

namespace Foo;

class Boo
{
    public const BAZ = 'baz';
}
PHP;

        $reflector     = new DefaultReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $class         = $reflector->reflectClass('Foo\Boo');
        $classConstant = $class->getConstant('BAZ');

        $context = new CompilerContext($reflector, $classConstant);

        self::assertSame($reflector, $context->getReflector());
        self::assertNull($context->getFileName());
        self::assertSame('Foo', $context->getNamespace());
        self::assertSame($class, $context->getClass());
        self::assertNull($context->getFunction());
    }

    public function testCreatingContextFromEnumCase(): void
    {
        $phpCode = <<<'PHP'
<?php

namespace Foo;

enum Boo
{
    case BAZ;
}
PHP;

        $reflector = new DefaultReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $enum      = $reflector->reflectClass('Foo\Boo');

        self::assertInstanceOf(ReflectionEnum::class, $enum);

        $enumCase = $enum->getCase('BAZ');

        $context = new CompilerContext($reflector, $enumCase);

        self::assertSame($reflector, $context->getReflector());
        self::assertNull($context->getFileName());
        self::assertSame('Foo', $context->getNamespace());
        self::assertSame($enum, $context->getClass());
        self::assertNull($context->getFunction());
    }

    public function testCreatingContextFromMethod(): void
    {
        $phpCode = <<<'PHP'
<?php

namespace Foo;

class Boo
{
    public function baz($parameter = __CLASS__)
    {
    }
}
PHP;

        $reflector = new DefaultReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $class     = $reflector->reflectClass('Foo\Boo');
        $method    = $class->getMethod('baz');

        $context = new CompilerContext($reflector, $method);

        self::assertSame($reflector, $context->getReflector());
        self::assertNull($context->getFileName());
        self::assertSame('Foo', $context->getNamespace());
        self::assertSame($class, $context->getClass());
        self::assertSame($method, $context->getFunction());
    }

    public function testCreatingContextFromFunction(): void
    {
        $phpCode = <<<'PHP'
<?php

namespace Foo;

function baz($parameter = __CLASS__)
{
}
PHP;

        $reflector = new DefaultReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $function  = $reflector->reflectFunction('Foo\baz');

        $context = new CompilerContext($reflector, $function);

        self::assertSame($reflector, $context->getReflector());
        self::assertNull($context->getFileName());
        self::assertSame('Foo', $context->getNamespace());
        self::assertNull($context->getClass());
        self::assertSame($function, $context->getFunction());
    }

    public function testCreatingContextFromParameter(): void
    {
        $phpCode = <<<'PHP'
<?php

namespace Foo;

function baz($parameter = __CLASS__)
{
}
PHP;

        $reflector = new DefaultReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $function  = $reflector->reflectFunction('Foo\baz');
        $parameter = $function->getParameter('parameter');

        $context = new CompilerContext($reflector, $parameter);

        self::assertSame($reflector, $context->getReflector());
        self::assertNull($context->getFileName());
        self::assertSame('Foo', $context->getNamespace());
        self::assertNull($context->getClass());
        self::assertSame($function, $context->getFunction());
    }

    public function testCreatingContextFromConstant(): void
    {
        $phpCode = <<<'PHP'
<?php

namespace Foo;

const BAZ = 'baz';
PHP;

        $reflector = new DefaultReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $constant  = $reflector->reflectConstant('Foo\BAZ');

        $context = new CompilerContext($reflector, $constant);

        self::assertSame($reflector, $context->getReflector());
        self::assertNull($context->getFileName());
        self::assertSame('Foo', $context->getNamespace());
        self::assertNull($context->getClass());
        self::assertNull($context->getFunction());
    }

    public function testCreatingContextWithoutNamespace(): void
    {
        $phpCode = <<<'PHP'
<?php

const BAZ = 'baz';
PHP;

        $reflector = new DefaultReflector(new StringSourceLocator($phpCode, $this->astLocator));
        $constant  = $reflector->reflectConstant('BAZ');

        $context = new CompilerContext($reflector, $constant);

        self::assertNull($context->getNamespace());
    }

    public function testFilenameRealPath(): void
    {
        $constant = $this->createMock(ReflectionConstant::class);
        $constant->method('getFileName')->willReturn('/home/ondrej/../Test.php');
        $context = new CompilerContext($this->createMock(Reflector::class), $constant);
        self::assertSame('/home/Test.php', $context->getFileName());
    }

    public function testFilenameRealPathPhar(): void
    {
        $constant = $this->createMock(ReflectionConstant::class);
        $constant->method('getFileName')->willReturn('phar:///home/ondrej/../Test.php');
        $context = new CompilerContext($this->createMock(Reflector::class), $constant);
        self::assertSame('phar:///home/Test.php', $context->getFileName());
    }
}
