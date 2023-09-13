<?php

namespace Roave\BetterReflectionTest\Fixture;

use Attribute;

enum SomeEnum: int
{

    case ONE = 1;
    case TWO = 2;

}

#[Attribute]
class AttributeThatAcceptsArgument
{

    public function __construct(public SomeEnum $e)
    {

    }

}

#[AttributeThatAcceptsArgument(e: SomeEnum::ONE)]
class ClassWithAttributeThatAcceptsArgument
{

}

class NestedClassUsingNamedArguments
{
    public function __construct(public ?SomeEnum $e = null, public string $s = '')
    {
    }
}

#[Attribute]
class AttributeThatHasNestedClassUsingNamedArguments
{
    public function __construct(public NestedClassUsingNamedArguments $nested)
    {
    }
}

#[AttributeThatHasNestedClassUsingNamedArguments(new NestedClassUsingNamedArguments(s: 'string'))]
class ClassWithAttributeThatHasNestedClassUsingNamedArguments
{
}
