<?php

namespace Roave\BetterReflectionTest\Fixture;

#[\Roave\BetterReflectionTest\Fixture\Attr]
#[\Roave\BetterReflectionTest\Fixture\AnotherAttr]
enum EnumWithAttributesForSourceStubber
{
    #[\Roave\BetterReflectionTest\Fixture\Attr]
    #[\Roave\BetterReflectionTest\Fixture\AnotherAttr]
    case CASE_WITH_ATTRIBUTES;
}
