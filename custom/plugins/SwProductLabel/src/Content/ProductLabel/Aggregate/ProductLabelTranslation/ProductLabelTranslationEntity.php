<?php

declare(strict_types=1);

namespace SwProductLabel\Content\ProductLabel\Aggregate\ProductLabelTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\TranslationEntity;

class ProductLabelTranslationEntity extends TranslationEntity
{
    protected string $productLabelId;

    protected ?string $name = null;

    public function getProductLabelId(): string
    {
        return $this->productLabelId;
    }

    public function setProductLabelId(string $productLabelId): void
    {
        $this->productLabelId = $productLabelId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }
}
