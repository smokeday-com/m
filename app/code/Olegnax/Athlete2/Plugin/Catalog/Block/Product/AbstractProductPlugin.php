<?php
/* Show price difference label for sale products */
declare(strict_types=1);

namespace Olegnax\Athlete2\Plugin\Catalog\Block\Product;

use Olegnax\Athlete2\Block\Product\SalePriceDifference;
use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Model\Product;

class AbstractProductPlugin
{
        /**
     * @var SalePriceDifference
     */
    private $salePriceDifference;
    /**
     * @var Product
     */
    private $product;

    /**
     * ListProductPricePlugin constructor.
     * @param SalePriceDifference $salePriceDifference
     */
    public function __construct(
        SalePriceDifference $salePriceDifference
    ) {
        $this->salePriceDifference = $salePriceDifference;
    }

    public function aroundGetProductPrice(AbstractProduct $subject, callable $proceed, Product $product)
    {
        $html = $proceed($product);
        if(!$this->salePriceDifference->isEnabled()){
            return $html;
        }

        if($this->salePriceDifference->getConfig('athlete2_settings/products_listing/show_price_diff_pos') === 'after'){
            return $html . $this->salePriceDifference->getSalePriceDifference($product); // merge with the original result
        } 
        return $this->salePriceDifference->getSalePriceDifference($product) . $html; // merge with the original result
    }
}