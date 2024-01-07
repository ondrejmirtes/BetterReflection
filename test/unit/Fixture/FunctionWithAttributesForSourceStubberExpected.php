<?php

namespace Roave\BetterReflectionTest\Fixture;

#[\Roave\BetterReflectionTest\Fixture\Attr]
#[\Roave\BetterReflectionTest\Fixture\AnotherAttr]
function functionWithAttributesForSourceStubber(#[\Roave\BetterReflectionTest\Fixture\Attr] #[\Roave\BetterReflectionTest\Fixture\AnotherAttr] array $parameterWithAttributes)
{
}
