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

namespace Route\Route\Model\ResourceModel;

/**
 *
 * Php version 7.0^
 *
 * @category  Resource_Model
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Fallback extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('route_operation_fallback', 'operation_id');
    }

    public function isUnique(\Route\Route\Model\Route\Operation\Fallback $fallback)
    {
        $select = $this->getConnection()->select()->from($this->getMainTable());

        $select->where('entity_id = ?', $fallback->getEntityId())
            ->where('type = ?', $fallback->getType())
            ->where('operation = ?', $fallback->getOperation());

        $test = $this->getConnection()->fetchRow($select);

        return  empty($test);
    }
}
