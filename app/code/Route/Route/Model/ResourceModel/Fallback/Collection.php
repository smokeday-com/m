<?php
/**
 * A Route Magento Extension that adds secure shipping
 * insurance to your orders
 *
 * Php version 7.0^
 *
 * @category
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */

namespace Route\Route\Model\ResourceModel\Fallback;

/**
 *
 * Php version 7.0^
 *
 * @category  Collection
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    const SIZE = 100;

    protected function _construct()
    {
        $this->_init(
            "Route\Route\Model\Route\Operation\Fallback",
            "Route\Route\Model\ResourceModel\Fallback"
        );
    }

    public function getPendingOperations()
    {
        return $this->addFieldToFilter('retry', ['lteq' => \Route\Route\Model\Route\Operation\Fallback::RETRY_LIMIT])
            ->addFieldToFilter('status', ['eq' => \Route\Route\Model\Route\Operation\Fallback::STATUS_PENDING])
            ->setPageSize(self::SIZE);
    }

    public function getCompletedOperations()
    {
        return
            $this->addFieldToFilter('status', [
                'in' => [
                    \Route\Route\Model\Route\Operation\Fallback::STATUS_DONE,
                    \Route\Route\Model\Route\Operation\Fallback::STATUS_EXCEED_LIMIT
                    ]
            ]);
    }
}
