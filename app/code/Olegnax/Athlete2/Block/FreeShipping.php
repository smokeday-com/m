<?php
namespace Olegnax\Athlete2\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Escaper;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Locale\FormatInterface;

class FreeShipping extends Template
{

    const XML_ENABLED = 'athlete2_settings/general/enable';
    const SHIPPING_MINICART_ENABLED = 'athlete2_settings/shipping_bar/minicart_enable';
    const SHIPPING_CARTPAGE_ENABLED = 'athlete2_settings/shipping_bar/cart_page_enable';
    const SHIPPING_TEXT_ENABLE = 'athlete2_settings/shipping_bar/cart_text_enable';
    const SHIPPING_TEXT = 'athlete2_settings/shipping_bar/cart_text';
    const SHIPPING_TEXT_PROGRESS = 'athlete2_settings/shipping_bar/cart_text_progress';
    const SHIPPING_TEXT_SUCCESS = 'athlete2_settings/shipping_bar/cart_text_success';
    const MAGENTO_SHIPPING_ENABLED = 'carriers/freeshipping/active';
    const MAGENTO_SHIPPING_GOAL = 'carriers/freeshipping/free_shipping_subtotal';
    const HIDE_IN_EMPTY_CART = 'athlete2_settings/shipping_bar/hide_in_empty_cart';
    const SUCCESS_ANIMATION = 'athlete2_design/shipping_bar/enable_success_fw_animation';

    protected $scopeConfig;
    /**
     * @var Escaper
     */
    private $escaper;
    public function __construct(
        Template\Context $context,
        FormatInterface $localeFormat,
        ScopeConfigInterface $scopeConfig,
        array $data = [],
        Escaper $escaper = null
    ) {
        parent::__construct($context, $data);
        $this->localeFormat = $localeFormat;
        $this->scopeConfig = $scopeConfig;
        $this->escaper = $escaper ?? ObjectManager::getInstance()->get(
            Escaper::class
        );
    }

    public function getJsLayout()
    {
        $minicart = (bool)$this->getConfig(static::SHIPPING_MINICART_ENABLED);
        $cartpage = (bool)$this->getConfig(static::SHIPPING_CARTPAGE_ENABLED);

        if($this->isEnabled() && $minicart){

            $freeShippingValue = $this->getConfig(static::MAGENTO_SHIPPING_GOAL);

            if(!empty($freeShippingValue)){
                $configData = [];
                $freeShippingText = __('Enjoy Free shipping on orders over {{free_shipping_price}}');
                $freeShippingTextProgress = __('You\'re {{free_shipping_remaining_price}} away from <span class="free-shipping-highlight">FREE Shipping!</span>');
                $freeShippingTextSuccess = __('Congratulations! You are eligible for Free Shipping!');

                if($this->getConfig(static::SHIPPING_TEXT_ENABLE)){
                    $freeShippingText = $this->getConfig(static::SHIPPING_TEXT);
                    $freeShippingTextProgress = $this->getConfig(static::SHIPPING_TEXT_PROGRESS);
                    $freeShippingTextSuccess = $this->getConfig(static::SHIPPING_TEXT_SUCCESS);
                }

                $freeShippingText = $this->escapeString($freeShippingText);
                $freeShippingTextProgress = $this->escapeString($freeShippingTextProgress);
                $freeShippingTextSuccess = $this->escapeString($freeShippingTextSuccess);
                
                $configData = [
                    'freeShippingPrice' => (int)$freeShippingValue,
                    'freeShippingText' => $freeShippingText,
                    'freeShippingTextProgress' => $freeShippingTextProgress,
                    'freeShippingTextSuccess' => $freeShippingTextSuccess,
                    'hideWhenEmpty' => (bool)$this->getConfig(static::HIDE_IN_EMPTY_CART),
                    'successAnimation' => (bool)$this->getConfig(static::SUCCESS_ANIMATION),
                    'priceFormat' => $this->getPriceFormat(), // Include the priceFormat configuration
                ];
                if($minicart && array_key_exists('ox-shipping-bar', $this->jsLayout['components'])){
                    $this->jsLayout['components']['ox-shipping-bar'] += $configData;
                }
                if($cartpage && array_key_exists('ox-shipping-bar-cart', $this->jsLayout['components'])){
                    $this->jsLayout['components']['ox-shipping-bar-cart'] += $configData;
                }

            }
            
        }
        return parent::getJsLayout();
    }
    /**
     * Converts incoming data to string format and escapes special characters.
     *
     * @return string
     */
    private function escapeString($data)
    {
        return $this->escaper->escapeHtml((string)$data, ['span', 'a']);
    }

    public function getConfig($path, $storeCode = null)
    {
        return $this->getSystemValue($path, $storeCode);
    }

    public function getSystemValue($path, $storeCode = null, $scopeType = ScopeInterface::SCOPE_STORE)
    {
        $value = $this->scopeConfig->getValue(
            $path,
            $scopeType,
            $storeCode
        );
        if (is_null($value)) {
            $value = '';
        }
        return $value;
    }
    public function isEnabled()
    {
        return (bool)$this->getConfig(static::XML_ENABLED) && (bool)$this->getConfig(static::MAGENTO_SHIPPING_ENABLED) && !empty($this->getConfig(static::MAGENTO_SHIPPING_GOAL));
    }

    private function getPriceFormat()
    {   
        return $this->localeFormat->getPriceFormat();
    }
}