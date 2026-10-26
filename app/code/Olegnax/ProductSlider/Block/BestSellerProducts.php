<?php

/**
 * Olegnax
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
 * @package     Olegnax_ProductSlider
 * @copyright   Copyright (c) 2023 Olegnax (http://www.olegnax.com/)
 * @license     https://www.olegnax.com/license
 */

namespace Olegnax\ProductSlider\Block;

use DateTime;
use Exception;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\CatalogInventory\Model\Configuration;
use Magento\Framework\App\Http\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Url\Helper\Data;
use Magento\Framework\View\LayoutFactory;
use Magento\Sales\Model\ResourceModel\Report\Bestsellers\Collection;
use Magento\Sales\Model\ResourceModel\Report\Bestsellers\CollectionFactory;
use Magento\Store\Model\ScopeInterface;

class BestSellerProducts extends ProductsByIds
{
    /**
     * @var ProductFactory
     */
    protected $product;
    # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
    # "Optimize Magefan_Blog and Olegnax_Athlete2 (Olegnax_ProductSlider, Olegnax_MegaMenu, InlineCss, Olegnax_ProductLabel, ProductImage) storefront performance":
    # https://github.com/smokeday-com/m/issues/20
    /**
     * @var array|null
     */
    protected $_loadedProductIds = null;

    /**
     * BestSellerProducts constructor.
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param Visibility $catalogProductVisibility
     * @param Context $httpContext
     * @param Data $urlHelper
     * @param ProductFactory $product
     * @param array $data
     * @param LayoutFactory|null $layoutFactory
     * @param Json|null $json
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        Visibility $catalogProductVisibility,
        Context $httpContext,
        Data $urlHelper,
        ProductFactory $product,
        array $data = [],
        LayoutFactory $layoutFactory = null,
        Json $json = null
    ) {
        $this->product = $product;
        parent::__construct($context, $productCollectionFactory, $catalogProductVisibility, $httpContext, $urlHelper,
            $data, $layoutFactory, $json);
    }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    public function getLoadedProductIds()
    {
        # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
        # "Optimize Magefan_Blog and Olegnax_Athlete2 (Olegnax_ProductSlider, Olegnax_MegaMenu, InlineCss, Olegnax_ProductLabel, ProductImage) storefront performance":
        # https://github.com/smokeday-com/m/issues/20
        if ($this->_loadedProductIds !== null) {
            return $this->_loadedProductIds;
        }
        $categoryIds = $this->getCategoryIds();
        $checkStock = (bool)(
            $this->_scopeConfig->getValue(
                Configuration::XML_PATH_SHOW_OUT_OF_STOCK,
                ScopeInterface::SCOPE_STORE
            ) && $this->getShowInStock()
        );
        $productsCount = (int)$this->getProductsCount();
        $cacheKey = 'OLEGNAX_BESTSELLER_IDS_'
            . $this->getStoreId() . '_'
            . implode('-', $categoryIds) . '_'
            . (int)$checkStock . '_'
            . $productsCount;
        $cached = $this->_cache->load($cacheKey);
        if (!empty($cached)) {
            $decoded = json_decode($cached, true);
            if (is_array($decoded)) {
                $this->_loadedProductIds = $decoded;
                return $this->_loadedProductIds;
            }
        }

        $productIds = [];
        /** @var Collection $collection */
        $collection = $this->_loadObject(CollectionFactory::class)->create();

        $collection->setPeriod('month');
        if (method_exists($collection, 'addStoreRestrictions')) {
            $collection->addStoreRestrictions([0, $this->getStoreId()]);
        }
        $now = new \DateTime();
        $bestsellers = $collection
            ->addFieldToFilter('period', ['lteq' => $now->format('Y-m-d')])
            ->addFieldToFilter('period', ['gteq' => date("Y-m-d", strtotime("-6 month"))])
            ->addFieldToFilter('product_price', array(['gt' => 9.99]))
            ->setPageSize(20);

        $candidateIds = [];
        foreach ($bestsellers as $_product) {
            $id = (int)$_product->getProductId();
            if ($id > 0 && !in_array($id, $candidateIds, true)) {
                $candidateIds[] = $id;
            }
        }

        if (!empty($candidateIds)) {
            $connection = $collection->getConnection();
            $validCatMap = null;
            if (!empty($categoryIds)) {
                $validCatProductIds = $connection->fetchCol(
                    $connection->select()
                        ->distinct()
                        ->from($collection->getTable('catalog_category_product'), ['product_id'])
                        ->where('product_id IN (?)', $candidateIds)
                        ->where('category_id IN (?)', $categoryIds)
                );
                $validCatMap = array_flip(array_map('intval', $validCatProductIds));
            }
            $inStockMap = null;
            if ($checkStock) {
                $inStockProductIds = $connection->fetchCol(
                    $connection->select()
                        ->from($collection->getTable('cataloginventory_stock_status'), ['product_id'])
                        ->where('product_id IN (?)', $candidateIds)
                        ->where('stock_status = ?', 1)
                );
                $inStockMap = array_flip(array_map('intval', $inStockProductIds));
            }
            foreach ($candidateIds as $id) {
                if ($validCatMap !== null && !isset($validCatMap[$id])) {
                    continue;
                }
                if ($inStockMap !== null && !isset($inStockMap[$id])) {
                    continue;
                }
                $productIds[] = $id;
                if ($productsCount && count($productIds) >= $productsCount) {
                    break;
                }
            }
        }

        $this->_cache->save(
            json_encode($productIds),
            $cacheKey,
            [\Magento\Catalog\Model\Product::CACHE_TAG],
            3600
        );
        $this->_loadedProductIds = $productIds;

        return $productIds;
    }

    /**
     * @param string $period
     * @param int $countPeriod
     * @return int
     */
    protected function getDateAgo($period = 'year', $countPeriod = 1)
    {
        if (!in_array($period, ['year', 'month', 'day'])) {
            $period = 'year';
        }

        return (new DateTime())
            ->modify('-' . $countPeriod . ' ' . $period)
            ->setTime(0, 0)
            ->getTimestamp();
    }

    /**
     * @param $id
     * @return bool
     */
    protected function categoryStatus($id)
    {
        $categoryIds = $this->getCategoryIds();
        if (!empty($categoryIds)) {
            try {
                $productCategoryIds = $this->product->create()->load($id)->getCategoryIds();
                return 0 < count(array_intersect($categoryIds, $productCategoryIds));
            } catch (Exception $exception) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array|mixed|null
     */
    protected function getCategoryIds()
    {
        $categoryIds = $this->getData('category_ids');
        if (!is_array($categoryIds)) {
            if ($categoryIds) {
                $categoryIds = is_array($categoryIds) ? $categoryIds : explode(',', (string)$categoryIds);
                $categoryIds = array_map('intval', $categoryIds);
                $categoryIds = array_filter($categoryIds);
                $categoryIds = array_unique($categoryIds);
            } else {
                $categoryIds = [];
            }
            $this->setData('category_ids', $categoryIds);
        }

        return $this->getData('category_ids');
    }

    /**
     * @param $id
     * @return bool|int
     */
    protected function stockStatus($id)
    {
        if ($this->_scopeConfig->getValue(
                Configuration::XML_PATH_SHOW_OUT_OF_STOCK,
                ScopeInterface::SCOPE_STORE
            ) &&
            $this->getShowInStock()) {
            try {
                return $this->stockRegistry->getProductStockStatus($id);
            } catch (Exception $exception) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $newval
     * @return array
     */
    public function getCacheKeyInfo($newval = [])
    {
        return parent::getCacheKeyInfo([
            'OLEGNAX_PRODUCTSLIDER_BESTSELLER_PRODUCTS_LIST_WIDGET',
            implode(',', $this->getCategoryIds()),
            $this->getData('period'),
        ]);
    }

}
