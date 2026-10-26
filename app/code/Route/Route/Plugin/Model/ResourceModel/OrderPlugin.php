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
namespace Route\Route\Plugin\Model\ResourceModel;

use Magento\Sales\Api\OrderRepositoryInterface;
use Route\Route\Helper\Data;
use Route\Route\Model\Route\Order as OrderClient;
use Route\Route\Logger\Sentry;

/**
 * Rearrange totals to avoid Paypal Rejection
 *
 * Php version 7.0^
 *
 * @category  plugin
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class OrderPlugin
{
    /**
     * @var OrderClient
     */
    protected $orderClient;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Sentry
     */
    protected $logger;

    /**
     * @var OrderRepositoryInterface $orderRepository
     */
    protected $orderRepository;

    /**
     * OrderPlugin constructor.
     *
     * @param OrderClient $orderClient Order api client
     * @param Data        $helper      Route Helper
     * @param Sentry $logger  Sentry logger
     */
    public function __construct(
        OrderClient $orderClient,
        Data $helper,
        Sentry $logger,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->orderClient = $orderClient;
        $this->helper = $helper;
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
    }

    public function afterSave(\Magento\Sales\Model\ResourceModel\Order $subject, $result, $order)
    {
        $oldStatus = $order->getOrigData('status');
        $newStatus = $order->getData('status');

        if ($oldStatus === $newStatus) {
            return $result;
        }

        //loads object from database if there's no enough information
        if(is_null($order->getRouteIsInsured())){
            $order = $this->orderRepository->get($order->getId());
        }

        if ($this->shouldCancelOrder($newStatus)) {
            $this->orderClient->postOrderCancel($order);
            return $result;
        }

        if ($this->shouldUpsertOrder($newStatus)) {
            $this->orderClient->postOrder(
                $order,
                $order->getRouteIsInsured()
            );
        }

        return $result;

    }

    /**
     * @param mixed $newStatus
     * @return bool
     */
    public function shouldUpsertOrder(mixed $newStatus): bool
    {
        $orderUpsertStatuses = $this->helper->getOrderStatus();
        return empty($orderUpsertStatuses) ||
            in_array($newStatus, $orderUpsertStatuses) ||
            is_null($this->helper->getConfigValue(Data::ORDER_STATUS));
    }

    /**
     * @param mixed $newStatus
     * @return bool
     */
    public function shouldCancelOrder(mixed $newStatus): bool
    {
        $orderCancelStatuses = $this->helper->getOrderStatusCanceled();
        return in_array($newStatus, $orderCancelStatuses);
    }
}
