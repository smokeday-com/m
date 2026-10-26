<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-affiliate
 * @version   1.5.2
 * @copyright Copyright (C) 2024 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\Affiliate\Block;

use DateTimeFactory;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\Affiliate\Model\ConfigProvider;
use Mirasvit\Affiliate\Service\BannerService;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Widget extends AbstractProduct
{
    private $configProvider;

    private $dateTimeFactory;

    private $productCollectionFactory;

    private $storeManager;

    private $bannerService;

    private $productRepository;

    private $searchCriteriaBuilder;

    private $sortOrderBuilder;

    private $filterGroupBuilder;

    private $filterBuilder;

    private $resource;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ConfigProvider             $configProvider,
        DateTimeFactory            $dateTimeFactory,
        ProductCollectionFactory   $productCollectionFactory,
        ResourceConnection         $resource,
        StoreManagerInterface      $storeManager,
        BannerService              $bannerService,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder      $searchCriteriaBuilder,
        SortOrderBuilder           $sortOrderBuilder,
        FilterGroupBuilder         $filterGroupBuilder,
        FilterBuilder              $filterBuilder,
        Context                    $context
    ) {
        $this->configProvider           = $configProvider;
        $this->dateTimeFactory          = $dateTimeFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->resource                 = $resource;
        $this->storeManager             = $storeManager;
        $this->bannerService            = $bannerService;
        $this->productRepository        = $productRepository;
        $this->searchCriteriaBuilder    = $searchCriteriaBuilder;
        $this->sortOrderBuilder         = $sortOrderBuilder;
        $this->filterGroupBuilder       = $filterGroupBuilder;
        $this->filterBuilder            = $filterBuilder;

        parent::__construct($context);
    }

    public function getList(): SearchResultsInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder;

        $statusGroup = $this->filterGroupBuilder
            ->addFilter(
                $this->filterBuilder->setField(ProductInterface::STATUS)
                    ->setConditionType('eq')
                    ->setValue(1)
                    ->create()
            )->create();

        $visibilityGroup = $this->filterGroupBuilder
            ->addFilter(
                $this->filterBuilder->setField(ProductInterface::VISIBILITY)
                    ->setConditionType('eq')
                    ->setValue(4)
                    ->create()
            )->create();

        if ($this->getSortType() == 'bestseller') {
            $searchGroup = $this->filterGroupBuilder
                ->addFilter(
                    $this->filterBuilder->setField('entity_id')
                        ->setConditionType('in')
                        ->setValue($this->getBestsellersIds())
                        ->create()
                )->create();

            $sort = $this->sortOrderBuilder
                ->setField(ProductInterface::NAME)
                ->setAscendingDirection()
                ->create();

            $searchCriteria->setFilterGroups([$statusGroup, $visibilityGroup, $searchGroup]);
        } elseif ($this->getSortType() == 'new') {
            $sort = $this->sortOrderBuilder->setField(ProductInterface::CREATED_AT)
                ->create();
            $searchCriteria->setFilterGroups([$statusGroup, $visibilityGroup]);
        } else {
            $sort = $this->sortOrderBuilder
                ->setField(ProductInterface::UPDATED_AT)
                ->create();

            $searchGroup = $this->filterGroupBuilder
                ->addFilter(
                    $this->filterBuilder->setField(ProductInterface::NAME)
                        ->setConditionType('like')
                        ->setValue('%' . $this->getSortType() . '%')
                        ->create()
                )->create();

            $searchCriteria->setFilterGroups([$statusGroup, $visibilityGroup, $searchGroup]);
        }

        $searchCriteria->setPageSize($this->getPageSize())
            ->setSortOrders([$sort]);

        return $this->productRepository->getList($searchCriteria->create());
    }

    public function getPageSize(): int
    {
        $size = $this->getRequest()->getParam('number', 9);
        $size = $size > 50 ? 50 : $size;

        return (int)$size;
    }

    public function getTitle(): string
    {
        return (string)$this->getRequest()->getParam('title');
    }

    public function isShowName(): bool
    {
        return (bool)$this->getRequest()->getParam('show_name');
    }

    public function isShowPrice(): bool
    {
        return (bool)$this->getRequest()->getParam('show_price');
    }

    public function getSortType(): string
    {
        if ($this->getRequest()->getParam('bestseller')) {
            return 'bestseller';
        } elseif ($this->getRequest()->getParam('new')) {
            return 'new';
        }

        return (string)$this->getRequest()->getParam('search');
    }

    /**
     * {@inheritDoc}
     */
    public function getProductUrl($product, $additional = [])
    {
        $url        = parent::getProductUrl($product, $additional);
        $identifier = (string)$this->getRequest()->getParam('identifier');
        $url        = $this->bannerService->gerReferralUrl($identifier, $url);

        return $url;
    }

    public function isM245orGreater(): bool
    {
        $version = \Mirasvit\Core\Service\CompatibilityService::getVersion();

        return version_compare($version, '2.4.5', '>=');
    }

    private function getBestsellersIds(): array
    {
        $storeId        = (int)$this->storeManager->getStore()->getId();
        $orderItemTable = $this->resource->getTableName('sales_order_item');

        $productCollection = $this->productCollectionFactory->create()
            ->addAttributeToFilter('visibility', Visibility::VISIBILITY_BOTH)
            ->addAttributeToFilter('status', Status::STATUS_ENABLED);
        $productCollection->getSelect()
            ->joinInner(['item' => $orderItemTable], 'e.entity_id=item.product_id', [])
            ->where('item.store_id = ?', $storeId)
            ->where('item.created_at > ?', $this->getBestsellersDateFrom())
            ->group('e.entity_id')
            ->reset(Select::COLUMNS)
            ->columns('e.entity_id')
            ->order('SUM(item.qty_ordered) DESC')
            ->limit($this->getPageSize());

        $bestSellersIds = [];
        foreach ($productCollection as $product) {
            $bestSellersIds[] = (int)$product->getId();
        }

        return $bestSellersIds;
    }

    private function getBestsellersDateFrom(): string
    {
        $days     = $this->configProvider->getBestsellersLastDays();
        $modifier = '-' . $days . ' day';
        $date     = $this->dateTimeFactory->create()->modify($modifier);

        return $date ? $date->format('Y-m-d') : '1970-01-01';
    }
}
