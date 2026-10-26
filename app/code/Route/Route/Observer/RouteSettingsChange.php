<?php
/**
 * A Route Magento Extension that adds secure shipping
 * insurance to your orders
 *
 * Php version 7.0^
 *
 * @category  Model
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */

namespace Route\Route\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Route\Route\Helper\Data;

/**
 * Observer to Route Settings changes
 *
 * Php version 7.0^
 *
 * @category  Observer
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class RouteSettingsChange implements ObserverInterface
{
    protected $helper;

    /**
     * RouteSettingsChange constructor.
     *
     * @param Data        $helper      Route Helper
     */
    public function __construct(
        Data $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * Main method executed reset merchant invalid flag
     *
     * @param Observer $observer observer object
     *
     * @return ObserverInterface
     */
    public function execute(Observer $observer)
    {
        $this->helper->removeMerchantInvalidFlag();

        return $this;
    }
}
