<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Reflection\StringCast;

use PHPStan\BetterReflection\Reflector\ClassReflector;
use PHPStan\BetterReflection\Reflector\FunctionReflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

/**
 * @covers \PHPStan\BetterReflection\Reflection\StringCast\ReflectionFunctionStringCast
 */
class ReflectionFunctionStringCastTest extends TestCase
{
    /** @var Locator */
    private $astLocator;

    /** @var ClassReflector */
    private $classReflector;

    protected function setUp() : void
    {
        parent::setUp();

        $betterReflection = BetterReflectionSingleton::instance();

        $this->astLocator     = $betterReflection->astLocator();
        $this->classReflector = $betterReflection->classReflector();
    }

    public function toStringProvider() : array
    {
        return [
            ['Roave\BetterReflectionTest\Fixture\functionWithoutParameters', "Function [ <user> function Roave\BetterReflectionTest\Fixture\\functionWithoutParameters ] {\n  @@ %s/Fixture/StringCastFunctions.php 5 - 7\n}"],
            ['Roave\BetterReflectionTest\Fixture\functionWithParameters', "Function [ <user> function Roave\BetterReflectionTest\Fixture\\functionWithParameters ] {\n  @@ %s/Fixture/StringCastFunctions.php 9 - 11\n\n  - Parameters [2] {\n    Parameter #0 [ <required> \$a ]\n    Parameter #1 [ <required> \$b ]\n  }\n}"],
        ];
    }

    /**
     * @dataProvider toStringProvider
     */
    public function testToString(string $functionName, string $expectedString) : void
    {
        $reflector          = new FunctionReflector(new SingleFileSourceLocator(__DIR__ . '/../../Fixture/StringCastFunctions.php', $this->astLocator), $this->classReflector);
        $functionReflection = $reflector->reflect($functionName);

        self::assertStringMatchesFormat($expectedString, (string) $functionReflection);
    }
}
