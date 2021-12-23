<?php

if (\PHP_VERSION_ID < 80100) {
    if (interface_exists(UnitEnum::class, false)) {
        return;
    }

    interface UnitEnum
    {
        /**
         * @return static[]
         */
        public static function cases(): array;
    }
}

