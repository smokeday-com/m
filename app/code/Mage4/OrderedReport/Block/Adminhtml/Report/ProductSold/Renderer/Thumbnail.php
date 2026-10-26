<?php

namespace Mage4\OrderedReport\Block\Adminhtml\Report\ProductSold\Renderer;

use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class Thumbnail extends AbstractRenderer
{
    /**
     * @var ProductRepository
     */
    protected $productRepository;
    protected $_Image;
    protected $storeManager;
    protected $resourceModel;

    public function __construct(
        Context               $context,
        ProductRepository     $productRepository,
        StoreManagerInterface $storeManager,
        Image                 $_Image,
        Product               $resourceModel,
        array                 $data = []
    ) {
        parent::__construct($context, $data);
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->_Image = $_Image;
        $this->resourceModel = $resourceModel;
    }

    /**
     * @throws NoSuchEntityException
     */
    public function render(DataObject $row)
    {
        $productSku = $row->getData('order_items_sku');
        $productId = $this->resourceModel->getIdBySku($productSku);
        $html = '';
        if ($productId) {
            try {
                if ($product = $this->productRepository->getById($productId)) {
                    $imageUrl = $this->_Image->init($product, 'product_page_image_small')
                        ->setImageFile($product->getSmallImage()) // image,small_image,thumbnail
                        ->resize(300)
                        ->getUrl();
                    if ($imageUrl) {
                        $html = '<img src="' . $imageUrl . '" width="100px" />';
                    }
                }
            } catch (NoSuchEntityException $e) {
                // handle product not found exception
            }
        }
        return $html;
    }
}
