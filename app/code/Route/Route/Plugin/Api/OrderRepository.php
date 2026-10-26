<?php

namespace Route\Route\Plugin\Api;

use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class OrderRepository
{

    /**
     * Order Extension Attributes Factory
     *
     * @var OrderExtensionFactory
     */
    protected $extensionFactory;

    /**
     * OrderRepositoryPlugin constructor
     *
     * @param OrderExtensionFactory $extensionFactory
     */
    public function __construct(OrderExtensionFactory $extensionFactory)
    {
        $this->extensionFactory = $extensionFactory;
    }

    /**
     * @param  OrderRepositoryInterface $subject
     * @param  $entity
     * @return mixed
     */
    public function afterGet(OrderRepositoryInterface $subject, $entity)
    {
        $extensionAttributes = $entity->getExtensionAttributes();

        if ($extensionAttributes && method_exists($extensionAttributes, 'setRouteFee')) {
            $extensionAttributes->setRouteFee($entity->getRouteFee());
            $extensionAttributes->setRouteIsInsured($entity->getRouteIsInsured());
            $extensionAttributes->setRouteTaxFee($entity->getRouteTaxFee());
            $extensionAttributes->setRouteOrderId($entity->getRouteOrderId());
            $entity->setExtensionAttributes($extensionAttributes);
        }
        return $entity;
    }

    /**
     * @param OrderRepositoryInterface   $subject
     * @param OrderSearchResultInterface $searchResult
     *
     * @return OrderSearchResultInterface
     */
    public function afterGetList(OrderRepositoryInterface $subject, OrderSearchResultInterface $searchResult)
    {
        $orders = $searchResult->getItems();

        foreach ($orders as &$order) {
            $extensionAttributes = $order->getExtensionAttributes();
            $extensionAttributes = $extensionAttributes ? $extensionAttributes : $this->extensionFactory->create();
            if (method_exists($extensionAttributes, 'setRouteFee')) {
                $extensionAttributes->setRouteFee($order->getRouteFee());
                $extensionAttributes->setRouteIsInsured($order->getRouteIsInsured());
                $extensionAttributes->setRouteTaxFee($order->getRouteTaxFee());
                $extensionAttributes->setRouteOrderId($order->getRouteOrderId());
                $order->setExtensionAttributes($extensionAttributes);
            }
        }

        return $searchResult;
    }
}
