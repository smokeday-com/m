<?php

namespace Mage4\OrderedReport\Block\Adminhtml\Report\ProductSold\Renderer;

use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class Name extends AbstractRenderer
{
    /**
     * @var ProductRepository
     */
    protected $productRepository;
    protected $resourceModel;
    protected $storeManager;

    public function __construct(
        Context               $context,
        ProductRepository     $productRepository,
        Product               $resourceModel,
        StoreManagerInterface $storeManager,
        array                 $data = []
    ) {
        parent::__construct($context, $data);
        $this->productRepository = $productRepository;
        $this->resourceModel = $resourceModel;
        $this->storeManager = $storeManager;
    }

    /**
     * @throws NoSuchEntityException
     */
    public function render(DataObject $row)
    {
        $productSku = $row->getData('order_items_sku');
        $name = '';
        $productId = $this->resourceModel->getIdBySku($productSku);
        if ($productId) {
            $product = $this->productRepository->getById($productId);
            $name = $product->getName();
        }
        return $name;
    }
}
