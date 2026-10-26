<?php
namespace Olegnax\Athlete2\Block\Product;

use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Block\Product\Context;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Olegnax\Athlete2\Helper\Helper as ThemeHelper;

class SalePriceDifference extends AbstractProduct
{
    /**
     * @var ThemeHelper
     */
    private $helper;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    /**
     * @var PriceHelper
     */
    protected $_priceHelper;

    /**
     * SalePriceDifference constructor.
     */
    public function __construct(
        Context $context,
        PriceHelper $priceHelper,
        ThemeHelper $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_priceHelper = $priceHelper;
        $this->helper = $helper;
        $this->logger = $context->getLogger();
    }
    /**
     * @return string
     */
    public function getSalePriceDifference(Product $product)
    {
         if ($this->isEnabled() && $product) {
            // try {
                $finalPrice = $product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();
                $oldPrice = $product->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue();
                $priceDifference = $oldPrice - $finalPrice;
                // if is on sale (has special price)
                if($priceDifference > 0){
                    //get custom text for label or use defaul if empty
                    $text = $this->getConfig('athlete2_settings/products_listing/show_price_diff_text');
                    if(!empty($text)){
                        $text = str_replace('{{price_diff}}', $this->formatPrice($priceDifference), (string)$text);
                    } else {
                        $text = __('Save %1', $this->formatPrice($priceDifference));
                    }
                    return '<div class="ox-sale-price-dif"><span class="price-dif__container">' . $text . '</span></div>';                
                }
            // } catch (\Exception $e) {
            //     $this->logger->critical($e->getLogMessage());
            // }
         }

        // If product is null or an exception occurs, proceed with the original method
        return '';
    }
    /**
     * Format the given price using the currency helper.
     *
     * @param float $price The price to be formatted.
     * @return string Formatted price string.
     */
    public function formatPrice($price)
    {
        // Use the _priceHelper object to format the price as currency.
        // The currency method takes three parameters:
        //   1. The price to be formatted.
        //   2. True to include the currency symbol.
        //   3. False to include the thousand separator.
        return $this->_priceHelper->currency($price, true, false);
    }

    public function getConfig($path, $storeCode = null){
        return $this->helper->getConfig($path, $storeCode);
    }

    public function isEnabled(){
        return $this->helper->isEnabled() &&
         (bool)$this->helper->getConfig('athlete2_settings/products_listing/show_price_diff');
    }
}