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

namespace Mirasvit\Affiliate\Service\Coupon;

use Magento\SalesRule\Api\CouponRepositoryInterface;
use Magento\SalesRule\Api\Data\CouponInterface as SalesCouponInterface;
use Magento\SalesRule\Api\Data\CouponInterfaceFactory as SalesCouponInterfaceFactory;
use Magento\SalesRule\Api\Data\RuleInterface as SalesRuleInterface;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\SalesRule\Model\ResourceModel\Coupon\CollectionFactory as SalesCouponCollectionFactory;

class SalesRuleService
{
    private $salesCouponCollectionFactory;

    private $salesRuleRepository;

    private $couponRepository;

    private $SalesCouponFactory;

    public function __construct(
        SalesCouponCollectionFactory $salesCouponCollectionFactory,
        RuleRepositoryInterface      $salesRuleRepository,
        CouponRepositoryInterface    $couponRepository,
        SalesCouponInterfaceFactory  $SalesCouponFactory
    ) {
        $this->salesCouponCollectionFactory = $salesCouponCollectionFactory;
        $this->salesRuleRepository          = $salesRuleRepository;
        $this->couponRepository             = $couponRepository;
        $this->SalesCouponFactory           = $SalesCouponFactory;
    }

    public function addCoupon(int $salesRuleId, string $couponCode): bool
    {
        $cartRule = $this->salesRuleRepository->getById($salesRuleId);

        /** @var SalesCouponInterface $coupon */
        $coupon = $this->salesCouponCollectionFactory->create()->addFieldToFilter('code', $couponCode)->getFirstItem();
        if (!$coupon->getCouponId()) {
            $coupon = $this->SalesCouponFactory->create();
            $coupon->setCode($couponCode)
                ->setRuleId($cartRule->getRuleId())
                ->setType(1);

            $this->couponRepository->save($coupon);
        }

        return true;
    }

    public function verifyRule(int $ruleId): bool
    {
        try {
            $salesRule = $this->salesRuleRepository->getById($ruleId);
            if ($salesRule->getUseAutoGeneration() && $salesRule->getCouponType() == SalesRuleInterface::COUPON_TYPE_SPECIFIC_COUPON) {
                return true;
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }
}
