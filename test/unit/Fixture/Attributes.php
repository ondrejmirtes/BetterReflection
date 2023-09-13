<?php

namespace Roave\BetterReflectionTest\Fixture;

use Attribute;

const SOME_CONSTANT = 'some-constant';

#[Attribute]
class Attr
{
}

#[Attribute]
class AnotherAttr extends Attr
{
}

#[Attr]
#[AnotherAttr]
class ClassWithAttributes
{

    #[Attr]
    #[AnotherAttr]
    public const CONSTANT_WITH_ATTRIBUTES = [];

    #[Attr]
    #[AnotherAttr]
    private array $propertyWithAttributes = [];

    #[Attr]
    #[AnotherAttr]
    public function methodWithAttributes(
        #[Attr]
        #[AnotherAttr]
        array $parameterWithAttributes
    ): array
    {

    }

    public function __construct(
        #[Attr]
        #[AnotherAttr]
        private $promotedPropertyWithAttributes
    )
    {
    }
}

#[Attr]
#[AnotherAttr]
function functionWithAttributes()
{
}

#[Attr]
#[AnotherAttr]
#[AnotherAttr]
class ClassWithRepeatedAttributes
{

}

#[Attr('arg1', 'arg2', arg3: self::class, arg4: [0, ClassWithAttributes::class, [__CLASS__, ClassWithRepeatedAttributes::class]])]
class ClassWithAttributesWithArguments
{
}

#[Attr]
#[AnotherAttr]
enum EnumWithAttributes
{
    #[Attr]
    #[AnotherAttr]
    case CASE_WITH_ATTRIBUTES;
}

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
