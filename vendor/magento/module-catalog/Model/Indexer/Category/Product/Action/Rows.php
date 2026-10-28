<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Catalog\Model\Indexer\Category\Product\Action;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Indexer\CacheContext;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\DB\Query\Generator as QueryGenerator;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Catalog\Model\Config;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Indexer\Product\Category as ProductCategoryIndexer;
use Magento\Catalog\Model\Indexer\Category\Product as CategoryProductIndexer;
use Magento\Catalog\Model\Indexer\Category\Product\TableMaintainer;
use Magento\Indexer\Model\WorkingStateProvider;

/**
 * Reindex multiple rows action.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Rows extends \Magento\Catalog\Model\Indexer\Category\Product\AbstractAction
{
    /**
     * Limitation by categories
     *
     * @var int[]
     */
    protected $limitationByCategories;

    # 2026-10-28 Dmitrii Fediuk https://github.com/dmitrii-fediuk
    # "Eliminate all-category FPC eviction fan-out in Magento_Elasticsearch IndexerHandler::updateCacheContext() and Category\Product\Action\Rows":
    # https://github.com/smokeday-com/m/issues/41
    /**
     * Product IDs whose category index rows actually changed during the last execute() run.
     *
     * @var int[]
     */
    private array $affectedProductIds = [];

    /**
     * @var CacheContext
     */
    private $cacheContext;

    /**
     * @var EventManagerInterface|null
     */
    private $eventManager;

    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @var WorkingStateProvider
     */
    private $workingStateProvider;

    /**
     * @param ResourceConnection $resource
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param QueryGenerator|null $queryGenerator
     * @param MetadataPool|null $metadataPool
     * @param TableMaintainer|null $tableMaintainer
     * @param CacheContext|null $cacheContext
     * @param EventManagerInterface|null $eventManager
     * @param IndexerRegistry|null $indexerRegistry
     * @param WorkingStateProvider|null $workingStateProvider
     * @SuppressWarnings(PHPMD.ExcessiveParameterList) Preserve compatibility with the parent class
     */
    public function __construct(
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        Config $config,
        QueryGenerator $queryGenerator = null,
        MetadataPool $metadataPool = null,
        ?TableMaintainer $tableMaintainer = null,
        CacheContext $cacheContext = null,
        EventManagerInterface $eventManager = null,
        IndexerRegistry $indexerRegistry = null,
        ?WorkingStateProvider $workingStateProvider = null
    ) {
        parent::__construct($resource, $storeManager, $config, $queryGenerator, $metadataPool, $tableMaintainer);
        $this->cacheContext = $cacheContext ?: ObjectManager::getInstance()->get(CacheContext::class);
        $this->eventManager = $eventManager ?: ObjectManager::getInstance()->get(EventManagerInterface::class);
        $this->indexerRegistry = $indexerRegistry ?: ObjectManager::getInstance()->get(IndexerRegistry::class);
        $this->workingStateProvider = $workingStateProvider ?:
            ObjectManager::getInstance()->get(WorkingStateProvider::class);
    }

    /**
     * Refresh entities index
     *
     * @param int[] $entityIds
     * @param bool $useTempTable
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute(array $entityIds = [], $useTempTable = false)
    {
        # 2026-10-28 Dmitrii Fediuk https://github.com/dmitrii-fediuk
        # "Eliminate all-category FPC eviction fan-out in Magento_Elasticsearch IndexerHandler::updateCacheContext() and Category\Product\Action\Rows":
        # https://github.com/smokeday-com/m/issues/41
        $this->limitationByCategories = [];
        $this->affectedProductIds = [];
        foreach ($entityIds as $entityId) {
            $this->limitationByCategories[] = (int)$entityId;
            $path = $this->getPathFromCategoryId($entityId);
            if (!empty($path)) {
                $pathIds = explode('/', $path);
                foreach ($pathIds as $pathId) {
                    $this->limitationByCategories[] = (int)$pathId;
                }
            }
        }
        $this->limitationByCategories = array_unique($this->limitationByCategories);
        $this->useTempTable = $useTempTable;
        $indexer = $this->indexerRegistry->get(ProductCategoryIndexer::INDEXER_ID);
        $workingState = $this->isWorkingState();

        if (!$indexer->isScheduled()
            || ($indexer->isScheduled() && !$useTempTable)
            || ($indexer->isScheduled() && $useTempTable && !$workingState)) {
            $indexSnapshotBefore = $this->getIndexSnapshotForCategories($this->limitationByCategories);
            if ($useTempTable && !$workingState && $indexer->isScheduled()) {
                foreach ($this->storeManager->getStores() as $store) {
                    $this->connection->truncateTable($this->getIndexTable($store->getId()));
                }
            } else {
                $this->removeEntries();
            }

            $this->reindex();

            // get actual state
            $workingState = $this->isWorkingState();

            if ($useTempTable && !$workingState && $indexer->isScheduled()) {
                foreach ($this->storeManager->getStores() as $store) {
                    $removalCategoryIds = array_diff($this->limitationByCategories, [$this->getRootCategoryId($store)]);
                    $this->connection->delete(
                        $this->tableMaintainer->getMainTable($store->getId()),
                        ['category_id IN (?)' => $removalCategoryIds]
                    );
                    $select = $this->connection->select()
                        ->from($this->tableMaintainer->getMainReplicaTable($store->getId()));
                    $this->connection->query(
                        $this->connection->insertFromSelect(
                            $select,
                            $this->tableMaintainer->getMainTable($store->getId()),
                            [],
                            AdapterInterface::INSERT_ON_DUPLICATE
                        )
                    );
                }
            }

            $indexSnapshotAfter = $this->getIndexSnapshotForCategories($this->limitationByCategories);
            $affectedCategories = $this->getDiffCategoryIds($indexSnapshotBefore, $indexSnapshotAfter);
            $this->affectedProductIds = $this->getDiffProductIds($indexSnapshotBefore, $indexSnapshotAfter);

            if (!empty($affectedCategories)) {
                $this->registerCategories($affectedCategories);
                $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $this->cacheContext]);
            }
        }

        return $this;
    }

    # 2026-10-28 Dmitrii Fediuk https://github.com/dmitrii-fediuk
    # "Eliminate all-category FPC eviction fan-out in Magento_Elasticsearch IndexerHandler::updateCacheContext() and Category\Product\Action\Rows":
    # https://github.com/smokeday-com/m/issues/41
    /**
     * Return product IDs whose category index rows actually changed during the last execute() run.
     *
     * @return int[]
     */
    public function getAffectedProductIds(): array
    {
        return $this->affectedProductIds;
    }

    /**
     * Snapshot main category-product index rows across all stores for given category IDs.
     *
     * @param int[] $categoryIds
     * @return array<int, array<int, array<int, string>>>
     */
    private function getIndexSnapshotForCategories(array $categoryIds): array
    {
        if (empty($categoryIds)) {
            return [];
        }
        $snapshot = [];
        foreach ($this->storeManager->getStores() as $store) {
            $storeId = (int)$store->getId();
            $mainTable = $this->tableMaintainer->getMainTable($storeId);
            $rows = $this->connection->fetchAll(
                $this->connection->select()
                    ->from($mainTable, ['category_id', 'product_id', 'position', 'is_parent', 'visibility'])
                    ->where('category_id IN (?)', $categoryIds, \Zend_Db::INT_TYPE)
            );
            foreach ($rows as $row) {
                $catId = (int)$row['category_id'];
                if ($catId <= 2) {
                    continue;
                }
                $prodId = (int)$row['product_id'];
                $snapshot[$storeId][$catId][$prodId] = sprintf(
                    '%d:%d:%d',
                    (int)$row['position'],
                    (int)$row['is_parent'],
                    (int)$row['visibility']
                );
            }
        }
        return $snapshot;
    }

    /**
     * Return category IDs where at least one product's membership, position, or visibility changed.
     *
     * @param array<int, array<int, array<int, string>>> $before
     * @param array<int, array<int, array<int, string>>> $after
     * @return int[]
     */
    private function getDiffCategoryIds(array $before, array $after): array
    {
        $changedCategoryIds = [];
        $storeIds = array_unique(array_merge(array_keys($before), array_keys($after)));
        foreach ($storeIds as $storeId) {
            $beforeCats = $before[$storeId] ?? [];
            $afterCats = $after[$storeId] ?? [];
            $catIds = array_unique(array_merge(array_keys($beforeCats), array_keys($afterCats)));
            foreach ($catIds as $catId) {
                if (($beforeCats[$catId] ?? []) !== ($afterCats[$catId] ?? [])) {
                    $changedCategoryIds[(int)$catId] = (int)$catId;
                }
            }
        }
        return array_values($changedCategoryIds);
    }

    /**
     * Return product IDs whose membership, position, is_parent, or visibility changed in any category.
     *
     * @param array<int, array<int, array<int, string>>> $before
     * @param array<int, array<int, array<int, string>>> $after
     * @return int[]
     */
    private function getDiffProductIds(array $before, array $after): array
    {
        $changedProductIds = [];
        $storeIds = array_unique(array_merge(array_keys($before), array_keys($after)));
        foreach ($storeIds as $storeId) {
            $beforeCats = $before[$storeId] ?? [];
            $afterCats = $after[$storeId] ?? [];
            $catIds = array_unique(array_merge(array_keys($beforeCats), array_keys($afterCats)));
            foreach ($catIds as $catId) {
                $beforeProds = $beforeCats[$catId] ?? [];
                $afterProds = $afterCats[$catId] ?? [];
                if ($beforeProds === $afterProds) {
                    continue;
                }
                $prodIds = array_unique(array_merge(array_keys($beforeProds), array_keys($afterProds)));
                foreach ($prodIds as $prodId) {
                    if (($beforeProds[$prodId] ?? null) !== ($afterProds[$prodId] ?? null)) {
                        $changedProductIds[(int)$prodId] = (int)$prodId;
                    }
                }
            }
        }
        return array_values($changedProductIds);
    }

    /**
     * Get state for current and shared indexer
     *
     * @return bool
     */
    private function isWorkingState() : bool
    {
        $indexer = $this->indexerRegistry->get(ProductCategoryIndexer::INDEXER_ID);
        $sharedIndexer = $this->indexerRegistry->get(CategoryProductIndexer::INDEXER_ID);
        return $this->workingStateProvider->isWorking($indexer->getId())
            || $this->workingStateProvider->isWorking($sharedIndexer->getId());
    }

    /**
     * Register categories assigned to products
     *
     * @param array $categoryIds
     * @return void
     */
    private function registerCategories(array $categoryIds)
    {
        if ($categoryIds) {
            $this->cacheContext->registerEntities(Category::CACHE_TAG, $categoryIds);
        }
    }

    /**
     * Return array of all category root IDs + tree root ID
     *
     * @param \Magento\Store\Model\Store $store
     * @return int
     */
    private function getRootCategoryId($store)
    {
        $rootId = \Magento\Catalog\Model\Category::TREE_ROOT_ID;
        if ($this->getPathFromCategoryId($store->getRootCategoryId())) {
            $rootId = $store->getRootCategoryId();
        }
        return $rootId;
    }

    /**
     * Remove index entries before reindexation
     *
     * @return void
     */
    private function removeEntries()
    {
        foreach ($this->storeManager->getStores() as $store) {
            $removalCategoryIds = array_diff($this->limitationByCategories, [$this->getRootCategoryId($store)]);
            $this->connection->delete(
                $this->getIndexTable($store->getId()),
                ['category_id IN (?)' => $removalCategoryIds]
            );
        }
    }

    /**
     * Retrieve select for reindex products of non anchor categories
     *
     * @param \Magento\Store\Model\Store $store
     * @return \Magento\Framework\DB\Select
     */
    protected function getNonAnchorCategoriesSelect(\Magento\Store\Model\Store $store)
    {
        $select = parent::getNonAnchorCategoriesSelect($store);
        return $select->where('cc.entity_id IN (?)', $this->limitationByCategories);
    }

    /**
     * Retrieve select for reindex products of non anchor categories
     *
     * @param \Magento\Store\Model\Store $store
     * @return \Magento\Framework\DB\Select
     */
    protected function getAnchorCategoriesSelect(\Magento\Store\Model\Store $store)
    {
        $select = parent::getAnchorCategoriesSelect($store);
        return $select->where('cc.entity_id IN (?)', $this->limitationByCategories);
    }

    /**
     * Check whether select ranging is needed
     *
     * @return bool
     */
    protected function isRangingNeeded()
    {
        return false;
    }

    /**
     * Check whether indexation of root category is needed
     *
     * @return bool
     */
    protected function isIndexRootCategoryNeeded()
    {
        return false;
    }
}
