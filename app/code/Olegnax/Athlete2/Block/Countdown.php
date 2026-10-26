<?php

/**
 * Athlete2 Theme
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Olegnax.com license that is
 * available through the world-wide-web at this URL:
 * https://www.olegnax.com/license
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Olegnax
 * @package     Olegnax_Athlete2
 * @copyright   Copyright (c) 2023 Olegnax (http://www.olegnax.com/)
 * @license     https://www.olegnax.com/license
 */

namespace Olegnax\Athlete2\Block;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Widget\Block\BlockInterface;
use Magento\Framework\View\Element\Template;
use Magento\Cms\Model\BlockFactory;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Olegnax\Athlete2\Model\DynamicStyle\EscapeCss;

class Countdown extends Template implements BlockInterface {

    protected $_template = 'widgets/countdown.phtml';

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;
    /**
     * Json Serializer Instance
     *
     * @var Json
     */
    protected $json;
    /**
     * @var DateTime
     */
    protected $dateTime;

    protected $escapeCss;

    protected $scopeConfig;

    public function __construct(
        Template\Context $context,
        BlockFactory $thisFactory,
        HttpContext $httpContext,
        DateTime $dateTime,
        ScopeConfigInterface $scopeConfig,
        TimezoneInterface $timezone,
        EscapeCss $escapeCss,
        Json $json,
        array $data = []
    ) {
        $this->blockFactory = $thisFactory;
        $this->httpContext = $httpContext;
        $this->dateTime = $dateTime;
        $this->scopeConfig = $scopeConfig;
        $this->timezone = $timezone;
        $this->escapeCss = $escapeCss;
        $this->json = $json;
        parent::__construct($context, $data);
      }

    public function getCacheKeyInfo($newval = []) {
        return array_merge([
            'OLEGNAX_COUNTDOWN_WIDGET',
            $this->_storeManager->getStore()->getId(),
            $this->_design->getDesignTheme()->getId(),
            $this->httpContext->getValue( Context::CONTEXT_GROUP),
            $this->json->serialize($this->getRequest()->getParams()),
            $this->json->serialize($this->getData()),
        ], parent::getCacheKeyInfo(), $newval);
    }

    /**
     * Get current time
     *
     * @return string
     */
    public function getCurrentTime()
    {
        return $this->dateTime->gmtDate();
    }

    public function getBlockId() {
        $name = $this->getNameInLayout();
        $name = preg_replace('/[^a-zA-Z0-9_]/i', '_', $name);
        $name .= substr(md5(microtime()), -5);
        return 'ox_' . $name;
    }

    public function getServerTimeZoneOffset()
    {
        // Retrieve the server time zone from the configuration
        $serverTimeZone = $this->scopeConfig->getValue(
            'general/locale/timezone',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        // Create a DateTimeZone object for the server's time zone
        $dateTimeZone = new \DateTimeZone($serverTimeZone);

        // Get the time zone offset in seconds
        $offsetSeconds = $dateTimeZone->getOffset(new \DateTime());

        // Convert the offset to hours
        $offsetHours = $offsetSeconds / 3600;

        return $offsetHours;
    }

    public function isDateValid($inputDate, $format = 'Y-m-d H:i:s')
    {
        try {
            // Create a DateTime object using the given format
            $dateTime = \DateTime::createFromFormat($format, $inputDate);
            
            // Check if the date is valid
            if ($dateTime && $dateTime->format($format) == $inputDate) {
                return true;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
    public function escapeCss($css){
        return $this->escapeCss->escapeCss($css);
    }
    /**
     * Render Inline styles.
     *
     * @param string $id     Block Identifier
     * @param string $styles CSS styles to render.
     * @return string Rendered CSS styles wrapped in style tags.
     */
    public function renderStyles($id = '', $styles = ''){
        $cssStyles = $this->cssStyles($id, $styles);
        return $this->escapeCss->renderStyles($cssStyles);
    }

  
    public function cssStyles($id = '', $styles = ''){

         $wrapperCss = $sectionCss = $numCss = $numCssMobile = $wrapperCssMobile = $mobileStyles = '';

        if($this->getTextFontSize()){
          $wrapperCss .= 'font-size:' . (int)$this->getTextFontSize() . 'px;'; 
        }
        if($this->getTextFontSizeMobile()){
          $wrapperCssMobile .= 'font-size:' . (int)$this->getTextFontSizeMobile() . 'px;'; 
        }
        if($this->getTextFontWeight()){
          $wrapperCss .= 'font-weight:' . (int)$this->getTextFontWeight() . ';'; 
        }
        if($this->getGap()){
          $wrapperCss .= 'gap:' . $this->escapeHtml($this->getGap()) . ';'; 
        }
        if($this->getTextColor()){
          $wrapperCss .= 'color:' .  $this->escapeHtml($this->getTextColor()) . ';'; 
        }
        if($wrapperCss){
          $styles .= '#' . $id . '.a2-countdown{' . $wrapperCss . '}';
        }
        if($wrapperCssMobile){
          $mobileStyles .= '#' . $id . '.a2-countdown{' . $wrapperCssMobile . '}';
        }
        
        if($this->getPadding()){
          $sectionCss .= 'padding:' . $this->escapeHtml($this->getPadding()) . ';'; 
        }
        if($this->getBgColor()){
          $sectionCss .= 'background-color:' .  $this->escapeHtml($this->getBgColor()) . ';'; 
        }
        if($sectionCss){
          $styles .= '#' . $id . '.a2-countdown > div{' . $sectionCss . '}';
        }
        $fontSize = $this->getNumFontSize();
        if($fontSize){
          $fontSizeMobile = $this->getNumFontSizeMobile();
          if($fontSizeMobile){
            $vw = (int)$fontSize / 16;
            $fontSize = 'clamp(' . (int)$fontSizeMobile . 'px,' . $vw . 'vw,' . (int)$fontSize . 'px)';
            $numCssMobile .= 'font-size:' . (int)$fontSizeMobile . 'px;'; 
          }
          $numCss .= 'font-size:' . $fontSize . ';';
        }
        if($this->getNumFontWeight()){
          $numCss .= 'font-weight:' . (int)$this->getNumFontWeight() . ';'; 
        }
        if($this->getNumWidth()){
          $numCss .= 'min-width:' . (int)$this->getNumWidth() . 'px;'; 
        }
        if($numCss){
          $styles .= '#' . $id . ' .num{' . $numCss . '}';
        }
        if($numCssMobile){
          $mobileStyles .= '#' . $id . ' .num{' . $numCssMobile . '}';
        }

        if($this->getShowSeparator()){
          $separator = '';
          $separatorFontSize = $this->getSepFontSize() ? ((int)$this->getSepFontSize() . 'px') : $fontSize;
          if($separatorFontSize){
            $separator .= 'font-size:' . $separatorFontSize . ';'; 
          }
          if($this->getNumFontWeight()){
            $separator .= 'font-weight:' . (int)$this->getNumFontWeight() . ';'; 
          }
          if(!$this->getHideText()){
            $separator .= 'margin-bottom:10px;'; 
          }
          if($separator){
            $styles .= '#' . $id . ' .separator{' . $separator . '}';
          }
        }
        if($mobileStyles){
          $styles .= '@media (max-width: 639px){' . $mobileStyles . '}';
        }
        return $styles;
        
    }
}
