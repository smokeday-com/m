<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Automatic Related Products for Magento 2
 */

namespace Amasty\Mostviewed\Model\Indexer;

use Amasty\Mostviewed\Model\ResourceModel\RuleIndex;
use Amasty\Mostviewed\Model\Group as Rule;
use Magento\Catalog\Model\Product as ProductModel;

class ProductIndexer extends AbstractIndexer
{
    /**
     * @inheritdoc
     */
    protected function doReindex($ids = [])
    {
        $rows = [];
        $count = 0;
        $matchedProductsCounterByRuleId = [];
        # 2026-10-28 Dmitrii Fediuk https://github.com/dmitrii-fediuk
        # "Make Amasty\Mostviewed\Model\Indexer\ProductIndexer::doReindex() cache invalidation differential so unchanged product-rule associations do not flush cat_p_<id>":
        # https://github.com/smokeday-com/m/issues/36
        $normalizedIds = array_values(array_unique(array_filter(array_map('intval', (array)$ids))));
        $beforeProductSnapshot = !empty($normalizedIds) ? $this->loadProductIndexSnapshot($normalizedIds) : [];
        $rules = $this->getRules()->getItems();
        foreach ($rules as $rule) {
            $ruleId = (int)$rule->getGroupId();
            $matchedProductsCounterByRuleId[$ruleId] = $this->loadCurrentProductCount($ruleId, RuleIndex::WHAT_SHOW);
        }

        foreach ([RuleIndex::WHERE_SHOW, RuleIndex::WHAT_SHOW] as $relation) {
            $this->clean($relation, $ids);

            /** @var Rule $rule */
            foreach ($rules as $rule) {
                $ruleId = (int)$rule->getGroupId();

                $rule->setRelation($relation);
                $position = $rule->getBlockPosition();
                $rule->setProductsFilter($ids);
                $matchedProducts = $rule->getMatchingProductIdsByGroup() ?: [];
                if (isset($matchedProducts['categories'])) {
                    continue;
                }
                foreach ($matchedProducts as $productId => $storeIds) {
                    while ($storeIds) {
                        $rows[] = [
                            RuleIndex::ENTITY_ID => $productId,
                            RuleIndex::RELATION  => $relation,
                            RuleIndex::STORE_ID  => array_shift($storeIds),
                            RuleIndex::RULE_ID   => $ruleId,
                            RuleIndex::POSITION  => $position
                        ];
                        if (++$count > 1000) {
                            $this->getIndexResource()->insertIndexData($rows);
                            $count = 0;
                            $rows = [];
                        }
                    }
                }
            }
        }

        if (!empty($rows)) {
            $this->getIndexResource()->insertIndexData($rows);
        }

        $changedRuleIds = [];
        foreach ($matchedProductsCounterByRuleId as $ruleId => $previousProductCount) {
            if ($previousProductCount !== $this->loadCurrentProductCount($ruleId, RuleIndex::WHAT_SHOW)) {
                $changedRuleIds[] = (int)$ruleId;
            }
        }

        $changedProductIds = [];
        if (!empty($normalizedIds)) {
            $afterProductSnapshot = $this->loadProductIndexSnapshot($normalizedIds);
            foreach ($normalizedIds as $pid) {
                if (($beforeProductSnapshot[$pid] ?? '') !== ($afterProductSnapshot[$pid] ?? '')) {
                    $changedProductIds[] = $pid;
                }
            }
        }

        if (!empty($changedRuleIds)) {
            $this->registerEntities(Rule::CACHE_TAG, $changedRuleIds);
        }
        if (!empty($changedProductIds)) {
            $this->registerEntities(ProductModel::CACHE_TAG, $changedProductIds);
        }
        if (!empty($changedRuleIds) || !empty($changedProductIds)) {
            $this->cleanCache();
        }
    }

    /**
     * Load canonical per-product digest of (relation, store_id, rule_id, position) rows in amasty_mostviewed_product_index.
     *
     * @param int[] $productIds
     * @return array<int, string>
     */
    private function loadProductIndexSnapshot(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }
        $conn = $this->getIndexResource()->getConnection();
        $select = $conn->select()
            ->from(
                $this->getIndexResource()->getMainTable(),
                [RuleIndex::ENTITY_ID, RuleIndex::RELATION, RuleIndex::STORE_ID, RuleIndex::RULE_ID, RuleIndex::POSITION]
            )
            ->where(RuleIndex::ENTITY_ID . ' IN (?)', $productIds)
            ->order([
                RuleIndex::ENTITY_ID . ' ASC',
                RuleIndex::RELATION . ' ASC',
                RuleIndex::STORE_ID . ' ASC',
                RuleIndex::RULE_ID . ' ASC',
                RuleIndex::POSITION . ' ASC',
            ]);
        $grouped = [];
        foreach ($conn->fetchAll($select) as $row) {
            $pid = (int)$row[RuleIndex::ENTITY_ID];
            $grouped[$pid][] = $row[RuleIndex::RELATION] . ':'
                . (int)$row[RuleIndex::STORE_ID] . ':'
                . (int)$row[RuleIndex::RULE_ID] . ':'
                . (string)$row[RuleIndex::POSITION];
        }
        $digests = [];
        foreach ($grouped as $pid => $tuples) {
            $digests[$pid] = sha1(implode(',', $tuples));
        }
        return $digests;
    }

    /**
     * @inheritdoc
     */
    protected function cleanList($relation, $ids)
    {
        $this->getIndexResource()->cleanByProductIds($ids, $relation);
    }
}
