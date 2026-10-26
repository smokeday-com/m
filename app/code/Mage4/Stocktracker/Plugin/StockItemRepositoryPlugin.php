<?php

namespace Mage4\Stocktracker\Plugin;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Model\Stock\StockItemRepository;
use Mage4\Stocktracker\Logger\Logger;
use Magento\Catalog\Api\ProductRepositoryInterface;

class StockItemRepositoryPlugin
{
    protected $logger;
    protected $productRepository;
    protected $originalStockData = [];
    protected static $recentOrderQtyChanges = [];

    public function __construct(
        Logger $logger,
        ProductRepositoryInterface $productRepository
    ) {
        $this->logger = $logger;
        $this->productRepository = $productRepository;
    }

    public function beforeSave(StockItemRepository $subject, StockItemInterface $stockItem)
    {
        try {
            $itemId = $stockItem->getItemId();
            if ($itemId) {
                try {
                    $originalStockItem = $subject->get($itemId);
                    $this->originalStockData[$itemId] = [
                        'qty' => $originalStockItem->getQty(),
                        'is_in_stock' => $originalStockItem->getIsInStock(),
                        'product_id' => $originalStockItem->getProductId()
                    ];
                } catch (\Exception $e) {
                    $this->originalStockData[$itemId] = [
                        'qty' => $stockItem->getQty(),
                        'is_in_stock' => $stockItem->getIsInStock(),
                        'product_id' => $stockItem->getProductId()
                    ];
                }
            }
        } catch (\Exception $e) {
            // Silent catch
        }

        return [$stockItem];
    }

    public function afterSave(StockItemRepository $subject, StockItemInterface $result, StockItemInterface $stockItem)
    {
        try {
            $itemId = $stockItem->getItemId();
            if (!$itemId || !isset($this->originalStockData[$itemId])) {
                return $result;
            }

            $originalData = $this->originalStockData[$itemId];
            $productId = $stockItem->getProductId();
            $newQty = $stockItem->getQty();
            $oldQty = $originalData['qty'] ?? $newQty;
            $newStockStatus = $stockItem->getIsInStock();
            $oldStockStatus = $originalData['is_in_stock'] ?? $newStockStatus;

            $qtyChanged = ($oldQty != $newQty);
            $statusChanged = ($oldStockStatus != $newStockStatus);

            if (!$qtyChanged && !$statusChanged) {
                unset($this->originalStockData[$itemId]);
                return $result;
            }

            $isAutomaticStatusChange = false;
            if ($statusChanged && !$qtyChanged) {
                $recentOrderTime = self::$recentOrderQtyChanges[$productId] ?? 0;
                $timeSinceOrder = time() - $recentOrderTime;

                if ($timeSinceOrder < 10 && $recentOrderTime > 0) {
                    unset($this->originalStockData[$itemId]);
                    return $result;
                }

                try {
                    $stockStatusChangedAuto = method_exists($stockItem, 'getStockStatusChangedAuto')
                        ? $stockItem->getStockStatusChangedAuto()
                        : null;
                    if ($stockStatusChangedAuto == 1) {
                        $isAutomaticStatusChange = true;
                    }
                } catch (\Exception $e) {
                    // Default to Admin
                }
            }

            if ($qtyChanged && $oldQty > $newQty && $oldQty > 0) {
                self::$recentOrderQtyChanges[$productId] = time();

                if (count(self::$recentOrderQtyChanges) > 1000) {
                    $currentTime = time();
                    foreach (self::$recentOrderQtyChanges as $pid => $timestamp) {
                        if ($currentTime - $timestamp > 60) {
                            unset(self::$recentOrderQtyChanges[$pid]);
                        }
                    }
                }
            }

            $sku = 'N/A';
            try {
                $product = $this->productRepository->getById($productId);
                $sku = $product->getSku();
            } catch (\Exception $e) {
                // Continue
            }

            $changedBy = 'Admin';

            if ($qtyChanged && $statusChanged) {
                $changedBy = 'Admin';
            } elseif ($statusChanged && !$qtyChanged) {
                $changedBy = $isAutomaticStatusChange ? 'System (Automatic)' : 'Admin';
            } elseif ($qtyChanged && !$statusChanged) {
                $changedBy = ($oldQty > $newQty && $oldQty > 0) ? 'System (Order)' : 'Admin';
            }

            $logData = [
                'product_id' => $productId,
                'sku' => $sku,
                'old_qty' => $oldQty,
                'new_qty' => $newQty,
                'is_in_stock' => $newStockStatus,
                'changed_by' => $changedBy
            ];

            if ($statusChanged) {
                if ($qtyChanged && $statusChanged) {
                    $logData['changed_by'] = sprintf(
                        'Admin (Qty: %s → %s, Status: %s → %s)',
                        $oldQty,
                        $newQty,
                        $oldStockStatus ? 'In Stock' : 'Out of Stock',
                        $newStockStatus ? 'In Stock' : 'Out of Stock'
                    );
                } elseif ($isAutomaticStatusChange) {
                    $logData['changed_by'] = sprintf(
                        'System (Status: %s → %s)',
                        $oldStockStatus ? 'In Stock' : 'Out of Stock',
                        $newStockStatus ? 'In Stock' : 'Out of Stock'
                    );
                } else {
                    $logData['changed_by'] = sprintf(
                        'Admin (Status: %s → %s)',
                        $oldStockStatus ? 'In Stock' : 'Out of Stock',
                        $newStockStatus ? 'In Stock' : 'Out of Stock'
                    );
                }
            }

            $this->logger->logStockChange($logData);
            unset($this->originalStockData[$itemId]);

        } catch (\Exception $e) {
            try {
                $this->logger->error('Stock tracker plugin error: ' . $e->getMessage());
            } catch (\Exception $logException) {
                // Silent catch
            }
        }

        return $result;
    }
}
