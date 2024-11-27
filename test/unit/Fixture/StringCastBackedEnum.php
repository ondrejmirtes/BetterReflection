<?php

namespace Roave\BetterReflectionTest\Fixture;

enum StringCastBackedEnum: string
{
    case ENUM_CASE = 'string';

    const CONSTANT = 'constant';

    /**
     * Something
     */
    case ENUM_CASE_WITH_DOC_COMMENT = 'something';
}
