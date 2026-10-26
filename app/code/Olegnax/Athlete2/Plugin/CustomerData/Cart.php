<?php
/**
 * @author      Olegnax
 * @package     Olegnax_Athlete2
 * @copyright   Copyright (c) 2023 Olegnax (http://olegnax.com/). All rights reserved.
 */

namespace Olegnax\Athlete2\Plugin\CustomerData;

use Magento\Framework\App\ProductMetadataInterface;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Cart
{
    const XML_ENABLED = 'athlete2_settings/general/enable';
    const CART_ITEMS_REORDER = 'athlete2_settings/header/minicart_reorder';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    public function afterGetSectionData(\Magento\Checkout\CustomerData\Cart $subject, $result)
    {
        if ($this->isEnabled() && (bool)$this->getConfig(static::CART_ITEMS_REORDER) && isset($result['items']) && is_array($result['items']) && count($result['items']) > 1) {
            $result['items'] = array_reverse($result['items']);
        }
        return $result;
    }

    private function getConfig($path, $storeCode = null)
    {
        return $this->getSystemValue($path, $storeCode);
    }

    private function getSystemValue($path, $storeCode = null, $scopeType = ScopeInterface::SCOPE_STORE)
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
    
    private function isEnabled()
    {
        return (bool)$this->getConfig(static::XML_ENABLED);
    }
}
