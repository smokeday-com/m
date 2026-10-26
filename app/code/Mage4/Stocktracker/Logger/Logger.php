<?php

namespace Mage4\Stocktracker\Logger;

use Monolog\Logger as MonologLogger;

class Logger extends MonologLogger
{
    public function logStockChange(array $data)
    {
        try {
            $message = sprintf(
                "[STOCK CHANGE] Product ID: %s | SKU: %s | Old Qty: %s | New Qty: %s | Stock Status: %s | Changed By: %s",
                $data['product_id'] ?? 'N/A',
                $data['sku'] ?? 'N/A',
                $data['old_qty'] ?? 'N/A',
                $data['new_qty'] ?? 'N/A',
                isset($data['is_in_stock']) ? ($data['is_in_stock'] ? 'In Stock' : 'Out of Stock') : 'N/A',
                $data['changed_by'] ?? 'System'
            );

            $this->info($message);
        } catch (\Exception $e) {
            // Silent catch
        }
    }

    public function logOrderPlacement(array $data)
    {
        try {
            $message = sprintf(
                "[ORDER PLACED] Product ID: %s | SKU: %s | Qty Ordered: %s | Order ID: %s | Old Qty: %s | New Qty: %s | Stock Status: %s",
                $data['product_id'] ?? 'N/A',
                $data['sku'] ?? 'N/A',
                $data['qty_ordered'] ?? 'N/A',
                $data['order_id'] ?? 'N/A',
                $data['old_qty'] ?? 'N/A',
                $data['new_qty'] ?? 'N/A',
                isset($data['is_in_stock']) ? ($data['is_in_stock'] ? 'In Stock' : 'Out of Stock') : 'N/A'
            );

            $this->info($message);
        } catch (\Exception $e) {
            // Silent catch
        }
    }
}
