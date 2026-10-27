<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Catalog\Model\Category\Link;

use Magento\Catalog\Api\Data\CategoryLinkInterface;
use Magento\Framework\EntityManager\Operation\ExtensionInterface;

/**
 * Save handler for catalog product link.
 */
class SaveHandler implements ExtensionInterface
{

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CategoryLink
     */
    private $productCategoryLink;

    /**
     * @var \Magento\Framework\EntityManager\HydratorPool
     */
    private $hydratorPool;

    /**
     * SaveHandler constructor.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\CategoryLink $productCategoryLink
     * @param \Magento\Framework\EntityManager\HydratorPool $hydratorPool
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\CategoryLink $productCategoryLink,
        \Magento\Framework\EntityManager\HydratorPool $hydratorPool
    ) {
        $this->productCategoryLink = $productCategoryLink;
        $this->hydratorPool = $hydratorPool;
    }

    /**
     * Execute
     *
     * @param object $entity
     * @param array $arguments
     * @return object
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute($entity, $arguments = [])
    {
        $entity->setIsChangedCategories(false);

        $extensionAttributes = $entity->getExtensionAttributes();
        if ($extensionAttributes === null && !$entity->hasCategoryIds()) {
            return $entity;
        }

        $modelCategoryLinks = $this->getCategoryLinksPositions($entity);

        $dtoCategoryLinks = $extensionAttributes->getCategoryLinks();
        if ($dtoCategoryLinks !== null) {
            $hydrator = $this->hydratorPool->getHydrator(CategoryLinkInterface::class);
            $dtoCategoryLinks = array_map(function ($categoryLink) use ($hydrator) {
                return $hydrator->extract($categoryLink) ;
            }, $dtoCategoryLinks);
            $processLinks = $this->mergeCategoryLinks($dtoCategoryLinks, $modelCategoryLinks);
        } else {
            $processLinks = $modelCategoryLinks;
        }

        $affectedCategoryIds = $this->productCategoryLink->saveCategoryLinks($entity, $processLinks);

        if (!empty($affectedCategoryIds)) {
            $entity->setAffectedCategoryIds($affectedCategoryIds);
            $entity->setIsChangedCategories(true);
        }

        return $entity;
    }

    /**
     * Get category links positions
     *
     * @param object $entity
     * @return array
     */
    private function getCategoryLinksPositions($entity)
    {
        $result = [];
        $currentCategoryLinks = $this->productCategoryLink->getCategoryLinks($entity, $entity->getCategoryIds());
        foreach ($entity->getCategoryIds() as $categoryId) {
            $key = array_search($categoryId, array_column($currentCategoryLinks, 'category_id'));
            if ($key === false) {
                $result[] = ['category_id' => (int)$categoryId, 'position' => 0];
            } else {
                $result[] = $currentCategoryLinks[$key];
            }
        }

        return $result;
    }

    /**
     * Merge category links
     *
     * @param array $newCategoryPositions
     * @param array $oldCategoryPositions
     * @return array
     */
    private function mergeCategoryLinks($newCategoryPositions, $oldCategoryPositions)
    {
        if (empty($newCategoryPositions)) {
            return [];
        }

        $categoryPositions = array_combine(array_column($oldCategoryPositions, 'category_id'), $oldCategoryPositions);
        foreach ($newCategoryPositions as $newCategoryPosition) {
            $categoryId = $newCategoryPosition['category_id'];
            # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
            # "Compute exact category membership/position diff in Product Category Indexer Rows::execute(), preserve non-zero category positions in SaveHandler::mergeCategoryLinks(), and restrict Stock CacheCleaner::clean() full-category flush to restocked products":
            # https://github.com/smokeday-com/m/issues/27
            if (!isset($categoryPositions[$categoryId])) {
                $categoryPositions[$categoryId] = [
                    'category_id' => $categoryId,
                    'position' => $newCategoryPosition['position'],
                ];
                continue;
            }
            if ((int)$newCategoryPosition['position'] !== 0 || !isset($categoryPositions[$categoryId]['position'])) {
                $categoryPositions[$categoryId]['position'] = $newCategoryPosition['position'];
            }
        }
        $result = array_values($categoryPositions);

        return $result;
    }
}
