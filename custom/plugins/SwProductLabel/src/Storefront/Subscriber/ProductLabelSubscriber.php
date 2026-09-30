<?php

declare(strict_types=1);

namespace SwProductLabel\Storefront\Subscriber;

use Shopware\Core\Content\Product\Events\ProductListingCriteriaEvent;
use Shopware\Storefront\Page\Product\ProductPageCriteriaEvent;
use SwProductLabel\Storefront\LabelCriteriaBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductLabelSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly LabelCriteriaBuilder $criteriaBuilder) {}

    public static function getSubscribedEvents(): array
    {
        return [
            ProductListingCriteriaEvent::class => 'addLabelAssociation',
            ProductPageCriteriaEvent::class => 'addLabelAssociation',
        ];
    }

    public function addLabelAssociation(ProductListingCriteriaEvent|ProductPageCriteriaEvent $event): void
    {
        $this->criteriaBuilder->addLabelAssociation($event->getCriteria());
    }
}
