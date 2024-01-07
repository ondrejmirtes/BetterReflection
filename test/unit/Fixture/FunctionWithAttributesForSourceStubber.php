<?php

namespace Roave\BetterReflectionTest\Fixture;

#[Attr]
#[AnotherAttr]
function functionWithAttributesForSourceStubber(
    #[Attr]
    #[AnotherAttr]
    array $parameterWithAttributes
)
{
}
