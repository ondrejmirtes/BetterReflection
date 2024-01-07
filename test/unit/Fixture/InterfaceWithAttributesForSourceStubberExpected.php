<?php

namespace Roave\BetterReflectionTest\Fixture;

#[\Roave\BetterReflectionTest\Fixture\Attr]
#[\Roave\BetterReflectionTest\Fixture\AnotherAttr]
interface InterfaceWithAttributesForSourceStubber
{
    #[\Roave\BetterReflectionTest\Fixture\Attr]
    #[\Roave\BetterReflectionTest\Fixture\AnotherAttr]
    public const CONSTANT_WITH_ATTRIBUTES = [];
    #[\Roave\BetterReflectionTest\Fixture\Attr]
    #[\Roave\BetterReflectionTest\Fixture\AnotherAttr]
    public function methodWithAttributes(#[\Roave\BetterReflectionTest\Fixture\Attr] #[\Roave\BetterReflectionTest\Fixture\AnotherAttr] array $parameterWithAttributes);
}
