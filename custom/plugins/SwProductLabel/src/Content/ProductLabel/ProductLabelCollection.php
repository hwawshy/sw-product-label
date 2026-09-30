<?php

declare(strict_types=1);

namespace SwProductLabel\Content\ProductLabel;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<ProductLabelEntity>
 */
class ProductLabelCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ProductLabelEntity::class;
    }
}
