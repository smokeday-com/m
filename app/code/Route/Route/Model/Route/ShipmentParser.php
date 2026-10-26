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

namespace Route\Route\Model\Route;

use Magento\Shipping\Model\Order\Track;
use Magento\Catalog\Model\Product;
use Route\Route\Service\Model\V1ShipmentsBody;

/**
 * Shipment Parser to object from Route API client
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
class ShipmentParser
{
    const CUSTOM = 'custom';
    const CARRIER_MAX_LENGTH = 100;
    private $_productCollection;

    public function __construct(
        Product $productCollection
    ) {
        $this->_productCollection = $productCollection;
    }

    /**
     * Parse Magento shipment object to Route format
     *
     * @param Track $track track with shipment data
     *
     * @param bool $includeLineItems include line items or not
     *
     * @return V1ShipmentsBody
     */
    public function getRouteObject($track, $includeLineItems = true)
    {
        $data = [
            'source_order_id' => $track->getShipment()->getOrder()->getIncrementId(),
            'tracking_number' => $track->getNumber(),
            'courier_id' => $this->getCarrierName($track),
            'source_product_ids' => [],
            'line_items' => []
        ];

        if ($includeLineItems) {
            foreach ($track->getShipment()->getAllItems() as $item) {
                $productId = $item->getSku() ? $this->_productCollection->getIdBySku($item->getSku()) : $item->getProductId();
                if (!$productId) {
                    continue;
                }
                $data['source_product_ids'] = array_merge(
                    $data['source_product_ids'],
                    array_fill(0, $item->getQty(), $productId)
                );

                if (!$item->getOrderItemId()) {
                    continue;
                }

                $data['line_items'][] = [
                    'source_id' => strval($item->getOrderItemId()),
                    'quantity' => intval($item->getQty())
                ];
            }
        }


        return new V1ShipmentsBody($data);
    }

    /**
     * @param $track
     * @return mixed
     */
    private function getCarrierName($track)
    {
        $carrier = $track->getCarrierCode() == self::CUSTOM ? $track->getTitle() : $track->getCarrierCode();
        $carrier = trim($carrier ?? '');
        if (strlen($carrier) > self::CARRIER_MAX_LENGTH) {
            $carrier = substr($carrier, 0, self::CARRIER_MAX_LENGTH);
        }
        return $carrier;
    }
}
