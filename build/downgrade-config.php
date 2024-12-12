<?php declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\FuncCall\RenameFunctionRector;
use Rector\Set\ValueObject\DowngradeLevelSetList;


return RectorConfig::configure()
    ->withSkip([
        dirname(__DIR__) . '/test/unit/Fixture',
        dirname(__DIR__) . '/src/Reflection/Adapter/ReflectionEnum*',
        \Rector\DowngradePhp80\Rector\Class_\DowngradeAttributeToAnnotationRector::class,
        RenameFunctionRector::class,
    ])
    ->withSets([
        DowngradeLevelSetList::DOWN_TO_PHP_74,
    ]);
