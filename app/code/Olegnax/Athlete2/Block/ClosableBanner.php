<?php

namespace Olegnax\Athlete2\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;

class ClosableBanner extends \Olegnax\Athlete2\Block\Template
{
    protected $_cookieManager;
    /**
     * @var DateTime
     */
    protected $dateTime;

    protected $settings;
    protected $name;

    public function __construct(
        Context $context,
        CookieManagerInterface $cookieManager,
        DateTime $dateTime,
        string $name = '',
        array $settings = [],
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->dateTime = $dateTime;
        $this->_cookieManager = $cookieManager;
        $this->settings = $settings;
        $this->name =  $this->getData('oxname') ?: 'above';
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
    
    public function isBannerClosed($cookieValue = 1)
    {
        return ($cookieValue == (int)$this->_cookieManager->getCookie('ox-banner-' . $this->name));
    }

    public function setSettings($settings){
        $this->settings = $settings;
    }

    public function getContentOutput(){
        if ( $this->settings[ 'header_banner_type' ] == 'textfield' ) {
            $output = $this->getBlockTemplateProcessor( $this->settings[ 'header_banner_custom' ] );
        } else {
            $output = $this->getLayout()->createBlock( 'Magento\Cms\Block\Block' )->setBlockId( $this->settings[ 'header_banner_static' ] )->toHtml();
        }	
        if($output){
            
            $endDate = $this->settings[ 'banner_hide_date' ];
            if($this->settings[ 'banner_hide_by_date' ] && $endDate){                
                return str_replace('{{countdown}}', $this->getCounterBlockHtml($endDate), (string)$output);		
            }
            return str_replace('{{countdown}}', '', (string)$output);
           
        }
        return '';
    }

    protected function getCounterBlockHtml($endDate)
    {
        $widgetBlock = $this->getLayout()
            ->createBlock(\Olegnax\Athlete2\Block\Countdown::class)
            ->setData([
                'end_date' => $this->escapeHtml($endDate),
                'day_to_hours' => (bool)$this->settings[ 'banner_days_to_hours' ],
                'hide_element' => '.header-banner-' . $this->escapeHtmlAttr($this->name),
                'hide_class' => 'd-none'
            ]);

        return $widgetBlock->toHtml();
    }
}