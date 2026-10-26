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

use Route\Route\Logger\Sentry;
use Route\Route\Model\Route\Operation\Fallback;
use Route\Route\Model\Route\Operation\FallbackFactory;
use Route\Route\Service\Api\ShipmentsApi;
use Route\Route\Service\ApiClientBuilder;

/**
 * Shipment Route API client
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
class Shipment extends Fallbackable
{
    const API_ENDPOINT = 'shipments';
    protected ShipmentParser $shipmentParser;

    private ShipmentsApi $apiInstance;

    /**
     * Shipment constructor.
     *
     * @param Sentry $logger logger
     * @param ShipmentParser $shipmentParser Ship parser to api format
     * @param FallbackFactory $fallback
     * @param ApiClientBuilder $apiClientBuilder
     */
    public function __construct(
        Sentry          $logger,
        ShipmentParser           $shipmentParser,
        FallbackFactory          $fallback,
        ApiClientBuilder         $apiClientBuilder
    ) {
        $this->shipmentParser = $shipmentParser;
        $this->apiInstance = $apiClientBuilder->buildShipmentApiClient();
        parent::__construct($logger, $fallback);
    }

    /**
     * Submit shipment creation to Route API
     *
     * @param \Magento\Sales\Model\Order\Shipment $shipment shipment object
     *
     * @return void
     */
    public function postShipment($shipment)
    {
        foreach ($shipment->getAllTracks() as $track) {
            $this->postTracking($track);
        }
    }

    /**
     * Cancel shipment tracking on Route API
     *
     * @param $track
     * @return bool
     */
    public function cancelShipment($track)
    {
        if ($this->shipmentExists($track)) {
            $shipment = $track->getShipment();
            try {
                $this->apiInstance->cancelShipment($track->getNumber(), null, $shipment->getOrder()->getIncrementId());
            } catch (\Exception $exception) {
                $this->enqueueOperation($shipment, 'cancelShipment');
                $this->logger->error(
                    'Error canceling shipment: ' . $exception->getMessage(),
                    $exception->getTrace(),
                    [
                        'params' => $shipment->getOrder()->getIncrementId(),
                        'method' => 'POST',
                        'endpoint' => "v1/shipments/{$track->getNumber()}/cancel",
                    ]
                );
            }

        }

        return true;
    }

    /**
     * Submit shipment tracking creation to Route API
     *
     * @param $track
     */
    public function postTracking($track)
    {
        $shipment = $track->getShipment();

        if (is_null($shipment) || $this->shipmentExists($track)) {
            return false;
        }

        try {
            $body = $this->shipmentParser->getRouteObject($track);
            $shipmentResponse = $this->apiInstance->createShipmentAsync($body)->wait();
        } catch (\Exception $exception) {
            if ($exception->getCode() != 400) {
                $this->enqueueOperation($shipment, 'postTracking');
                $this->logger->error(
                    'Error trying to create shipment: ' . $exception->getMessage(),
                    $exception->getTrace(),
                    [
                        'params' => sprintf("%s", $body),
                        'method' => 'POST',
                        'endpoint' => 'v1/shipments',
                    ]
                );
                return false;
            }
            try {
                $body = $this->shipmentParser->getRouteObject($track, false);
                $shipmentResponse = $this->apiInstance->createShipment($body);
            } catch (\Exception $exception) {
                $this->enqueueOperation($shipment, 'postTracking');
                $this->logger->error(
                    'Error trying to create shipment: ' . $exception->getMessage(),
                    $exception->getTrace(),
                    [
                        'params' => sprintf("%s", $body),
                        'method' => 'POST',
                        'endpoint' => 'v1/shipments',
                    ]
                );
                return false;
            }
        }

        if (!$shipmentResponse->getId()) {
            return false;
        }

        try {
            $track
                ->setRouteShipmentId($shipmentResponse->getId())
                ->setAvoidResend(true)
                ->save();
        } catch (\Exception $exception) {
            $this->logger->error(
                $exception->getMessage(),
                $exception->getTrace()
            );
        }
    }

    /**
     * Check if shipment track exists on Route
     *
     * @param $track
     * @return mixed
     */
    public function shipmentExists($track)
    {
        if ($track->getRouteShipmentId()) {
            return true;
        }

        //CHECK IF TRACKING ORDER IS OLDER THAN MERCHANT CREATION DATE
        $shipment = $track->getShipment();
        $order = $shipment->getOrder();
        $orderRouteId = $order->getRouteOrderId();

        if ($orderRouteId === Order::DEFAULT_ROUTE_ORDER_ID) {
            $track->setRouteShipmentId(Order::DEFAULT_ROUTE_ORDER_ID)
                ->setAvoidResend(true)
                ->save();
            return true;
        }

        $shipmentResponse = $this->getShipment($track->getNumber(), $order->getIncrementId());

        if (!$shipmentResponse || !$shipmentResponse->getId()) {
            return false;
        }

        try {
            $track
                ->setRouteShipmentId($shipmentResponse->getId())
                ->setAvoidResend(true)
                ->save();
        } catch (\Exception $exception) {
            $this->logger->error(
                $exception->getMessage(),
                $exception->getTrace()
            );
            return false;
        }

        return true;
    }

    /**
     * Get shipment id from Route if exist
     *
     * @param $trackNumber
     * @return bool|string
     */
    public function getShipment($trackNumber, $orderId)
    {
        try {
            return $this->apiInstance->getShipment($trackNumber, $orderId);
        } catch (\Exception $exception) {
            return false;
        }
    }

    public function enqueueOperation($object, $operation)
    {
        try {
            if (!$this->isRetrying()) {
                $fallback = $this->fallback->create();
                $fallback->setEntityId($object->getId())
                    ->setType(self::API_ENDPOINT)
                    ->setOperation($operation)
                    ->setStatus(Fallback::STATUS_PENDING)
                    ->save();
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), $e->getTrace());
        }
    }
}
