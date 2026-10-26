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
use Mirasvit\Affiliate\Api\Data\ProgramInterface;
use Mirasvit\Affiliate\Repository\AccountRepository;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Service\CouponService;

/**
 * @see \Mirasvit\Affiliate\Repository\ProgramRepository::save()
 */
class EnsureCouponsAfterProgramSavePlugin
{
    private $accountRepository;

    private $couponService;

    public function __construct(AccountRepository $accountRepository, CouponService $couponService)
    {
        $this->accountRepository = $accountRepository;
        $this->couponService     = $couponService;
    }

    /**
     * @param mixed            $repository
     * @param ProgramInterface $program
     *
     * @return ProgramInterface
     * @throws CouldNotSaveException
     */
    public function afterSave($repository, ProgramInterface $program)
    {
        if (!$program->getIsActive()
            || $program->getType() != ProgramInterface::TYPE_PAY_PER_SALE
            || !$program->getCouponIsActive()
        ) {
            return $program;
        }

        $accountCollection = $this->accountRepository->getCollection();
        $accountCollection->addFieldToFilter(AccountInterface::STATUS, AccountInterface::STATUS_APPROVED);
        if (!$program->getIsVisible()) {
            $accountCollection->addFieldToFilter(AccountInterface::PROGRAM_IDS, ['finset' => $program->getId()]);
        }
        foreach ($accountCollection as $account) {
            $this->couponService->ensureCoupon($account, $program);
        }

        return $program;
    }
}
