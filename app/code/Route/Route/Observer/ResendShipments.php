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

use Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory;
use Route\Route\Helper\Data;
use Route\Route\Model\Route\Order;
use Route\Route\Model\Route\Shipment;

/**
 * Cron that resend Shipments
 *
 * Php version 7.0^
 *
 * @category  Cron
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2020 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class ResendShipments
{
    /**
     * @var CollectionFactory
     */
    private $trackCollectionFactory;

    /**
     * @var Data
     */
    private $routeHelper;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var Order
     */
    private $orderClient;

    /**
     * @var Shipment
     */
    private $shipmentClient;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * ResendShipments constructor.
     * @param CollectionFactory $trackCollectionFactory
     * @param Data $routeHelper
     * @param ResourceConnection $resource
     * @param OrderRepositoryInterface $orderRepository
     * @param Order $orderClient
     * @param Shipment $shipmentClient
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        CollectionFactory $trackCollectionFactory,
        Data $routeHelper,
        ResourceConnection $resource,
        OrderRepositoryInterface $orderRepository,
        Order $orderClient,
        Shipment $shipmentClient,
        TimezoneInterface $timezone
    ) {
        $this->trackCollectionFactory = $trackCollectionFactory;
        $this->routeHelper = $routeHelper;
        $this->resource = $resource;
        $this->orderRepository = $orderRepository;
        $this->orderClient = $orderClient;
        $this->shipmentClient = $shipmentClient;
        $this->timezone = $timezone;
    }

    /**
     * Retry Shipments made on last hour
     * @throws Exception
     */
    public function retryShipmentsHourly()
    {
        $date = $this->timezone->date();
        $date->sub(new \DateInterval('PT24H'));
        $this->retryShipments($date);
    }

    /**
     * Retry Shipments based on custom config
     * @throws Exception
     */
    public function retryShipmentsCustom()
    {
        $date = $this->timezone->date();
        $date->sub(new \DateInterval($this->routeHelper->getShipmentCronjobCustomPeriod()));
        $this->retryShipments($date);
    }

    public function retryShipments($timeRange)
    {
        if (!$this->routeHelper->isRouteModuleEnable() || $this->routeHelper->isInvalidMerchant()) {
            return $this;
        }

        foreach ($this->getPendingTracks($timeRange) as $track) {
            if (!$track->getRouteOrderId()) {
                $order = $this->orderRepository->get($track->getOrderId());
                if (empty($order->getRouteOrderId())) {
                    $this->orderClient->postOrder($order, $order->getRouteIsInsured(), false);
                }
            }
            $this->shipmentClient->postTracking($track);
        }
        return $this;
    }

    /**
     * Get pending tracks collection
     * @param $date
     * @return Collection
     */
    private function getPendingTracks($date)
    {
        $tracksCollection = $this->trackCollectionFactory->create();

        $tracksCollection->join(
            ['order'=> $this->resource->getTableName('sales_order')],
            'order.entity_id=main_table.order_id',
            ['status'=>'status', 'route_is_insured' => 'route_is_insured', 'route_order_id' => 'route_order_id']
        );

        if (!empty($this->routeHelper->getOrderStatus())) {
            $tracksCollection->addFieldToFilter('status', [
                'in' => $this->routeHelper->getOrderStatus()
            ]);
        }

        $tracksCollection->addFieldToFilter('status', [
            'nin' => $this->routeHelper->getOrderStatusCanceled()
        ])->addFieldToFilter('main_table.created_at', [
            'gt' => $tracksCollection->getConnection()->formatDate($date)
        ])->addFieldToFilter('main_table.route_shipment_id', [
            ['null' => true],
            ['eq' => ''],
            ['eq' => '0']
        ]);

        return $tracksCollection;
    }
}
