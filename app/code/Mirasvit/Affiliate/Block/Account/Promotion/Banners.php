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

namespace Mirasvit\Affiliate\Block\Account\Promotion;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\View\Element\Template;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Api\Data\BannerInterface;
use Mirasvit\Affiliate\Repository\BannerRepository;
use Mirasvit\Affiliate\Service\AnalyticsService;
use Mirasvit\Affiliate\Service\BannerService;
use Mirasvit\Affiliate\Service\SessionService;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Banners extends Template
{
    private $bannerRepository;

    private $sessionService;

    private $searchCriteriaBuilder;

    public $analyticsService;

    public $bannerManagement;

    public function __construct(
        BannerRepository $bannerRepository,
        AnalyticsService $analyticsService,
        BannerService $bannerManagement,
        SessionService $sessionService,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Template\Context $context
    ) {
        $this->bannerRepository = $bannerRepository;
        $this->analyticsService = $analyticsService;
        $this->bannerManagement = $bannerManagement;

        $this->sessionService        = $sessionService;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;

        parent::__construct($context);
    }

    /**
     * @return BannerInterface[]
     */
    public function getList(): array
    {
        $banners = $this->bannerRepository->getCollection()
            ->addStoreFilter()
            ->addFieldToFilter(BannerInterface::IS_ACTIVE, 1);

        return $banners->getItems();
    }

    public function getAccount(): ?AccountInterface
    {
        return $this->sessionService->getAccount();
    }

    public function getHtmlCode(BannerInterface $banner, AccountInterface $account): string
    {
        return $this->bannerManagement->getHtmlCode($banner, $account);
    }
}
