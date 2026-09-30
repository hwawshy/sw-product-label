<?php

declare(strict_types=1);

namespace SwProductLabel\Content\ProductLabel;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\SearchRanking;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use SwProductLabel\Content\ProductLabel\Aggregate\ProductLabelProduct\ProductLabelProductDefinition;
use SwProductLabel\Content\ProductLabel\Aggregate\ProductLabelTranslation\ProductLabelTranslationDefinition;

class ProductLabelDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'product_label';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return ProductLabelCollection::class;
    }

    public function getEntityClass(): string
    {
        return ProductLabelEntity::class;
    }

    public function since(): ?string
    {
        return '6.7.0.0';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new ApiAware(), new PrimaryKey(), new Required()),
            (new TranslatedField('name'))->addFlags(new ApiAware(), new SearchRanking(SearchRanking::HIGH_SEARCH_RANKING)),
            (new StringField('color', 'color'))->addFlags(new ApiAware(), new Required()),
            (new IntField('priority', 'priority'))->addFlags(new ApiAware()),
            (new BoolField('active', 'active'))->addFlags(new ApiAware()),
            (new DateTimeField('valid_from', 'validFrom'))->addFlags(new ApiAware()),
            (new DateTimeField('valid_to', 'validTo'))->addFlags(new ApiAware()),
            new CreatedAtField(),
            new UpdatedAtField(),
            (new ManyToManyAssociationField(
                'products',
                ProductDefinition::class,
                ProductLabelProductDefinition::class,
                'product_label_id',
                'product_id',
            ))->addFlags(new ApiAware(), new CascadeDelete()),
            (new TranslationsAssociationField(ProductLabelTranslationDefinition::class, 'product_label_id'))
                ->addFlags(new CascadeDelete()),
        ]);
    }
}
