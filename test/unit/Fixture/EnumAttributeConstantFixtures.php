<?php

namespace Roave\BetterReflectionTest\Fixture;

enum Foo: int {
    case ONE = 1;
    const MAPPING = [
        self::ONE->value => 'one',
    ];
}

#[\Attribute]
class MyAttr
{
    public function __construct(private array $mapping)
    {
    }
}

#[MyAttr(Foo::MAPPING)]
class Bar {

}

#[MyAttr([Foo::ONE])]
class Baz {

}
