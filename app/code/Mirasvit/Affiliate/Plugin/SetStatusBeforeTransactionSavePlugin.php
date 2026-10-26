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
use Mirasvit\Affiliate\Model\ConfigProvider;
use Mirasvit\Affiliate\Service\TransactionService;

/**
 * @see \Mirasvit\Affiliate\Repository\TransactionRepository::save()
 */
class SetStatusBeforeTransactionSavePlugin
{
    private $transactionService;

    private $configProvider;

    public function __construct(
        ConfigProvider $configProvider,
        TransactionService $transactionService
    ) {
        $this->configProvider     = $configProvider;
        $this->transactionService = $transactionService;
    }

    /**
     * @param mixed                $repository
     * @param TransactionInterface $transaction
     *
     * @return array
     */
    public function beforeSave($repository, TransactionInterface $transaction)
    {
        $account = $this->transactionService->getAccount($transaction);

        if ($transaction->getStatus()) {
            return [$transaction];
        }

        if ($transaction->getAmount() > 0 && $this->configProvider->getCommissionHoldingPeriod() > 0) {
            $transaction->setStatus(TransactionInterface::STATUS_IN_HOLD)
                ->setBalance((float)$account->getBalance())
                ->setHoldTo((string)date("Y-m-d H:i:s", time() + $this->configProvider->getCommissionHoldingPeriod()));
        } else {
            $transaction->setStatus(TransactionInterface::STATUS_COMPLETE)
                ->setBalance((float)($account->getBalance() + $transaction->getAmount()));
        }

        return [$transaction];
    }
}
