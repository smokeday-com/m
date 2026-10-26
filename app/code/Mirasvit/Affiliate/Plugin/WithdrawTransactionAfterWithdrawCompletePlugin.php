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

use Mirasvit\Affiliate\Api\Data\WithdrawInterface;
use Mirasvit\Affiliate\Repository\AccountRepository;
use Mirasvit\Affiliate\Service\TransactionService;

/**
 * @see \Mirasvit\Affiliate\Repository\WithdrawRepository::save()
 */
class WithdrawTransactionAfterWithdrawCompletePlugin
{
    private $transactionService;

    private $accountRepository;

    public function __construct(
        AccountRepository $accountRepository,
        TransactionService $transactionService
    ) {
        $this->accountRepository  = $accountRepository;
        $this->transactionService = $transactionService;
    }

    /**
     * @param mixed             $repository
     * @param WithdrawInterface $withdraw
     *
     * @return WithdrawInterface
     */
    public function afterSave($repository, WithdrawInterface $withdraw)
    {
        if ($withdraw->getStatus() == WithdrawInterface::STATUS_COMPLETE
            && $withdraw->getStatus() != $withdraw->getOrigData(WithdrawInterface::STATUS)
        ) {
            $account = $this->accountRepository->get($withdraw->getAccountId());
            $this->transactionService->addWithdrawTransaction(
                $account,
                (float)(-1 * ($withdraw->getAmount() + $withdraw->getFee()))
            );
        }

        return $withdraw;
    }
}
