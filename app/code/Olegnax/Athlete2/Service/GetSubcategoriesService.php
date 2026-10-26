<?php

declare(strict_types=1);

namespace Olegnax\Athlete2\Service;

use Magento\Catalog\Model\CategoryFactory;

class GetSubcategoriesService
{
    private $_category;
    private $_categories;
    protected $_categoryFactory;

    public function __construct(
        CategoryFactory $_categoryFactory
    ) {
        $this->_categoryFactory = $_categoryFactory;
    }

    public function setCategory($category){
        $this->_category = $category;
        return $this;
    }
    public function setCategories($categories){
        $this->_categories = $categories;
        return $this;
    }

    public function getSubcategories()
    {
        $subCats = [];
       
        if(is_array($this->_categories) && count($this->_categories)){
            $subCats = $this->loadCategoriesByIds();
        }elseif($this->_category){
            $subCats = $this->loadSubcategories();
        }

        return $subCats;
    }
    
    /**
     * @return Collection
     */
    protected function loadCategoriesByIds(){

        $collection = $this->_categoryFactory->create()->getCollection()
        ->setLoadProductCount(true)
        ->addAttributeToSelect(['image', 'name', 'url_key', 'ox_category_thumb'])
        ->addAttributeToFilter('is_active', 1)
        ->addFieldToFilter('entity_id', ['in' => $this->_categories]);

        // sort by input order
        $collection->getSelect()->order(
            'FIELD(e.entity_id, ' . implode(
                ",",
                array_filter($this->_categories)
            ) . ')'
        );

        return $collection;
    }
    
    protected function loadSubcategories(){
        return $this->_category->getCollection()
            ->setLoadProductCount(true)
            ->addAttributeToSelect(['image', 'name', 'url_key', 'ox_category_thumb'])
            ->addAttributeToFilter('is_active', 1)
            ->addIdFilter($this->_category->getChildren())
            ->addAttributeToSort('position')
            ->load();
    }
}