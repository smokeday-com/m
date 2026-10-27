<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogInventory\Model\Indexer\Stock;

use Magento\Catalog\Model\Category;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Indexer\CacheContext;
use Magento\CatalogInventory\Model\Stock;
use Magento\Catalog\Model\Product;

/**
 * Clean product cache only when stock status was updated
 */
class CacheCleaner
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @var CacheContext
     */
    private $cacheContext;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @param ResourceConnection $resource
     * @param StockConfigurationInterface $stockConfiguration
     * @param CacheContext $cacheContext
     * @param ManagerInterface $eventManager
     * @param MetadataPool|null $metadataPool
     */
    public function __construct(
        ResourceConnection $resource,
        StockConfigurationInterface $stockConfiguration,
        CacheContext $cacheContext,
        ManagerInterface $eventManager,
        MetadataPool $metadataPool = null
    ) {
        $this->resource = $resource;
        $this->stockConfiguration = $stockConfiguration;
        $this->cacheContext = $cacheContext;
        $this->eventManager = $eventManager;
        $this->metadataPool = $metadataPool ?: ObjectManager::getInstance()->get(MetadataPool::class);
    }

    /**
     * Clean cache by product ids.
     *
     * @param array $productIds
     * @param callable $reindex
     * @return void
     */
    public function clean(array $productIds, callable $reindex)
    {
        $productStatusesBefore = $this->getProductStockStatuses($productIds);
        $reindex();
        $productStatusesAfter = $this->getProductStockStatuses($productIds);
        $productIds = $this->getProductIdsForCacheClean($productStatusesBefore, $productStatusesAfter);
        if ($productIds) {
            $this->cacheContext->registerEntities(Product::CACHE_TAG, array_unique($productIds));
            $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $this->cacheContext]);
            # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
            # "Compute exact category membership/position diff in Product Category Indexer Rows::execute(), preserve non-zero category positions in SaveHandler::mergeCategoryLinks(), and restrict Stock CacheCleaner::clean() full-category flush to restocked products":
            # https://github.com/smokeday-com/m/issues/27
            $restockedProductIds = $this->getRestockedProductIds($productStatusesBefore, $productStatusesAfter);
            if ($restockedProductIds) {
                $categoryIds = $this->getCategoryIdsByProductIds($restockedProductIds);
                if ($categoryIds) {
                    $this->cacheContext->registerEntities(Category::CACHE_TAG, array_unique($categoryIds));
                    $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $this->cacheContext]);
                }
            }
        }
    }

    /**
     * Return product IDs that transitioned into In Stock (0 -> 1 or newly added In Stock).
     *
     * Products transitioning 1 -> 0 (Out of Stock) already have their Product::CACHE_TAG (cat_p_<id>)
     * registered, which flushes both the product page and the exact paginated category page(s) where
     * the product was rendered via ListProduct::getIdentities().
     *
     * @param array $productStatusesBefore
     * @param array $productStatusesAfter
     * @return int[]
     */
    private function getRestockedProductIds(array $productStatusesBefore, array $productStatusesAfter): array
    {
        $enabledProductsIds = array_diff(array_keys($productStatusesAfter), array_keys($productStatusesBefore));
        $commonProductsIds = array_intersect(array_keys($productStatusesBefore), array_keys($productStatusesAfter));
        $restockedIds = [];

        foreach ($enabledProductsIds as $productId) {
            $statusAfter = $productStatusesAfter[$productId];
            if ((int)($statusAfter['stock_status'] ?? 0) === 1) {
                $restockedIds[] = (int)$productId;
                if (!empty($statusAfter['parent_id'])) {
                    $restockedIds[] = (int)$statusAfter['parent_id'];
                }
            }
        }

        foreach ($commonProductsIds as $productId) {
            $statusBefore = $productStatusesBefore[$productId];
            $statusAfter = $productStatusesAfter[$productId];
            if ((int)$statusBefore['stock_status'] === 0 && (int)$statusAfter['stock_status'] === 1) {
                $restockedIds[] = (int)$productId;
                if (!empty($statusAfter['parent_id'])) {
                    $restockedIds[] = (int)$statusAfter['parent_id'];
                }
            }
        }

        return array_values(array_unique($restockedIds));
    }

    /**
     * Get current stock statuses for product ids.
     *
     * @param array $productIds
     * @return array
     */
    private function getProductStockStatuses(array $productIds)
    {
        $linkField = $this->metadataPool->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class)
            ->getLinkField();
        $select = $this->getConnection()->select()
            ->from(
                ['css' => $this->resource->getTableName('cataloginventory_stock_status')],
                ['product_id', 'stock_status', 'qty']
            )
            ->joinLeft(
                ['cpr' => $this->resource->getTableName('catalog_product_relation')],
                'css.product_id = cpr.child_id',
                []
            )
            ->joinLeft(
                ['cpe' => $this->resource->getTableName('catalog_product_entity')],
                'cpr.parent_id = cpe.' . $linkField,
                ['parent_id' => 'cpe.entity_id']
            )
            ->where('product_id IN (?)', $productIds, \Zend_Db::INT_TYPE)
            ->where('stock_id = ?', Stock::DEFAULT_STOCK_ID)
            ->where('website_id = ?', $this->stockConfiguration->getDefaultScopeId());

        $statuses = [];
        foreach ($this->getConnection()->fetchAll($select) as $item) {
            $statuses[$item['product_id']] = $item;
        }
        return $statuses;
    }

    /**
     * Return list of product ids that need to be flushed from cache
     *
     * @param array $productStatusesBefore
     * @param array $productStatusesAfter
     * @return array
     */
    private function getProductIdsForCacheClean(array $productStatusesBefore, array $productStatusesAfter)
    {
        $disabledProductsIds = array_diff(array_keys($productStatusesBefore), array_keys($productStatusesAfter));
        $enabledProductsIds = array_diff(array_keys($productStatusesAfter), array_keys($productStatusesBefore));
        $commonProductsIds = array_intersect(array_keys($productStatusesBefore), array_keys($productStatusesAfter));
        $productIds = array_merge($disabledProductsIds, $enabledProductsIds);

        $stockThresholdQty = $this->stockConfiguration->getStockThresholdQty();

        foreach ($commonProductsIds as $productId) {
            $statusBefore = $productStatusesBefore[$productId];
            $statusAfter = $productStatusesAfter[$productId];

            if ($statusBefore['stock_status'] !== $statusAfter['stock_status']
                || ($stockThresholdQty && $statusAfter['qty'] <= $stockThresholdQty)) {
                $productIds[] = $productId;
                if (isset($statusAfter['parent_id'])) {
                    $productIds[] = $statusAfter['parent_id'];
                }
            }
        }

        return array_map('intval', $productIds);
    }

    /**
     * Get category ids for products
     *
     * @param array $productIds
     * @return array
     */
    private function getCategoryIdsByProductIds(array $productIds): array
    {
        $categoryProductTable = $this->resource->getTableName('catalog_category_product');
        $select = $this->getConnection()->select()
            ->from(['catalog_category_product' => $categoryProductTable], ['category_id'])
            ->where('product_id IN (?)', $productIds, \Zend_Db::INT_TYPE);

        return $this->getConnection()->fetchCol($select);
    }

    /**
     * Get database connection.
     *
     * @return AdapterInterface
     */
    private function getConnection()
    {
        if (null === $this->connection) {
            $this->connection = $this->resource->getConnection();
        }

        return $this->connection;
    }
}
