<?php

declare(strict_types=1);

use SwProductLabel\Command\ProductLabelDeactivateExpiredCommand;
use SwProductLabel\Command\ProductLabelDemoCommand;
use SwProductLabel\Content\Product\ProductLabelExtension;
use SwProductLabel\Content\ProductLabel\Aggregate\ProductLabelProduct\ProductLabelProductDefinition;
use SwProductLabel\Content\ProductLabel\Aggregate\ProductLabelTranslation\ProductLabelTranslationDefinition;
use SwProductLabel\Content\ProductLabel\ProductLabelDefinition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set(ProductLabelDefinition::class);
    $services->set(ProductLabelTranslationDefinition::class);
    $services->set(ProductLabelProductDefinition::class);
    $services->set(ProductLabelExtension::class);

    $services->set(ProductLabelDeactivateExpiredCommand::class);
    $services->set(ProductLabelDemoCommand::class)
        ->arg('$labelRepository', service('product_label.repository'))
        ->arg('$productRepository', service('product.repository'))
        ->arg('$taxRepository', service('tax.repository'));

    $services->load('SwProductLabel\\Storefront\\', __DIR__ . '/../../Storefront');
};
