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

use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Mirasvit\Affiliate\Api\Data\TransactionInterface;
use Mirasvit\Affiliate\Model\ConfigProvider;
use Mirasvit\Affiliate\Service\NotificationService;
use Mirasvit\Affiliate\Service\TransactionService;

/**
 * @see \Mirasvit\Affiliate\Repository\TransactionRepository::save()
 */
class EmailOnNewTransactionPlugin
{
    private $pricingHelper;

    private $transactionService;

    private $notificationService;

    public function __construct(
        NotificationService $notificationService,
        TransactionService $transactionService,
        PricingHelper $pricingHelper
    ) {
        $this->notificationService = $notificationService;
        $this->transactionService  = $transactionService;
        $this->pricingHelper       = $pricingHelper;
    }

    /**
     * @param mixed                $repository
     * @param TransactionInterface $transaction
     *
     * @return TransactionInterface
     */
    public function afterSave($repository, TransactionInterface $transaction)
    {
        if ($transaction->getOrigData(TransactionInterface::ID)) {
            return $transaction;
        }

        $amount = $transaction->getAmount();
        if (abs($amount) < ConfigProvider::FLOAT_EPSILON) {
            return $transaction;
        }

        $account = $this->transactionService->getAccount($transaction);

        $vars = [
            'account'     => [
                'id'    => $account->getId(),
                'name'  => $account->getName(),
                'email' => $account->getEmail(),
            ],
            'transaction' => [
                'id'     => $transaction->getId(),
                'status' => $transaction->getStatus(),
            ],
            'amount'      => $this->pricingHelper->currency($transaction->getAmount(), true, false),
            'balance'     => $this->pricingHelper->currency($account->getBalance(), true, false),
        ];

        $this->notificationService->notifyAccount(
            $account,
            'affiliate/notification/customer/transaction_new_template',
            $vars
        );

        return $transaction;
    }
}
