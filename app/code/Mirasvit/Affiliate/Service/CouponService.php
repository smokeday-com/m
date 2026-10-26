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

namespace Mirasvit\Affiliate\Service;

use Magento\SalesRule\Api\Data\CouponInterface as SalesCouponInterface;
use Magento\SalesRule\Api\RuleRepositoryInterface as SalesRuleRepositoryInterface;
use Magento\SalesRule\Model\ResourceModel\Coupon\CollectionFactory as SalesCouponCollectionFactory;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Api\Data\CouponInterface;
use Mirasvit\Affiliate\Api\Data\ProgramInterface;
use Mirasvit\Affiliate\Repository\CouponRepository;
use Mirasvit\Affiliate\Repository\ProgramRepository;
use Mirasvit\Affiliate\Service\Coupon\SalesRuleService;

class CouponService
{
    public const COUPON_STATUS_AVAILABLE = 'available';
    public const COUPON_STATUS_INACTIVE  = 'inactive';
    public const COUPON_STATUS_REMOVED   = 'removed';

    private $salesCouponCollectionFactory;

    private $couponRepository;

    private $programRepository;

    private $programService;

    private $couponGenerateService;

    private $salesRuleRepository;

    private $salesRuleService;

    public function __construct(
        SalesRuleRepositoryInterface $salesRuleRepository,
        SalesCouponCollectionFactory $salesCouponCollectionFactory,
        ProgramRepository            $programRepository,
        ProgramService               $programService,
        CouponGenerateService        $couponGenerateService,
        CouponRepository             $couponRepository,
        SalesRuleService             $salesRuleService
    ) {
        $this->salesRuleRepository          = $salesRuleRepository;
        $this->salesCouponCollectionFactory = $salesCouponCollectionFactory;
        $this->programRepository            = $programRepository;
        $this->programService               = $programService;
        $this->couponGenerateService        = $couponGenerateService;
        $this->couponRepository             = $couponRepository;
        $this->salesRuleService             = $salesRuleService;
    }

    public function updateCouponCode(CouponInterface $coupon, string $code): void
    {
        $program = $this->programRepository->get($coupon->getProgramId());
        if ($program) {
            $this->salesRuleService->addCoupon($program->getCartRuleId(), $code);
            $coupon->setCode($code);
            $this->couponRepository->save($coupon);
        }
    }

    public function ensureCoupon(AccountInterface $account, ProgramInterface $program): void
    {
        $salesRuleId = $program->getCartRuleId();
        if (!$this->salesRuleService->verifyRule($salesRuleId)) {
            return;
        }

        $salesRule = $this->salesRuleRepository->getById($program->getCartRuleId());
        if (!$this->programService->checkBelongingSameSite($salesRule, $program)) {
            return;
        }

        $coupon = $this->getLastAvailableCoupon($account, $program);

        /** @var SalesCouponInterface $salesCoupon */
        $salesCoupon = $this->salesCouponCollectionFactory->create()
            ->addFieldToFilter('code', $coupon->getCode())
            ->addFieldToFilter('rule_id', $program->getCartRuleId())
            ->getFirstItem();

        // $coupon->getCode() - to resolve the issue with already existed coupons with empty codes
        if (!$coupon->getCode() || !$salesCoupon->getCouponId()) {
            $couponCode = $this->couponGenerateService->generateCode();
            $newCoupon  = $this->couponRepository->create()
                ->setAccountId($account->getId())
                ->setProgramId($program->getId())
                ->setCode($couponCode);

            $this->salesRuleService->addCoupon($program->getCartRuleId(), $couponCode);

            $newCoupon->setCode($couponCode);
            $this->couponRepository->save($newCoupon);
        }
    }

    public function getCouponStatus(CouponInterface $coupon): string
    {
        /** @var SalesCouponInterface $salesCoupon */
        $salesCoupon = $this->salesCouponCollectionFactory->create()
            ->addFieldToFilter('code', $coupon->getCode())
            ->getFirstItem();
        if (!$salesCoupon->getCouponId()) {
            return self::COUPON_STATUS_REMOVED;
        }

        $program = $this->programRepository->get($coupon->getProgramId());
        if (!$program) {
            return self::COUPON_STATUS_INACTIVE;
        }

        try {
            $salesRule = $this->salesRuleRepository->getById($program->getCartRuleId());
        } catch (\Exception $e) {
            return self::COUPON_STATUS_INACTIVE;
        }

        if (!$this->programService->checkBelongingSameSite($salesRule, $program)) {
            return self::COUPON_STATUS_INACTIVE;
        }

        if ($salesRule->getRuleId() != $program->getCartRuleId()) {
            return self::COUPON_STATUS_INACTIVE;
        }

        if (!$program->getCouponIsActive()) {
            return self::COUPON_STATUS_INACTIVE;
        }

        if ($program->getCouponIsActive()) {
            return self::COUPON_STATUS_AVAILABLE;
        }

        return '';
    }

    public function getLastAvailableCoupon(AccountInterface $account, ProgramInterface $program): CouponInterface
    {
        $collection = $this->couponRepository->getCollection()
            ->addFieldToFilter(CouponInterface::ACCOUNT_ID, $account->getId())
            ->addFieldToFilter(CouponInterface::PROGRAM_ID, $program->getId())
            ->setOrder(CouponInterface::ID, 'DESC');

        foreach ($collection as $coupon) {
            if ($this->getCouponStatus($coupon) == self::COUPON_STATUS_AVAILABLE) {
                return $coupon;
            }
        }

        return $this->couponRepository->create();
    }

    public function isCouponAvailable(string $couponCode): bool
    {
        /** @var CouponInterface $coupon */
        $coupon = $this->couponRepository->getCollection()
            ->addFieldToFilter(CouponInterface::CODE, $couponCode)
            ->getFirstItem();

        if ($coupon->getId()) {
            $couponStatus = $this->getCouponStatus($coupon);
            if ($couponStatus == CouponService::COUPON_STATUS_AVAILABLE) {
                return true;
            }
        }

        return false;
    }
}
