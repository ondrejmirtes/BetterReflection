<?php

namespace Roave\BetterReflectionTest\Fixture;

enum StringCastPureEnum
{
    case ENUM_CASE;

    const CONSTANT = 'constant';

    /**
     * Something
     */
    case ENUM_CASE_WITH_DOC_COMMENT;
}
