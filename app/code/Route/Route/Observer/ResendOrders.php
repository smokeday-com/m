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

use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Route\Route\Helper\Data;
use Route\Route\Model\Route\Order;

/**
 * Cron that resend Orders
 *
 * Php version 7.0^
 *
 * @category  Cron
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class ResendOrders
{
    const ISSUE_BEGIN = '20200201000000';
    const LAST_PROCESSED_ORDER = 'last_processed_order';
    const SIZE = 100;
    private $orderCollectionFactory;
    private $routeHelper;
    private $orderClient;

    public function __construct(
        CollectionFactory $orderCollectionFactory,
        Data $routeHelper,
        Order $orderClient
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->routeHelper = $routeHelper;
        $this->orderClient = $orderClient;
    }

    public function retryOrders()
    {
        if (!$this->routeHelper->isRouteModuleEnable() || $this->routeHelper->isInvalidMerchant()) {
            return $this;
        }
        $lastProcessedOne = $this->routeHelper->getConfigValue(self::LAST_PROCESSED_ORDER, true);

        foreach ($this->getPendingOrders($lastProcessedOne) as $order) {
            $lastProcessedOne = $order->getId();
            if (empty($order->getRouteOrderId())) {
                $this->orderClient->postOrder($order, $order->getRouteIsInsured());
            }
        }

        $this->routeHelper->saveConfig(self::LAST_PROCESSED_ORDER, $lastProcessedOne);

        return $this;
    }

    /**
     * @param int $lastProcessedOne
     *
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    private function getPendingOrders($lastProcessedOne = 0)
    {
        $ordersCollection = $this->orderCollectionFactory->create();
        $date = \DateTime::createFromFormat('YmdHis', self::ISSUE_BEGIN);

        if (!empty($this->routeHelper->getOrderStatus())) {
            $ordersCollection->addFieldToFilter('status', [
                'in' => $this->routeHelper->getOrderStatus()
            ]);
        }

        if (!empty($lastProcessedOne)) {
            $ordersCollection->addFieldToFilter('entity_id', [
                'gt' => $lastProcessedOne
            ]);
        }

        $ordersCollection->getSelect()->limit(self::SIZE);
        $ordersCollection->addFieldToFilter('status', [
            'nin' => $this->routeHelper->getOrderStatusCanceled()
        ])->addFieldToFilter('created_at', [
            'gt' => $date->format('YmdHis')
        ])->addFieldToFilter('route_order_id', [
                ['null' => true],
                ['eq' => '']
        ]);

        return $ordersCollection;
    }
}
