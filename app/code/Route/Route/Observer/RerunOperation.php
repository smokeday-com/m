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

namespace Route\Route\Observer;

use Route\Route\Helper\Data;
use Route\Route\Model\ResourceModel\Fallback;
use Route\Route\Model\ResourceModel\Fallback\CollectionFactory;

/**
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
class RerunOperation
{
    protected $collectionFactory;
    protected $fallbackResource;
    protected $routeHelper;

    public function __construct(
        CollectionFactory $collectionFactory,
        Fallback $fallbackResource,
        Data $routeHelper
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->fallbackResource = $fallbackResource;
        $this->routeHelper = $routeHelper;
    }

    public function retryFallbackOperations()
    {
        if (!$this->routeHelper->isRouteModuleEnable() || $this->routeHelper->isInvalidMerchant()) {
            return $this;

        }

        $operationCollection = $this->collectionFactory->create();

        foreach ($operationCollection->getPendingOperations() as $operation) {
            $operation->retry();
            $this->fallbackResource->save($operation);
        }

        return $this;
    }

    public function clearFallbackOperations()
    {
        $operationCollection = $this->collectionFactory->create()->getCompletedOperations();
        $operationCollection->walk('delete');
    }
}
