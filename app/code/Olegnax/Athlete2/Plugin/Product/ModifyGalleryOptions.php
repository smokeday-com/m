<?php
namespace Olegnax\Athlete2\Plugin\Product;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class ModifyGalleryOptions
{

    const XML_ENABLED = 'athlete2_settings/general/enable';
    const GALLERY_NAVDIR = 'athlete2_settings/product/gallery_navdir';
    const GALLERY_HEIGHT = 'athlete2_settings/product_images/product_image_height';
    const GALLERY_WIDTH = 'athlete2_settings/product_images/product_image_width';
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


    public function afterGetOptionsJson(
        \Magento\Catalog\Block\Product\View\GalleryOptions $subject,
        $result
    ) {
        if (!$this->isEnabled()){
            return $result;
        }
        // Decode the JSON to an associative array
        $optionsArray = json_decode($result, true);

        if ($this->getConfig(static::GALLERY_NAVDIR) === 'vertical'){
            $optionsArray['navdir'] = 'vertical'; 
        }
        if ($value = $this->getConfig(static::GALLERY_HEIGHT)){
            $optionsArray['height'] = $value; 
        }
        if ($value = $this->getConfig(static::GALLERY_WIDTH)){
            $optionsArray['width'] = $value; 
        }
        // Encode the modified options array back to JSON
        $modifiedOptions = json_encode($optionsArray);

        return $modifiedOptions;
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
