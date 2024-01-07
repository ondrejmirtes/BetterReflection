<?php

namespace Roave\BetterReflectionTest\Fixture;

#[Attr]
#[AnotherAttr]
trait TraitWithAttributesForSourceStubber
{
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
