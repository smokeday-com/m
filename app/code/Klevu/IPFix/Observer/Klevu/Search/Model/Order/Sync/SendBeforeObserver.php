<?php

namespace Klevu\IPFix\Observer\Klevu\Search\Model\Order\Sync;


use Magento\Framework\Event\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class SendBeforeObserver implements ObserverInterface
{

    /**
     * @var OrderRepositoryInterface
     */


    private $orderRepository;


    /**
     * @param OrderRepositoryInterface $orderRepository
     */

    public function __construct(
        OrderRepositoryInterface $orderRepository
    )
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $eventData = $observer->getDataUsingMethod('event_data');
        $orderItem = $eventData->getDataUsingMethod('order_item');

        if (!($orderItem instanceof OrderItemInterface) || !$orderItem->getOrderId()) {


            return;

        }

        try {


            $order = $this->orderRepository->get($orderItem->getOrderId());

        } catch (NoSuchEntityException $e) {


            return;

        }
        $xForwardedFor = trim((string)$order->getXForwardedFor());


        if (!$xForwardedFor) {


            return;
        }

        $parameters = $eventData->getDataUsingMethod('parameters');

        $parameters['klevu_shopperIP'] = trim(current(explode(',', $xForwardedFor)));


        $eventData->setData('parameters', $parameters);
    }
}
