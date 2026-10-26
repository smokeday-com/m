<?php

namespace Olegnax\Athlete2\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Olegnax\Athlete2\Helper\Helper;
use Magento\Framework\App\CacheInterface;

class Move implements ObserverInterface
{
	/**
     * @var CacheInterface
     */
	protected $cacheManager;
	/**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var Helper
     */
    private $helper;
	
    public function __construct(
		Helper $helper,
        ScopeConfigInterface $scopeConfig,
		CacheInterface $cacheManager
    )
    {
        $this->scopeConfig = $scopeConfig;
		$this->helper = $helper;
		$this->cacheManager = $cacheManager;
    }

    public function execute(Observer $observer)
    {
        if (!$this->getConfig(Helper::XML_ENABLED)) {
            return;
        }
		
        $layout = $observer->getData('layout');

		/* move minicart in header 1*/
		$headerLayout = $this->getConfig('athlete2_settings/header/header_layout');
		if($headerLayout == 'header_1'){
			$layout->getUpdate()->addHandle('olegnax_athlete2_header_move_minicart');
		}
		if($this->getConfig( 'athlete2_settings/header/language_in_header' )){
			$layout->getUpdate()->addHandle('olegnax_athlete2_remove_topbar_switchers');
		} else{
			if( !$this->getConfig('athlete2_settings/header/language_tiny_drops' ) ){
				$layout->getUpdate()->addHandle('olegnax_athlete2_switcher_topbar_modal');
			}
		}		
		
        $fullActionName = $observer->getData('full_action_name');
		
		$version = $this->helper->getVersion();
		$account_action_drop = $this->getConfig('athlete2_settings/header/account_action');
		if($account_action_drop == 'login' && $fullActionName !='customer_account_login' && $fullActionName !='multishipping_checkout_login'){
			
			if(	version_compare($version, '2.4.7', '>=') ){
				$layout->getUpdate()->addHandle('olegnax_athlete2_login_recaptcha_247');
			}elseif(version_compare($version, '2.4.0', '>') && version_compare($version, '2.4.7', '<')){
				$layout->getUpdate()->addHandle('olegnax_athlete2_login_recaptcha');
            } elseif (version_compare($version, '2.3.1', '=')) {
                $layout->getUpdate()->addHandle('olegnax_athlete2_login_recaptcha_msp_231');
            } else {
                $layout->getUpdate()->addHandle('olegnax_athlete2_login_recaptcha_msp');
            }
		}
		if(	version_compare($version, '2.3.6', '>' ) && version_compare($version, '2.4.0', '!=' ) ){
			$layout->getUpdate()->addHandle('olegnax_athlete2_header_search_args');
		}

		/* Category */
		 if ($fullActionName == 'catalog_category_view' || $fullActionName == 'catalogsearch_result_index' ) {
			if($this->getConfig('athlete2_settings/products_listing/move_cat_title')){
				$layout->getUpdate()->addHandle('olegnax_athlete2_category_move_title');
			}
			if($this->getConfig('athlete2_settings/products_listing/move_cat_cms_block')){
				$layout->getUpdate()->addHandle('olegnax_athlete2_category_move_cms_block');
			}
			/*
			if($this->getConfig('athlete2_settings/products_listing/move_breadrumbs')){
				$layout->getUpdate()->addHandle('olegnax_athlete2_category_move_breadcrumbs');
			}
			if($this->getConfig('athlete2_settings/products_listing/move_image')){
				$layout->getUpdate()->addHandle('olegnax_athlete2_category_move_image');
			}
			if($this->getConfig('athlete2_settings/products_listing/move_desc')){
				$layout->getUpdate()->addHandle('olegnax_athlete2_category_move_desc');
			}*/
		 }

		 /* Print */
		 if ($fullActionName == 'sales_order_print' ) {
			$layout->getUpdate()->addHandle('olegnax_remove_newsletter');
		 }

		/* Cart Page */
		if ($fullActionName == 'checkout_cart_index' ) {
			/* Free shipping bar position on acrt page */
			if($this->getConfig('athlete2_settings/shipping_bar/cart_page_enable') && $this->getConfig('athlete2_settings/shipping_bar/minicart_enable')){
				$freeShippingCartPosition  = $this->getConfig('athlete2_settings/shipping_bar/cart_page_position');
				if( $freeShippingCartPosition == 'top'){
					$layout->getUpdate()->addHandle('olegnax_athlete2_free_shipping_cart_page_top');
				} elseif($freeShippingCartPosition == 'summary'){
					$layout->getUpdate()->addHandle('olegnax_athlete2_free_shipping_cart_page_summary');
				}
			}				
		}
		
		/* Product Page */
		 if (!in_array($fullActionName, ['catalog_product_view', 'ox_quickview_catalog_product_view'])) {
			 return $this;			 
		 }
			$productConfig =  $this->getConfig('athlete2_settings/product');
		 	$productPageHandles = [];
			/* move reviews */
			$reviewsInTab = $productConfig['reviews_position'];
			if ($reviewsInTab) {
				if($reviewsInTab == 'oxbottom'){
					$productPageHandles[] = 'olegnax_athlete2_catalog_product_view_review_oxbottom';
				} elseif($reviewsInTab == 'bottom'){
					$productPageHandles[] = 'olegnax_athlete2_catalog_product_view_review_bottom';
				} elseif($reviewsInTab == 'gallery'){
					$productPageHandles[] = 'olegnax_athlete2_catalog_product_view_review_gallery';
				}
			} else{
				$productPageHandles[] = 'olegnax_athlete2_catalog_product_view_move_review';
			}
			if($this->getConfig('athlete2_settings/products_listing/show_price_diff')){
				$prod_bar = $this->getConfig('athlete2_settings/product_bar/enabled');
				if($this->getConfig('athlete2_settings/products_listing/show_price_diff_pos') === 'after'){
					$productPageHandles[] = 'olegnax_athlete2_catalog_product_view_price_diff_after';
					if($prod_bar){
						$productPageHandles[] = 'olegnax_athlete2_catalog_product_bar_price_diff_after';
					}
				 } else{
					$productPageHandles[] = 'olegnax_athlete2_catalog_product_view_price_diff_before';
					if($prod_bar){
						$productPageHandles[] = 'olegnax_athlete2_catalog_product_bar_price_diff_before';
					}
				 }
			}
			if( $productConfig['disable_sticky_header']){
				$productPageHandles[] = 'olegnax_athlete2_catalog_product_disable_sticky_header';
			}

			if($productConfig['product_social_min'] == 'title'){
				$productPageHandles[] = 'olegnax_athlete2_catalog_product_view_share_title';
			}

			/* stock info */
			if($this->getConfig('athlete2_settings/product_stock/enable')){
				$stockPosition = $this->getConfig('athlete2_settings/product_stock/position');
				if($stockPosition === 'below_cart'){
					$productPageHandles[] = 'olegnax_athlete2_catalog_product_stock_after_cart';
				} elseif($stockPosition === 'below_actions'){
					$productPageHandles[] = 'olegnax_athlete2_catalog_product_stock_after_actions';
				} elseif($stockPosition === 'below_price'){
					$productPageHandles[] = 'olegnax_athlete2_catalog_product_stock_after_price';
				} elseif($stockPosition === 'original'){
					$productPageHandles[] = 'olegnax_athlete2_catalog_product_stock_original';
				}
			}

			$tabsInInfo = $productConfig['product_tabs_position'];
			/*if($fullActionName == 'ox_quickview_catalog_product_view' && $tabsInInfo == 'info'){
				$layout->getUpdate()->addHandle('olegnax_athlete2_catalog_product_tabs_remove');
			}*/

			
			if ($tabsInInfo == 'info') {
				$productPageHandles[] = 'olegnax_athlete2_catalog_product_tabs_right';
			} 
			if($tabsInInfo == 'oxbottom'){
				$productPageHandles[] = 'olegnax_athlete2_catalog_product_tabs_oxbottom';
			} 
			if($tabsInInfo == 'bottom'){
				$productPageHandles[] = 'olegnax_athlete2_catalog_product_tabs_bottom';
			}
			if($tabsInInfo == 'gallery'){
				$productPageHandles[] = 'olegnax_athlete2_catalog_product_tabs_gallery';
			}
			/* move related */
			$moveRelated = $productConfig['related_positon'];
			$moveUpsell  = $productConfig['upsell_positon'];
			if ($moveRelated == 'oxbottom') {
				$productPageHandles[] = 'olegnax_athlete2_catalog_product_related_oxbottom';
			} elseif($moveRelated == 'bottom'){
				$productPageHandles[] = 'olegnax_athlete2_catalog_product_related_bottom';
			} elseif($moveRelated == 'gallery'){
				$productPageHandles[] = 'olegnax_athlete2_catalog_product_related_gallery';
			}
			if ($moveUpsell == 'oxbottom') {
				$productPageHandles[] = 'olegnax_athlete2_catalog_product_upsell_oxbottom';
			} elseif($moveUpsell == 'bottom'){
				$productPageHandles[] = 'olegnax_athlete2_catalog_product_upsell_bottom';
			} elseif($moveUpsell == 'gallery'){
				$productPageHandles[] = 'olegnax_athlete2_catalog_product_upsell_gallery';
			}
			/* sticky product, move elements in sticky wrapper */
			$galleryLayout  = $productConfig['gallery_layout'];
			$stickyDesc  = $productConfig['gallery_sticky'];
			$infoWrapper  = $productConfig['gallery_wrapper'];
			if(($stickyDesc && ($galleryLayout == '1col' || $galleryLayout == '2cols')) || $infoWrapper){
				$productPageHandles[] = 'olegnax_athlete2_catalog_product_info_wrapper';
			}
			if($this->getConfig('athlete2_settings/product_quick_nav/enabled') && $this->getConfig('athlete2_settings/product_quick_nav/position')){
				if($infoWrapper){
					$productPageHandles[] = 'olegnax_athlete2_catalog_product_quicknav_after_wrapper';
				} else{
					$productPageHandles[] = 'olegnax_athlete2_catalog_product_quicknav_after_info';
				}
			}

			/* replace fotorama with css only gallery for mobile theme */
			if( $productConfig['css_only_gallery']){
				if($this->helper->isMobileTheme()){
					$productPageHandles[] = 'olegnax_athlete2_css_only_gallery';
					$productPageHandles[] = 'olegnax_athlete2_remove_fotorama_video';
				}
			}
			if($fullActionName == 'catalog_product_view' && ( $galleryLayout == '1col' || $galleryLayout == '2cols')){
				/* remove fotorama video if fotorama disabled */
				$productPageHandles[] = 'olegnax_athlete2_remove_fotorama_video';
				/* set product gallery layout */
				$productPageHandles[] = 'olegnax_athlete2_product_gallery_layout';
			}
			$update = $layout->getUpdate();
			foreach ($productPageHandles as $handle) {
				$update->addHandle($handle);
			}
		
        return $this;
    }

    public function getConfig($path, $storeCode = null)
    {
        $value = $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeCode);
        if (is_null($value)) {
            $value = '';
        }
        return $value;
    }
}
