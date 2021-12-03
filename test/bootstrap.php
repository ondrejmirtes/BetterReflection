<?php

declare(strict_types=1);

if (PHP_VERSION_ID >= 80000) {
    require __DIR__ . '/../stubs/UnitEnum.php';
    require __DIR__ . '/../stubs/BackedEnum.php';

    require __DIR__ . '/../stubs/ReflectionEnum.php';
    require __DIR__ . '/../stubs/ReflectionEnumUnitCase.php';
    require __DIR__ . '/../stubs/ReflectionEnumBackedCase.php';
}

require __DIR__ . '/../stubs/ReflectionIntersectionType.php';
require __DIR__ . '/../stubs/ReflectionUnionType.php';
require __DIR__ . '/../stubs/ReflectionAttribute.php';
require __DIR__ . '/../stubs/Attribute.php';

$GLOBALS['loader'] = require __DIR__ . '/../vendor/autoload.php';
