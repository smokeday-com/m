<?php
/* Show price difference label for sale products */
declare(strict_types=1);

namespace Olegnax\Athlete2\Plugin\CatalogWidget\Block\Product;

use Olegnax\Athlete2\Block\Product\SalePriceDifference;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Block\Product\AbstractProduct;

class CatalogWidgetBlockPlugin
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

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param Product $product
     */
    public function setProduct($product)
    {
        $this->product = $product;
    }

    /**
     * @return string
     */
    private function getSalePriceDifference(){
        $product = $this->getProduct();
        return $this->salePriceDifference->getSalePriceDifference($product);
    }

    public function beforeGetProductPriceHtml(AbstractProduct $original, Product $product): array
    {
        $this->setProduct($product);

        return [$product];
    }

    public function afterGetProductPriceHtml(AbstractProduct $original, string $html): string
    {
        if(!$this->salePriceDifference->isEnabled()){
            return $html;
        }

        if($this->salePriceDifference->getConfig('athlete2_settings/products_listing/show_price_diff_pos') === 'after'){
            return $html . $this->getSalePriceDifference(); // merge with the original result
        }
        return $this->getSalePriceDifference() . $html; // merge with the original result
    }
}
