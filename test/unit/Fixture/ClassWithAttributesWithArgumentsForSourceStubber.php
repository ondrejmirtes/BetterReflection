<?php

namespace Roave\BetterReflectionTest\Fixture;

#[Attr(true, 123, 'arg1', 'arg2', arg3: self::class, arg4: [0, ClassWithAttributes::class, [__CLASS__, ClassWithRepeatedAttributes::class]])]
class ClassWithAttributesWithArgumentsForSourceStubber
{
}
