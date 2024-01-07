<?php

namespace Roave\BetterReflectionTest\Fixture;

#[Attr]
#[AnotherAttr]
enum EnumWithAttributesForSourceStubber
{
    #[Attr]
    #[AnotherAttr]
    case CASE_WITH_ATTRIBUTES;
}
