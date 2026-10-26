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

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Framework\View\Element\Template;
use Magento\SalesRule\Api\Data\RuleInterface as SalesRuleInterface;
use Magento\SalesRule\Api\RuleRepositoryInterface as SalesRuleRepositoryInterface;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Api\Data\CouponInterface;
use Mirasvit\Affiliate\Api\Data\ProgramInterface;
use Mirasvit\Affiliate\Repository\CouponRepository;
use Mirasvit\Affiliate\Repository\ProgramRepository;
use Mirasvit\Affiliate\Service\CouponService;
use Mirasvit\Affiliate\Service\SessionService;

class Coupon extends Template
{
    private $couponRepository;

    private $couponService;

    private $programRepository;

    private $salesRuleRepository;

    private $sessionService;

    private $pricingHelper;

    public function __construct(
        SalesRuleRepositoryInterface $salesRuleRepository,
        CouponRepository             $couponRepository,
        CouponService                $couponService,
        ProgramRepository            $programRepository,
        SessionService               $sessionService,
        PricingHelper                $pricingHelper,
        Template\Context             $context
    ) {
        $this->couponRepository    = $couponRepository;
        $this->couponService       = $couponService;
        $this->programRepository   = $programRepository;
        $this->salesRuleRepository = $salesRuleRepository;
        $this->sessionService      = $sessionService;
        $this->pricingHelper       = $pricingHelper;

        parent::__construct($context);
    }

    public function getAccount(): ?AccountInterface
    {
        return $this->sessionService->getAccount();
    }

    /**
     * @return ProgramInterface[]
     */
    public function getProgramsList(): array
    {
        $programCollection = $this->programRepository->getCollection()
            ->addActiveFilter()
            ->addFieldToFilter(ProgramInterface::TYPE, ProgramInterface::TYPE_PAY_PER_SALE)
            ->addFieldToFilter(ProgramInterface::COUPON_IS_ACTIVE, 1)
            ->addStoreFilter()
            ->setOrder(ProgramInterface::PRIORITY);

        $assignedProgramsIds = $this->getAccount()->getProgramIds();
        if (!empty($assignedProgramsIds)) {
            $programCollection->addFieldToFilter(ProgramInterface::ID, $assignedProgramsIds);
        } else {
            $programCollection->addFieldToFilter(ProgramInterface::IS_VISIBLE, true);
        }

        return $programCollection->getItems();
    }

    public function getCoupon(ProgramInterface $program): CouponInterface
    {
        $account = $this->getAccount();
        if (!$account) {
            return $this->couponRepository->create();
        }

        return $this->couponService->getLastAvailableCoupon($account, $program);
    }

    public function getSalesRule(ProgramInterface $program): ?SalesRuleInterface
    {
        $salesRuleId = $program->getCartRuleId();

        try {
            return $this->salesRuleRepository->getById($salesRuleId);
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    public function getDiscountAmountHtml(SalesRuleInterface $salesRule): string
    {
        if ($salesRule->getSimpleAction() == SalesRuleInterface::DISCOUNT_ACTION_BY_PERCENT) {
            return (string)__('%1%', round((float)$salesRule->getDiscountAmount(), 2));
        }

        return (string)__('%1', $this->pricingHelper->currency($salesRule->getDiscountAmount(), true, false));
    }
}
