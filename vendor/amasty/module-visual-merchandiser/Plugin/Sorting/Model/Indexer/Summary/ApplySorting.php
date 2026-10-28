<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Visual Merchandiser for Magento 2
 */

namespace Amasty\VisualMerch\Plugin\Sorting\Model\Indexer\Summary;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Indexer\Category\Product as CategoryProductIndexer;
use Magento\Catalog\Model\Indexer\Category\Product\Action\FullFactory;
use Magento\Catalog\Model\Indexer\Product\Category\Processor as ProductCategoryProcessor;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Indexer\CacheContext;
use Magento\Framework\Indexer\IndexMutexInterface;
use Magento\Store\Model\StoreManagerInterface;

class ApplySorting
{
    /**
     * @var ProductCategoryProcessor
     */
    private ProductCategoryProcessor $productCategoryProcessor;

    # 2026-10-28 Dmitrii Fediuk https://github.com/dmitrii-fediuk
    # "Replace unconditional global cat_c/cat_p cache flush in Amasty_VisualMerch ApplySorting::afterReindexAll() and CatalogCategoryProductReindex::execute() with differential per-category cat_c_<id> invalidation":
    # https://github.com/smokeday-com/m/issues/32
    private FullFactory $fullActionFactory;
    private IndexMutexInterface $indexMutex;
    private ResourceConnection $resource;
    private StoreManagerInterface $storeManager;
    private CacheContext $cacheContext;
    private ManagerInterface $eventManager;

    public function __construct(
        ProductCategoryProcessor $productCategoryProcessor,
        ?FullFactory $fullActionFactory = null,
        ?IndexMutexInterface $indexMutex = null,
        ?ResourceConnection $resource = null,
        ?StoreManagerInterface $storeManager = null,
        ?CacheContext $cacheContext = null,
        ?ManagerInterface $eventManager = null
    ) {
        $this->productCategoryProcessor = $productCategoryProcessor;
        $om = ObjectManager::getInstance();
        $this->fullActionFactory = $fullActionFactory ?? $om->get(FullFactory::class);
        $this->indexMutex = $indexMutex ?? $om->get(IndexMutexInterface::class);
        $this->resource = $resource ?? $om->get(ResourceConnection::class);
        $this->storeManager = $storeManager ?? $om->get(StoreManagerInterface::class);
        $this->cacheContext = $cacheContext ?? $om->get(CacheContext::class);
        $this->eventManager = $eventManager ?? $om->get(ManagerInterface::class);
    }

    /**
     * @see \Amasty\Sorting\Model\Indexer\Summary::reindexAll
     */
    public function afterReindexAll(): void
    {
        # 2026-10-28 Dmitrii Fediuk https://github.com/dmitrii-fediuk
        # "Replace unconditional global cat_c/cat_p cache flush in Amasty_VisualMerch ApplySorting::afterReindexAll() and CatalogCategoryProductReindex::execute() with differential per-category cat_c_<id> invalidation":
        # https://github.com/smokeday-com/m/issues/32
        $beforeDigests = $this->captureCategoryIndexDigests();
        $this->indexMutex->execute(
            CategoryProductIndexer::INDEXER_ID,
            function (): void {
                $this->fullActionFactory->create()->execute();
            }
        );
        $afterDigests = $this->captureCategoryIndexDigests();
        $changedCategoryIds = $this->computeChangedCategoryIds($beforeDigests, $afterDigests);
        if (!empty($changedCategoryIds)) {
            $this->cacheContext->registerEntities(Category::CACHE_TAG, $changedCategoryIds);
            $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $this->cacheContext]);
            $this->cacheContext->flush();
        }
    }

    /**
     * Capture per-category SHA-1 digests of (store_id, product_id, position, is_parent, visibility)
     * across all active store category-product index tables.
     *
     * @return array<int, string>
     */
    private function captureCategoryIndexDigests(): array
    {
        $conn = $this->resource->getConnection();
        $tuplesByCategory = [];
        foreach ($this->storeManager->getStores() as $store) {
            $storeId = (int)$store->getId();
            if ($storeId <= 0) {
                continue;
            }
            $tableName = $this->resource->getTableName('catalog_category_product_index_store' . $storeId);
            if (!$conn->isTableExists($tableName)) {
                continue;
            }
            $select = $conn->select()
                ->from($tableName, ['category_id', 'product_id', 'position', 'is_parent', 'visibility'])
                ->where('category_id > 2')
                ->order(['category_id ASC', 'product_id ASC']);
            foreach ($conn->fetchAll($select) as $row) {
                $cid = (int)$row['category_id'];
                $tuplesByCategory[$cid][] = $storeId . ':'
                    . (int)$row['product_id'] . ':'
                    . (int)$row['position'] . ':'
                    . (int)$row['is_parent'] . ':'
                    . (int)$row['visibility'];
            }
        }
        $digests = [];
        foreach ($tuplesByCategory as $cid => $tuples) {
            $digests[$cid] = sha1(implode(',', $tuples));
        }
        return $digests;
    }

    /**
     * Return category IDs (> 2) whose product membership or sort positions changed.
     *
     * @param array<int, string> $before
     * @param array<int, string> $after
     * @return int[]
     */
    private function computeChangedCategoryIds(array $before, array $after): array
    {
        $changed = [];
        $allCategoryIds = array_unique(array_merge(array_keys($before), array_keys($after)));
        foreach ($allCategoryIds as $cid) {
            if (($before[$cid] ?? null) !== ($after[$cid] ?? null)) {
                $changed[] = (int)$cid;
            }
        }
        sort($changed);
        return $changed;
    }
}
