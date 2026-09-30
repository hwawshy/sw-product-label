<?php

declare(strict_types=1);

namespace SwProductLabel\Content\ProductLabel;

use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use SwProductLabel\Content\ProductLabel\Aggregate\ProductLabelTranslation\ProductLabelTranslationCollection;

class ProductLabelEntity extends Entity
{
    use EntityIdTrait;

    protected ?string $name = null;

    protected string $color;

    protected int $priority;

    protected bool $active;

    protected ?\DateTimeInterface $validFrom = null;

    protected ?\DateTimeInterface $validTo = null;

    protected ?ProductCollection $products = null;

    protected ?ProductLabelTranslationCollection $translations = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): void
    {
        $this->color = $color;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getValidFrom(): ?\DateTimeInterface
    {
        return $this->validFrom;
    }

    public function setValidFrom(?\DateTimeInterface $validFrom): void
    {
        $this->validFrom = $validFrom;
    }

    public function getValidTo(): ?\DateTimeInterface
    {
        return $this->validTo;
    }

    public function setValidTo(?\DateTimeInterface $validTo): void
    {
        $this->validTo = $validTo;
    }

    public function getProducts(): ?ProductCollection
    {
        return $this->products;
    }

    public function setProducts(?ProductCollection $products): void
    {
        $this->products = $products;
    }

    public function getTranslations(): ?ProductLabelTranslationCollection
    {
        return $this->translations;
    }

    public function setTranslations(?ProductLabelTranslationCollection $translations): void
    {
        $this->translations = $translations;
    }
}
