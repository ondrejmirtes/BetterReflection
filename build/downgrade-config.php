<?php declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\DowngradeLevelSetList;

return static function (RectorConfig $config): void {
    $config->skip([
        '*/test/unit/Fixture/*',
        'src/Reflection/Adapter/ReflectionEnum*'
    ]);
    $config->sets([DowngradeLevelSetList::DOWN_TO_PHP_73]);
};
