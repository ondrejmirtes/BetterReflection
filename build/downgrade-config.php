<?php declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Set\ValueObject\DowngradeLevelSetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set(Option::SKIP, [
        '*/test/unit/Fixture/*',
        'src/Reflection/Adapter/ReflectionEnum*'
    ]);
    $containerConfigurator->import(DowngradeLevelSetList::DOWN_TO_PHP_73);

    $services = $containerConfigurator->services();
    $services->set(\Rector\DowngradePhp72\Rector\FunctionLike\DowngradeObjectTypeDeclarationRector::class);
};
