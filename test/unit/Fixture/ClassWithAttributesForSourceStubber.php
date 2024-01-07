<?php

namespace Roave\BetterReflectionTest\Fixture;

#[Attr]
#[AnotherAttr]
class ClassWithAttributesForSourceStubber
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
}
