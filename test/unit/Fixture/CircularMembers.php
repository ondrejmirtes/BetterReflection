<?php

namespace Roave\BetterReflectionTest\Fixture\CircularMembers;

interface Interface1 extends Interface2
{
    const CONSTANT_1 = 1;

    public function method1(): void;
}

interface Interface2 extends Interface1
{
    const CONSTANT_2 = 2;

    public function method2(): void;
}

class Class1 extends Class2
{
    public $property1;

    public function method1(): void
    {
    }
}

class Class2 extends Class1
{
    public $property2;

    public function method2(): void
    {
    }
}

trait Trait1
{
    use Trait2;

    public $property1;

    public function method1(): void
    {
    }
}

trait Trait2
{
    use Trait1;

    public $property2;

    public function method2(): void
    {
    }
}
