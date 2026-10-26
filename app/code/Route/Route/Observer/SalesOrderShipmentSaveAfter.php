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
use Route\Route\Model\Route\Shipment;
use Route\Route\Model\Route\Order;

/**
 * Observer to shipment change event
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
class SalesOrderShipmentSaveAfter implements ObserverInterface
{
    protected $shipmentClient;
    protected $orderClient;

    /**
     * SalesOrderShipmentSaveAfter constructor
     *
     * @param Shipment $shipmentClient Shipment Route Client
     * @param Order $orderClient Order Route Client
     */
    public function __construct(
        Shipment $shipmentClient,
        Order $orderClient
    ) {
        $this->shipmentClient = $shipmentClient;
        $this->orderClient = $orderClient;
    }

    /**
     * Main method executed on shipment change event is triggered
     *
     * @param Observer $observer observer object
     *
     * @return ObserverInterface
     */
    public function execute(Observer $observer)
    {
        $shipment = $observer->getEvent()->getData('shipment');
        $order = $shipment->getOrder();
        if ($this->orderClient->hasRouteOrderId($order)) {
            $this->shipmentClient->postShipment(
                $shipment
            );
        }
        return $this;
    }
}
