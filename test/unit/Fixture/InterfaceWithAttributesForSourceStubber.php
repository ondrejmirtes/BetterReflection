<?php

namespace Roave\BetterReflectionTest\Fixture;

#[Attr]
#[AnotherAttr]
interface InterfaceWithAttributesForSourceStubber
{
    #[Attr]
    #[AnotherAttr]
    public const CONSTANT_WITH_ATTRIBUTES = [];

    #[Attr]
    #[AnotherAttr]
    public function methodWithAttributes(
        #[Attr]
        #[AnotherAttr]
        array $parameterWithAttributes
    );
}
