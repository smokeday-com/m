<?php

namespace Mage4\OrderedReport\Block\Adminhtml\Report\ProductSold\Renderer;

use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class AvailableStock extends AbstractRenderer
{
    protected $stockRegistry;
    /**
     * @var ProductRepository
     */
    protected $productRepository;
    protected $resourceModel;
    protected $storeManager;

    public function __construct(
        Context                $context,
        ProductRepository      $productRepository,
        Product                $resourceModel,
        StoreManagerInterface  $storeManager,
        StockRegistryInterface $stockRegistry,
        array                  $data = []
    ) {
        parent::__construct($context, $data);
        $this->productRepository = $productRepository;
        $this->resourceModel = $resourceModel;
        $this->storeManager = $storeManager;
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * @throws NoSuchEntityException
     */
    public function render(DataObject $row)
    {
        $productSku = $row->getData('order_items_sku');
        $qty = '';
        $productId = $this->resourceModel->getIdBySku($productSku);
        if ($productId) {
            $product = $this->stockRegistry->getStockItem($productId, null);
            $qty = $product->getQty();
        }
        return $qty;
    }
}
