<?php

namespace Mage4\Stocktracker\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Mage4\Stocktracker\Logger\Logger;
use Magento\CatalogInventory\Api\StockRegistryInterface;

class OrderPlaceAfter implements ObserverInterface
{
    protected $logger;
    protected $stockRegistry;
    protected static $loggedOrders = [];

    public function __construct(
        Logger $logger,
        StockRegistryInterface $stockRegistry
    ) {
        $this->logger = $logger;
        $this->stockRegistry = $stockRegistry;
    }

    public function execute(Observer $observer)
    {
        try {
            $order = $observer->getEvent()->getOrder();

            if (!$order || !$order->getId()) {
                return;
            }

            $orderId = $order->getIncrementId();
            $orderEntityId = $order->getId();
            $logKey = $orderEntityId . '_' . $orderId;

            if (isset(self::$loggedOrders[$logKey])) {
                return;
            }

            self::$loggedOrders[$logKey] = true;

            if (count(self::$loggedOrders) > 1000) {
                self::$loggedOrders = array_slice(self::$loggedOrders, -500, null, true);
            }

            $items = $order->getAllVisibleItems();

            foreach ($items as $item) {
                try {
                    $productId = $item->getProductId();
                    $sku = $item->getSku();
                    $qtyOrdered = $item->getQtyOrdered();
                    $stockItem = $this->stockRegistry->getStockItem($productId);

                    if (!$stockItem || !$stockItem->getItemId()) {
                        continue;
                    }

                    $currentQty = $stockItem->getQty();
                    $oldQty = $currentQty + $qtyOrdered;
                    $minQty = $stockItem->getMinQty();
                    $backorders = $stockItem->getBackorders();
                    $manageStock = $stockItem->getManageStock();
                    $isInStock = $stockItem->getIsInStock();

                    if ($manageStock) {
                        if ($currentQty <= $minQty && $backorders == 0) {
                            $isInStock = false;
                        } elseif ($currentQty > $minQty) {
                            $isInStock = true;
                        }
                    }

                    $logData = [
                        'product_id' => $productId,
                        'sku' => $sku,
                        'qty_ordered' => $qtyOrdered,
                        'order_id' => $orderId,
                        'old_qty' => $oldQty,
                        'new_qty' => $currentQty,
                        'is_in_stock' => $isInStock
                    ];

                    $this->logger->logOrderPlacement($logData);

                } catch (\Exception $e) {
                    try {
                        $this->logger->error('Stock tracker error for item: ' . $e->getMessage());
                    } catch (\Exception $logException) {
                        // Silent catch
                    }
                }
            }

        } catch (\Exception $e) {
            try {
                $this->logger->error('Stock tracker error: ' . $e->getMessage());
            } catch (\Exception $logException) {
                // Silent catch
            }
        }
    }
}
