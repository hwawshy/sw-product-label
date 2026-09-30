<?php

declare(strict_types=1);

namespace SwProductLabel\Storefront\Cache;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Product\SalesChannel\Detail\ProductDetailRoute;
use Shopware\Core\Content\Product\SalesChannel\Listing\ProductListingRoute;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Adapter\Cache\CacheInvalidator;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Invalidates the cached product listing and detail routes that render
 * product labels. The listing route caches its result per category, the
 * detail route per product, so the affected categories and products have to
 * be resolved for the labels that changed.
 */
class ProductLabelCacheInvalidator
{
    public function __construct(
        private readonly CacheInvalidator $cacheInvalidator,
        private readonly Connection $connection,
    ) {}

    /**
     * @param list<string> $labelIds
     */
    public function invalidateByLabelIds(array $labelIds): void
    {
        if ($labelIds === []) {
            return;
        }

        $productIds = array_map(
            static fn(mixed $productId): string => \is_string($productId) ? $productId : '',
            $this->connection->fetchFirstColumn(
                'SELECT DISTINCT LOWER(HEX(product_id))
                 FROM product_label_product
                 WHERE product_label_id IN (:labelIds)',
                ['labelIds' => Uuid::fromHexToBytesList($labelIds)],
                ['labelIds' => ArrayParameterType::BINARY],
            ),
        );

        $this->invalidateByProductIds($productIds);
    }

    /**
     * @param list<string> $productIds
     */
    public function invalidateByProductIds(array $productIds): void
    {
        if ($productIds === []) {
            return;
        }

        $categoryIds = array_map(
            static fn(mixed $categoryId): string => \is_string($categoryId) ? $categoryId : '',
            $this->connection->fetchFirstColumn(
                'SELECT DISTINCT LOWER(HEX(category_id))
                 FROM product_category_tree
                 WHERE product_id IN (:productIds)
                 AND product_version_id = :version',
                [
                    'productIds' => Uuid::fromHexToBytesList($productIds),
                    'version' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                ],
                ['productIds' => ArrayParameterType::BINARY],
            ),
        );

        $this->cacheInvalidator->invalidate([
            ...array_map(ProductDetailRoute::buildName(...), $productIds),
            ...array_map(ProductListingRoute::buildName(...), $categoryIds),
        ]);
    }
}
