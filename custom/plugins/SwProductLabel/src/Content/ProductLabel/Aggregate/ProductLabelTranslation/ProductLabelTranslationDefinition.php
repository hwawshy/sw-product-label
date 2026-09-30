<?php

declare(strict_types=1);

namespace SwProductLabel\Content\ProductLabel\Aggregate\ProductLabelTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use SwProductLabel\Content\ProductLabel\ProductLabelDefinition;

class ProductLabelTranslationDefinition extends EntityTranslationDefinition
{
    final public const ENTITY_NAME = 'product_label_translation';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return ProductLabelTranslationCollection::class;
    }

    public function getEntityClass(): string
    {
        return ProductLabelTranslationEntity::class;
    }

    public function since(): ?string
    {
        return '6.7.0.0';
    }

    protected function getParentDefinitionClass(): string
    {
        return ProductLabelDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new StringField('name', 'name'))->addFlags(new ApiAware(), new Required()),
        ]);
    }
}
