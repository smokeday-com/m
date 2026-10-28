<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Visual Merchandiser for Magento 2
 */

namespace Amasty\VisualMerch\Cron;

class CatalogCategoryProductReindex
{
    /**
     * @var \Magento\Catalog\Model\Indexer\Category\Product
     */
    private $indexer;

    public function __construct(
        \Magento\Catalog\Model\Indexer\Category\Product $indexer
    ) {
        $this->indexer = $indexer;
    }

    /**
     * @return void
     */
    public function execute()
    {
        # 2026-10-28 Dmitrii Fediuk https://github.com/dmitrii-fediuk
        # "Replace unconditional global cat_c/cat_p cache flush in Amasty_VisualMerch ApplySorting::afterReindexAll() and CatalogCategoryProductReindex::execute() with differential per-category cat_c_<id> invalidation":
        # https://github.com/smokeday-com/m/issues/32
        \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Amasty\VisualMerch\Plugin\Sorting\Model\Indexer\Summary\ApplySorting::class)
            ->afterReindexAll();
    }
}
