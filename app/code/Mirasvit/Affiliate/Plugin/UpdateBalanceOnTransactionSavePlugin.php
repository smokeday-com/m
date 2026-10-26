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

use Mirasvit\Affiliate\Api\Data\TransactionInterface;
use Mirasvit\Affiliate\Repository\AccountRepository;
use Mirasvit\Affiliate\Repository\TransactionRepository;
use Mirasvit\Affiliate\Service\TransactionService;

/**
 * @see \Mirasvit\Affiliate\Repository\TransactionRepository::save()
 */
class UpdateBalanceOnTransactionSavePlugin
{
    private $accountRepository;

    private $transactionService;

    private $transactionRepository;

    public function __construct(
        TransactionRepository $transactionRepository,
        TransactionService $transactionService,
        AccountRepository $accountRepository
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->transactionService    = $transactionService;
        $this->accountRepository     = $accountRepository;
    }

    /**
     * @param mixed                $repository
     * @param TransactionInterface $transaction
     *
     * @return TransactionInterface
     */
    public function afterSave($repository, TransactionInterface $transaction)
    {
        $collection = $this->transactionRepository->getCollection();
        $collection
            ->addFieldToFilter(TransactionInterface::ACCOUNT_ID, $transaction->getAccountId())
            ->addFieldToFilter(TransactionInterface::STATUS, TransactionInterface::STATUS_COMPLETE);

        $balance          = 0;
        $totalCommissions = 0;
        $totalWithdrawals = 0;
        foreach ($collection as $item) {
            $balance += $item->getAmount();

            if ($item->getType() == TransactionInterface::TYPE_COMMISSION) {
                $totalCommissions += $item->getAmount();
            }

            if ($item->getType() == TransactionInterface::TYPE_WITHDRAW) {
                $totalWithdrawals += -1 * $item->getAmount();
            }
        }

        $account = $this->transactionService->getAccount($transaction);

        $account->setBalance((float)$balance)
            ->setTotalCommissions((float)$totalCommissions)
            ->setTotalWithdrawals((float)$totalWithdrawals);

        $this->accountRepository->save($account);

        return $transaction;
    }
}
