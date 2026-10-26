<?php declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\FuncCall\RenameFunctionRector;
use Rector\Set\ValueObject\DowngradeLevelSetList;


return RectorConfig::configure()
    ->withSkip([
        dirname(__DIR__) . '/test/unit/Fixture',
        dirname(__DIR__) . '/src/Reflection/Adapter/ReflectionEnum*',
        dirname(__DIR__) . '/src/Reflection/Adapter/ReflectionParameterWithDocComment.php',
        // the tests pass enum cases to the adapter
        dirname(__DIR__) . '/test/unit/Reflection/Adapter/ReflectionEnumTest',
        // the test reflects a closure with a new in initializer
        \Rector\DowngradePhp81\Rector\FunctionLike\DowngradeNewInInitializerRector::class => [
            dirname(__DIR__) . '/test/unit/SourceLocator/SourceStubber/ReflectionSourceStubberTest.php',
        ],
        \Rector\DowngradePhp80\Rector\Class_\DowngradeAttributeToAnnotationRector::class,
        RenameFunctionRector::class,
    ])
    ->withSets([
        DowngradeLevelSetList::DOWN_TO_PHP_74,
    ]);
