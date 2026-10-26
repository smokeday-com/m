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

namespace Route\Route\Model\Route\Operation;

use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Phrase;
use Magento\Sales\Model\Order;
use Route\Route\Model\Route\Fallbackable;
use Route\Route\Model\Route\Shipment as ShipmentClient;

/**
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
class FallbackShipment implements OperationFallback
{
    protected $shipmentClient;
    protected $shipment;
    protected $fallback;

    public function __construct(
        Order\Shipment $shipment,
        ShipmentClient $shipmentClient
    ) {
        $this->shipmentClient = $shipmentClient;
        $this->shipment = $shipment;
    }

    public function getEntity()
    {
        $entity = $this->shipment->load($this->fallback->getEntityId());
        if ($entity->getId() > 0) {
            return $entity;
        }
        throw new NotFoundException(new Phrase('Entity not Found'));
    }

    public function getOperationObject(): Fallbackable
    {
        return $this->shipmentClient;
    }

    public function getParams()
    {
        return [$this->getEntity()];
    }

    public function setFallback(Fallback $fallback): OperationFallback
    {
        $this->fallback = $fallback;
        return $this;
    }
}
