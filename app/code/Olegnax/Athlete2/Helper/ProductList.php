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
 * @copyright   Copyright (c) 2024 Olegnax (http://www.olegnax.com/)
 * @license     https://www.olegnax.com/license
 */

namespace Olegnax\Athlete2\Helper;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Olegnax\Athlete2\Service\GetCurrentCategoryService;
use Olegnax\Core\Helper\Helper as CoreHelper;

class ProductList extends Helper
{
    /**
    * @var CoreHelper
    */  
    protected $coreHelper;
    /**
    * @var Json
    */
    protected $_json;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    
    protected $banners;
    protected $filterToggles;
    protected $allFiltersClosed;
    protected $category;

    /**
     * @var GetCurrentCategoryService
     */
    private $currentCategoryService;

    public function __construct(
        Context $context,
        CoreHelper $coreHelper,
        StoreManagerInterface $storeManager,
        GetCurrentCategoryService $currentCategoryService,
        Json $json,
        array $banners = ['grid' => [], 'list' => []]
    ) {
        $this->storeManager = $storeManager;
        parent::__construct($context, $coreHelper);
        $this->currentCategoryService = $currentCategoryService;
        $this->_json = $json;
        $this->banners = $banners;
    }

    /**
     * @param Product $product
     * @return Category|null
     */
    public function getLastCategory($product)
    {
        /** @var Collection $categoryCollection */
        $categoryCollection = $product->getCategoryCollection()
            ->addAttributeToSelect('name')
            ->addAttributeToFilter('is_active', '1');
        if ($rootCategoryId = $this->getRootCategoryId()) {
            $categoryCollection->addFieldToFilter('path', ['like' => '1/' . $rootCategoryId . '%']);
        }
        $category = null;
        if ($categoryCollection->getSize()) {
            $category = null;
            /** @var Category $_category */
            foreach ($categoryCollection as $_category) {
                if (!empty($_category)) {
                    $size_path = count(explode('/', (string)$_category->getPath()));
                }
                $_size_path = 0;
                if (!empty($category)) {
                    $_size_path = count(explode('/', (string)$category->getPath()));
                }
                if ($_size_path < $size_path) {
                    $category = $_category;
                }
            }
        }
        return $category;
    }

    /**
     * @return int
     */
    protected function getRootCategoryId()
    {
        return $this->storeManager->getGroup()->getRootCategoryId();
    }
    
    public function getBannersData($category = null, $view = 'grid'){
        $settings = $this->getConfig('athlete2_settings/products_listing_banners');
        if (!$settings[ 'enable_' .  $view ]){
            return '';
        }
        
        $categoryId = $category ?: $this->getCategoryId();
        if(!$categoryId){
            return '';
        }

        if(!array_key_exists($categoryId, $this->banners[$view])){
            $bannersData = [];
            $banners = $settings[ 'banners_by_block_' . $view ];
            $bannersData[$view] = empty($banners) ? [] : $this->_json->unserialize($banners);
            if(!empty($bannersData[$view])){
                $this->banners[$view][$categoryId] = $this->organizeArrayByCategory($bannersData[$view], $categoryId);
                return $this->banners[$view][$categoryId];
            }
        } else {
            return $this->banners[$view][$categoryId];
        }
        return '';
    }

    /* get list of banners for current category */
    protected function organizeArrayByCategory($inputArray, $categoryId)
    {
        $resultArray = [];

        foreach ($inputArray as $key => $data) {
            $categoryIds = explode(',', $data['category_ids']);

            if (in_array($categoryId, $categoryIds)) {
                unset($data['category_ids']); // Remove category_ids from the result array

                // Check if the category ID already exists in the result array
                if (!isset($resultArray[$categoryId])) {
                    $resultArray[$categoryId] = [];
                }

                // Append the data to the result array under the category ID
                $resultArray[$categoryId][$data['sort_order']] = $data['block'];
            }
        }
        if(array_key_exists($categoryId, $resultArray)){
            return $resultArray[$categoryId];
        }

        return $resultArray;
    }

    private function getCategoryId()
    {
        if (!$this->category) {
            $this->category = $this->currentCategoryService->getCategoryId();
        }

        return $this->category;
    }
    
    private function getFilterTogglesData(){
        if(!$this->filterToggles){
            $toggles = $this->getConfig('athlete2_settings/products_listing/filters_opened_by_attribute');
            $togglesDataArray = empty($toggles) ? [] : $this->_json->unserialize($toggles);
            $attrIds = [];
            if(is_array($togglesDataArray) && !empty($togglesDataArray)){
                foreach ($togglesDataArray as $item) {
                    if (isset($item['id'])) {
                        $attrIds[] = $item['id'];
                    }
                }
            }

            $this->filterToggles = $attrIds;            
        }
        return $this->filterToggles;
    }

    public function isFilterAttrOpened($code){
       
        if(!$this->filterToggles){
            $this->getFilterTogglesData();
        }
        return (is_array($this->filterToggles) && !empty($this->filterToggles)) ? in_array($code, $this->filterToggles) : false;
    }

    public function isFilterToggleClosed($filter){
        $opened = true;
        if($this->allFiltersClosed === null){
            $this->allFiltersClosed = $this->getConfig('athlete2_settings/products_listing/filters_closed');
        }
        if($this->allFiltersClosed){
            $opened = false;
            if($filter instanceof \Magento\CatalogSearch\Model\Layer\Filter\Category){
                $opened = $this->getConfig('athlete2_settings/products_listing/filters_opened_categories');
            }else{
                $opened = $this->isFilterAttrOpened($filter->getRequestVar());
            }
        }
        return !$opened;
    }
}
