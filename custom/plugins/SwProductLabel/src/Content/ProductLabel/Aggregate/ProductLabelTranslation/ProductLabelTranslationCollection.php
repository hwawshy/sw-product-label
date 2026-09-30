<?php

declare(strict_types=1);

namespace SwProductLabel\Content\ProductLabel\Aggregate\ProductLabelTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<ProductLabelTranslationEntity>
 */
class ProductLabelTranslationCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ProductLabelTranslationEntity::class;
    }
}
