<?php
namespace Olegnax\Athlete2\Service;

use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;

class GetProductStockService
{
    protected $stockRegistry;
    protected $getSalableQuantityDataBySku;
    private $stockState;
    private $moduleManager;
    private $objectManager;
    private $msiEnabled;
    
    public function __construct(
        StockRegistryInterface $stockRegistry,
        StockStateInterface $stockState,
        Manager $moduleManager,
        ObjectManagerInterface $objectManager
    ) {
        $this->stockRegistry = $stockRegistry;
        $this->stockState = $stockState;
        $this->moduleManager = $moduleManager;
        // $this->objectManager = ObjectManager::getInstance();
        $this->objectManager        = $objectManager;
    }

    public function isMsiEnabled()
    {
        if(!isset($this->msiEnabled)){
            $this->msiEnabled = $this->moduleManager->isEnabled('Magento_Inventory') && $this->moduleManager->isEnabled('Magento_InventorySalesApi') && $this->moduleManager->isEnabled('Magento_InventorySalesAdminUi');
        }
        return $this->msiEnabled;
    }

    protected function getSalableQuantity($sku)
    {
        if(!$this->getSalableQuantityDataBySku){
            $this->getSalableQuantityDataBySku = $this->objectManager->get('\Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku');
        }
        $salableData = $this->getSalableQuantityDataBySku->execute($sku);
        return isset($salableData[0]['qty']) ? $salableData[0]['qty'] : 0;
    }
    
    public function getSimpleProductQuantity($product)
    {
        $qty = null;
        if ($product !== null && $product instanceof Product) {
            $sku = $product->getSku();
            if ($this->isMsiEnabled() && $sku !== null) {
                try {
                    // Attempt to get the salable quantity using MSI
                    $qty = $this->getSalableQuantity($sku);
                } catch (\Magento\Framework\Exception\InputException $e) {
                    $qty = null;
                }
            } 

            if(!$qty) {
                $stockItem = $this->stockRegistry->getStockItem($product->getId());
                $qty = $stockItem->getQty();
            }
        }
        return $qty;
    }

    public function getBaseStockQty($productId, $websiteId = null)
    {
        $qty = null;
        try{
            $qty = $this->stockState->getStockQty($productId, $websiteId);
        } catch (\Magento\Framework\Exception\InputException $e) {
            $qty = null;
        }
        return $qty;

    }
}
