<?php

declare(strict_types=1);

use SwProductLabel\Content\Product\ProductLabelExtension;
use SwProductLabel\Content\ProductLabel\Aggregate\ProductLabelProduct\ProductLabelProductDefinition;
use SwProductLabel\Content\ProductLabel\Aggregate\ProductLabelTranslation\ProductLabelTranslationDefinition;
use SwProductLabel\Content\ProductLabel\ProductLabelDefinition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set(ProductLabelDefinition::class);
    $services->set(ProductLabelTranslationDefinition::class);
    $services->set(ProductLabelProductDefinition::class);
    $services->set(ProductLabelExtension::class);
};
