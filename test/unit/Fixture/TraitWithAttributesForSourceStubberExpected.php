<?php

namespace Roave\BetterReflectionTest\Fixture;

#[\Roave\BetterReflectionTest\Fixture\Attr]
#[\Roave\BetterReflectionTest\Fixture\AnotherAttr]
trait TraitWithAttributesForSourceStubber
{
    #[\Roave\BetterReflectionTest\Fixture\Attr]
    #[\Roave\BetterReflectionTest\Fixture\AnotherAttr]
    private array $propertyWithAttributes = [];
    #[\Roave\BetterReflectionTest\Fixture\Attr]
    #[\Roave\BetterReflectionTest\Fixture\AnotherAttr]
    public function methodWithAttributes(#[\Roave\BetterReflectionTest\Fixture\Attr] #[\Roave\BetterReflectionTest\Fixture\AnotherAttr] array $parameterWithAttributes): array
    {
    }
}
