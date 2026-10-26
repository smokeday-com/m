<?php /** @noinspection PhpUndefinedMethodInspection */

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

use Exception;
use Magento\Sales\Api\Data\OrderInterface;
use Route\Route\Logger\Sentry;
use Route\Route\Helper\Data;
use Route\Route\Model\Route\Operation\Fallback;
use Route\Route\Model\Route\Operation\FallbackFactory;
use Magento\Sales\Model\ResourceModel\Order as orderResource;
use Route\Route\Service\Api\OrdersApi;
use Route\Route\Service\ApiClientBuilder;
use Route\Route\Service\ApiException;
use Route\Route\Service\Model\InlineResponse2001;

/**
 * Order Route API client
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
class Order extends Fallbackable
{
    const API_ENDPOINT = 'orders';
    const DEFAULT_ROUTE_ORDER_ID = 'NOT_VALID';

    protected OrderParser $orderParser;

    protected Data $helper;

    protected Shipment $shipmentClient;

    protected orderResource $orderResource;

    protected OrdersApi $apiInstance;

    /**
     * Order constructor.
     * @param Data $helper
     * @param Sentry $logger
     * @param OrderParser $parser
     * @param FallbackFactory $fallback
     * @param Shipment $shipmentClient
     * @param orderResource $orderResource Magento\Sales\Model\ResourceModel\Order
     * @param ApiClientBuilder $apiClientBuilder
     */
    public function __construct(
        Data                     $helper,
        Sentry          $logger,
        OrderParser              $parser,
        FallbackFactory          $fallback,
        Shipment                 $shipmentClient,
        OrderResource            $orderResource,
        ApiClientBuilder         $apiClientBuilder
    ) {
        $this->orderParser = $parser;
        $this->helper = $helper;
        $this->shipmentClient = $shipmentClient;
        $this->orderResource = $orderResource;
        $this->apiInstance = $apiClientBuilder->buildOrderApiClient();
        parent::__construct($logger, $fallback);
    }

    /**
     * Submit an order creation to Route API
     *
     * @param OrderInterface $order order object
     * @param bool $isInsured if order is insured or not
     * @param bool $parseShipment send false if shipments aren't needed to process
     * @return void
     */
    public function postOrder(OrderInterface $order, bool $isInsured, bool $parseShipment = true): bool
    {
        if (!$this->helper->canSubmitOrder($order)) {
            return false;
        }

        if ($this->hasRouteOrderId($order)) {
            return $this->postUpdateOrder($order, $isInsured);
        }

        $body = $this->orderParser->getRouteObject($order, $isInsured);

        try {
            $completeOrder = $this->apiInstance->createOrderAsync($body)->wait();
        } catch (ApiException $exception) {
            $this->enqueueOperation($order, 'postOrder');
            $this->logger->error(
                "Error while trying to create an order:" . $exception->getMessage(),
                $exception->getTrace(),
                [
                    'params' => sprintf("%s", $body),
                    'method' => 'POST',
                    'endpoint' => '/v2/orders',
                ]
            );
            return false;
        }

        try {
            /** @noinspection PhpUndefinedMethodInspection */
            $order->setRouteOrderId($completeOrder->getId() ?? self::DEFAULT_ROUTE_ORDER_ID);
            /** @noinspection PhpParamsInspection */
            $this->orderResource->save($order);
        } catch (Exception $exception) {
            $this->logger->error(
                $exception->getMessage(),
                $exception->getTrace()
            );
            return false;
        }

        if ($parseShipment && $shipments = $order->getShipmentsCollection()) {
            foreach ($shipments as $shipment) {
                $this->shipmentClient->postShipment(
                    $shipment
                );
            }
        }
        return true;
    }

    /**
     * Submit an order cancelling to Route API
     *
     * @param \Magento\Sales\Model\Order $order order object
     *
     */
    public function postOrderCancel(OrderInterface $order) : bool
    {
        if (!$this->hasRouteOrderId($order)) {
            return false;
        }

        try {
            $this->apiInstance->orderCancel($order->getIncrementId());
            return true;
        } catch (Exception $exception) {
            $this->enqueueOperation($order, 'postOrderCancel');
            $this->logger->error(
                "Error trying to cancel an order:" . $exception->getMessage(),
                $exception->getTrace(),
                [
                    'params' => $order->getIncrementId(),
                    'method' => 'POST',
                    'endpoint' => "/v1/orders/{$order->getIncrementId()}/cancel",
                ]
            );
            return false;
        }
    }

    /**
     * Submit order update to Route API
     *
     * @param OrderInterface $order order object
     * @param bool $isInsured if order has insurance
     *
     * @return void
     */
    public function postUpdateOrder(OrderInterface $order, bool $isInsured) : bool
    {
        if (!$this->helper->canSubmitOrder($order)) {
            return false;
        }

        if (!$this->hasRouteOrderId($order)) {
            return $this->postOrder($order, $isInsured);
        }

        $body = $this->orderParser->getRouteObject($order, $isInsured);

        try {
            $this->apiInstance->updateOrder($body, $order->getIncrementId());
            return true;
        } catch (Exception $exception) {
            $this->enqueueOperation($order, 'postUpdateOrder');
            $this->logger->error(
                "Error trying to update an order:" . $exception->getMessage(),
                $exception->getTrace(),
                [
                    'params' => sprintf("%s", $body),
                    'method' => 'POST',
                    'endpoint' => "/v2/orders/{$order->getIncrementId()}",
                ]
            );
        }
        return false;
    }

    public function hasRouteOrderId(OrderInterface $order): bool
    {
        if (!empty($order->getRouteOrderId())) {
            return true;
        }

        $routeOrder = $this->getOrder($order);

        if (!$routeOrder || !$routeOrder->getId()) {
            return false;
        }

        try {
            $order->setRouteOrderId($routeOrder['id']);
            $this->orderResource->save($order);
            return true;
        } catch (Exception $exception) {
            $this->logger->error(
                $exception->getMessage(),
                $exception->getTrace()
            );
        }

        return false;
    }

    /**
     * @param $object
     * @param $operation
     * @return void
     */
    public function enqueueOperation($object, $operation): void
    {
        try {
            if (!$this->isRetrying()) {
                $fallback = $this->fallback->create();
                $fallback->setEntityId($object->getIncrementId())
                    ->setType(self::API_ENDPOINT)
                    ->setOperation($operation)
                    ->setStatus(Fallback::STATUS_PENDING)
                    ->save();
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), $e->getTrace());
        }
    }

    /**
     * Get Order from Route API
     *
     * @param OrderInterface $order Order Object
     *
     * @return InlineResponse2001|null
     *
     */
    public function getOrder(OrderInterface $order) :  InlineResponse2001 | null
    {
        try {
            return $this->apiInstance->getOrder($order->getIncrementId());
        } catch (Exception $exception) {
            if ($exception->getCode() != 404) {
                $this->logger->error(
                    "Error getting order information:" . $exception->getMessage(),
                    $exception->getTrace(),
                    [
                        'params' => $order->getIncrementId(),
                        'method' => 'GET',
                        'endpoint' => "/v1/orders/{$order->getIncrementId()}",
                    ]
                );
            }
        }
        return null;
    }
}
