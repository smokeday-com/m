<?php

namespace Olegnax\Athlete2\Plugin\Product;

use Magento\Catalog\Model\ResourceModel\Product\Gallery as ProductGallery;
use Magento\Framework\DB\Select;

class Gallery
{
    /**
     * Add 'thumbcarousel' column to product gallery select.
     * Make sure that thumbcarousel persist in product gallery data for admin panel
     *
     * @param ProductGallery $subject
     * @param Select $select
     * @return Select
     */
    public function afterCreateBatchBaseSelect(ProductGallery $subject, Select $select)
    {
        $select->columns('thumbcarousel');

        return $select;
    }
}