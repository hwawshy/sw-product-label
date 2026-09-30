<?php

declare(strict_types=1);

namespace SwProductLabel\Storefront\Subscriber;

use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use SwProductLabel\Content\ProductLabel\Aggregate\ProductLabelProduct\ProductLabelProductDefinition;
use SwProductLabel\Content\ProductLabel\ProductLabelDefinition;
use SwProductLabel\Storefront\Cache\ProductLabelCacheInvalidator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductLabelCacheSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly ProductLabelCacheInvalidator $cacheInvalidator) {}

    public static function getSubscribedEvents(): array
    {
        return [
            EntityWrittenContainerEvent::class => 'invalidateRouteCaches',
        ];
    }

    public function invalidateRouteCaches(EntityWrittenContainerEvent $event): void
    {
        $this->cacheInvalidator->invalidateByLabelIds(
            $event->getPrimaryKeys(ProductLabelDefinition::ENTITY_NAME),
        );

        $mappingEvent = $event->getEventByEntityName(ProductLabelProductDefinition::ENTITY_NAME);
        if (!$mappingEvent) {
            return;
        }

        $productIds = array_values(array_unique(array_filter(array_map(
            static fn(array $payload): string => \is_string($payload['productId']) ? $payload['productId'] : '',
            $mappingEvent->getPayloads(),
        ))));

        $this->cacheInvalidator->invalidateByProductIds($productIds);
    }
}
