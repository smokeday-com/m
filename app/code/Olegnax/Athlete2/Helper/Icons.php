<?php

namespace Olegnax\Athlete2\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Escaper;
use Magento\Store\Model\ScopeInterface;
use Olegnax\Athlete2\Model\Config\Settings\Icons\ToolbarIconsList;
use Olegnax\Athlete2\Model\Config\Settings\Icons\IconsList;


class Icons extends AbstractHelper
{
    const ICONS_PATH = 'athlete2_design/appearance_icons/icon_';
    /**
     * @var Helper
     */
    protected $helper;
    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @param Helper $helper
     * @param Escaper $escaper
     */
    public function __construct(
        Context $context,
        Helper $helper,
        Escaper $escaper
    ) {
        parent::__construct($context);
        $this->helper = $helper;
        $this->escaper = $escaper;
    }
    /**
     * @param string $path
     * @param string $storeCode
     * @return mixed
     */
    public function getConfig($path, $storeCode = null)
    {
        $value = $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeCode);
        if (is_null($value)) {
            $value = '';
        }
        return $value;
    }
    /**
     * @return array
     */
    public function getIconsList(){
        return [
            'cart',
            'account',
            'search',
            'compare',
            'wishlist',
        ];
    }
    /**
     * @return array
     */
    public function getIcon($name, $option_path = '')
    {
        if(!$name){
            return '';
        }
        $option_value = '';
        if($option_path){
            $option = $option_path . $name;
            $option_value = $this->getConfig($option) ?: '';
        }
        if(!$option_value){
            $option = static::ICONS_PATH . $name;
            $option_value = $this->getConfig($option) ?: '';
        }
        $icon = $this->getIconByValue($option_value);

        return $icon;
    }
    
    /**
     * @return array
     */
    public function getIconByValue($option_value, $_array_type = '')
    {
        if ($option_value && $option_value != 'custom') {
            if($_array_type === 'toolbar'){
                $icons_array = $this->getToolbarIconsArray();
            }else{
                $icons_array = $this->getIconsArray();
            }
            if (is_array($icons_array) && array_key_exists($option_value, $icons_array)) {
                return $icons_array[$option_value];
            }
        }
        return '';
    }
    /**
     * @return string
     */
    public function getIconOutput($icon)
    {
        if($icon && is_array($icon)){
            return $icon[0];
        }
        return '';
    }
    /**
     * @return string
     */
    public function getIconHTML($name, $option_path = '', $classes = '')
    {
        if( !$name ){
            return '';
        }

        $icon_option_value = $option_path ? $this->getConfig($option_path . $name) : '';
        $classes = 'a2-icon-' . $name . ' ' . $classes;
        $width = $this->getSize($name, 'width', $option_path);
        $height = $this->getSize($name, 'height', $option_path);

        if($icon_option_value === 'custom'){
            return '<svg version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" class="a2-icon--custom ' .  $this->escaper->escapeHtmlAttr($classes) . '" style="width:' . $this->escaper->escapeHtmlAttr($width) . 'px; height:' . $this->escaper->escapeHtmlAttr($height) . 'px;"></svg>';
        }

        if($icon_option_value){
            $classes .= ' a2-icon--' . $icon_option_value;
            $icon_array = $this->getIconByValue($icon_option_value);
            $_vb_width = '';
            $_vb_height = '';
            if(is_array($icon_array) && count($icon_array)){
                $_vb_width = $icon_array[1];
                $_vb_height = $icon_array[2];
            }
            $icon  = '<svg xmlns="http://www.w3.org/2000/svg" class="' .  $this->escaper->escapeHtmlAttr($classes) . '" 
            width="' . $this->escaper->escapeHtmlAttr($width) . '" height="' . $this->escaper->escapeHtmlAttr($height) . '" 
            viewBox="0 0 ' . $this->escaper->escapeHtmlAttr($_vb_width ?: $width) . ' ' . $this->escaper->escapeHtmlAttr($_vb_height ?: $height) . '" 
            style="width:' . $this->escaper->escapeHtmlAttr($width) . 'px; height:' . $this->escaper->escapeHtmlAttr($height) . 'px;">' . $this->getIconOutput($icon_array) .'</svg>';
        } else {
            $icon_class_name = $this->getConfig(static::ICONS_PATH . $name);
            $classes .= ' a2-icon--' . $icon_class_name;
            $icon = '<svg version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" class="' . $this->escaper->escapeHtmlAttr($classes) . '" width="' . $this->escaper->escapeHtmlAttr($width) . '" height="' . $this->escaper->escapeHtmlAttr($height) . '" viewBox="0 0 ' . $this->escaper->escapeHtmlAttr($width) . ' ' . $this->escaper->escapeHtmlAttr($height) . '"><use xlink:href="#a2-'. $this->escaper->escapeHtmlAttr($name) .'-icon"></use></svg>';
        }

        return $icon;
        
    }
    /**
     * @return string
     */
    public function getSize($name, $size, $option_path = ''){       
        $output = $this->getConfig($option_path . $size . '_' . $name);
        if(!$output){
            $icon = $this->getIcon($name, $option_path);
            // if custom icon size is not set, then get defaut value from icon array
            if(is_array($icon)){
                if($size === 'width'){
                    $output = $icon[1];
                }
                if($size === 'height'){
                    $output = $icon[2];
                }
            }            
        }
        return $output ?: 20;
    }

    /**
     * @return array
     */
    private static function getIconsArray(){
        // icon, width, height       
        return IconsList::getIconsArray();
    }

    public function getToolbarIcon($icon_name){
        if(!$icon_name){
            return '';
        }
        $icon ='';
        $toolbar = $this->getConfig('athlete2_design/appearance_toolbar');
        $toolbar_icons    = $toolbar['toolbar_icons'];
        if($toolbar_icons === 'custom'){
            $width =  $toolbar['toolbar_icon_width_' . $icon_name] ?: '16';
            $height = $toolbar['toolbar_icon_height_' . $icon_name] ?: '16';
            $iconUrl = $toolbar['toolbar_icon_custom_' . $icon_name . ''];
            $icon = '<img src="' . $block->escapeUrl($iconUrl) . '" width="' . $block->escapeHtmlAttr($width) . '" height="' . $block->escapeHtmlAttr($height) . '">';
        } else{
            $icon = $this->getToolbarIconHTML($icon_name, $toolbar_icons);
        }
        return $icon;
    }
    private function getToolbarIconsArray(){    
        return ToolbarIconsList::getIconsArray();
    }
    private function getToolbarIconHTML($name, $value){
        $icon_array = $this->getIconByValue($name . '_' . $value, 'toolbar');
        if(is_array($icon_array) && count($icon_array)){
            $width = $icon_array[1];
            $height = $icon_array[2];
            return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $this->escaper->escapeHtmlAttr($width) . '" height="' . $this->escaper->escapeHtmlAttr($height) . '" 
            viewBox="0 0 ' . $this->escaper->escapeHtmlAttr($width) . ' ' . $this->escaper->escapeHtmlAttr($height) . '" 
            style="width:' . $this->escaper->escapeHtmlAttr($width) . 'px; height:' . $this->escaper->escapeHtmlAttr($height) . 'px;">' . $this->getIconOutput($icon_array) .'</svg>';
        }
        return '';
    }
}
