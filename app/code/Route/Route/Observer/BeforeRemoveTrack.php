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
 * @copyright 2020 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */

namespace Route\Route\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Route\Route\Model\Route\Shipment as ShipmentClient;

/**
 * Observer to order place event
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
class BeforeRemoveTrack implements ObserverInterface
{
    /**
     * @var ShipmentClient
     */
    protected $shipmentClient;

    /**
     * BeforeRemoveTrack constructor.
     * @param ShipmentClient $shipmentClient
     */
    public function __construct(
        ShipmentClient $shipmentClient
    ) {
        $this->shipmentClient = $shipmentClient;
    }

    /**
     * Cancel shipment tracking on Route
     *
     * @param Observer $observer
     * @return $this|void
     */
    public function execute(Observer $observer)
    {
        $track = $observer->getEvent()->getTrack();
        $this->shipmentClient->cancelShipment($track);

        return $this;
    }
}
