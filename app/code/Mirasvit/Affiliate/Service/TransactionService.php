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

use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Api\Data\EventInterface;
use Mirasvit\Affiliate\Api\Data\TransactionInterface;
use Mirasvit\Affiliate\Api\Data\TransactionInterfaceFactory;
use Mirasvit\Affiliate\Model\ConfigProvider;
use Mirasvit\Affiliate\Model\Explain\TransactionExplain;
use Mirasvit\Affiliate\Repository\AccountRepository;
use Mirasvit\Affiliate\Repository\TransactionRepository;

class TransactionService
{
    private $transactionFactory;

    private $transactionRepository;

    private $accountRepository;

    private $configProvider;

    public function __construct(
        ConfigProvider              $configProvider,
        AccountRepository           $accountRepository,
        TransactionRepository       $transactionRepository,
        TransactionInterfaceFactory $transactionFactory
    ) {
        $this->configProvider        = $configProvider;
        $this->accountRepository     = $accountRepository;
        $this->transactionRepository = $transactionRepository;
        $this->transactionFactory    = $transactionFactory;
    }

    public function getAccount(TransactionInterface $transaction): ?AccountInterface
    {
        return $this->accountRepository->get($transaction->getAccountId());
    }

    public function addCommissionTransaction(AccountInterface $account, EventInterface $event, float $amount, string $message, TransactionExplain $explain = null): void
    {
        if (abs($amount) < ConfigProvider::FLOAT_EPSILON) {
            return;
        }

        $transaction = $this->transactionFactory->create();
        $transaction->setType(TransactionInterface::TYPE_COMMISSION)
            ->setAccountId($account->getId())
            ->setEventId($event->getId())
            ->setCustomerId($event->getCustomerId())
            ->setAmount($amount)
            ->setMessage($message)
            ->setStoreId($event->getStoreId());
        if ($event->getEntityId()) {
            $transaction->setEntityId($event->getEntityId());
        }
        if ($explain && $event->getType() == EventInterface::TYPE_SALE) {
            $transaction->setExplain($explain->getData());
        }

        $this->transactionRepository->save($transaction);
    }

    public function addWithdrawTransaction(AccountInterface $account, float $amount): void
    {
        $transaction = $this->transactionFactory->create();
        $transaction->setType(TransactionInterface::TYPE_WITHDRAW)
            ->setAccountId($account->getId())
            ->setAmount($amount)
            ->setMessage((string)__('Withdrawal'));

        $this->transactionRepository->save($transaction);
    }

    public function completeTransaction(TransactionInterface $transaction): TransactionInterface
    {
        if (strtotime($transaction->getHoldTo()) <= time()) {
            $transaction->setStatus(TransactionInterface::STATUS_COMPLETE)
                ->setHoldTo(null);

            $this->transactionRepository->save($transaction);
        }

        return $transaction;
    }

    public function isExceededTransactionLimit(AccountInterface $account, EventInterface $event): bool
    {
        if ($this->configProvider->getCommissionsLimit() && $event->getCustomerId()) {
            $transactions = $this->transactionRepository->getCollection();
            $transactions
                ->addStoreFilter($event->getStoreId())
                ->addFieldToFilter(TransactionInterface::ACCOUNT_ID, $account->getId())
                ->addFieldToFilter(TransactionInterface::CUSTOMER_ID, $event->getCustomerId());

            if ($transactions->getSize() >= $this->configProvider->getCommissionsLimit()) {
                return true;
            }
        }

        return false;
    }
}
