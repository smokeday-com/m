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

namespace Mirasvit\Affiliate\Plugin;

use Magento\Framework\Exception\CouldNotSaveException;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Api\Data\ProgramInterface;
use Mirasvit\Affiliate\Repository\ProgramRepository;
use Mirasvit\Affiliate\Service\CouponService;

/**
 * @see \Mirasvit\Affiliate\Repository\AccountRepository::save()
 */
class EnsureCouponsAfterAccountSavePlugin
{
    private $programRepository;

    private $couponService;

    public function __construct(ProgramRepository $programRepository, CouponService $couponService)
    {
        $this->programRepository = $programRepository;
        $this->couponService     = $couponService;
    }

    /**
     * @param mixed $repository
     * @param AccountInterface $account
     *
     * @return AccountInterface
     * @throws CouldNotSaveException
     */
    public function afterSave($repository, AccountInterface $account)
    {
        if ($account->getStatus() != AccountInterface::STATUS_APPROVED) {
            return $account;
        }

        $programCollection = $this->programRepository->getCollection()
            ->addActiveFilter()
            ->addFieldToFilter(ProgramInterface::TYPE, ProgramInterface::TYPE_PAY_PER_SALE)
            ->addFieldToFilter(ProgramInterface::COUPON_IS_ACTIVE, 1);

        foreach ($programCollection as $program) {
            if ($program->getIsVisible() || in_array($program->getId(), $account->getProgramIds())) {
                $this->couponService->ensureCoupon($account, $program);
            }
        }

        return $account;
    }
}
