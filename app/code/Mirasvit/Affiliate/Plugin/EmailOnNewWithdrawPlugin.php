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
use Mirasvit\Affiliate\Service\NotificationService;

class EmailOnNewWithdrawPlugin
{
    private $notificationService;

    private $accountRepository;

    public function __construct(
        NotificationService $notificationService,
        AccountRepository $accountRepository
    ) {
        $this->notificationService = $notificationService;
        $this->accountRepository   = $accountRepository;
    }

    /**
     * @param mixed             $repository
     * @param WithdrawInterface $withdraw
     *
     * @return WithdrawInterface
     */
    public function afterSave($repository, WithdrawInterface $withdraw)
    {
        if ($withdraw->getOrigData(WithdrawInterface::ID)) {
            return $withdraw;
        }

        $account = $this->accountRepository->get($withdraw->getAccountId());

        $vars = [
            'account'  => [
                'id'    => $account->getId(),
                'name'  => $account->getName(),
                'email' => $account->getEmail(),
            ],
            'withdraw' => [
                'id'     => $withdraw->getId(),
                'amount' => $withdraw->getAmount(),
                'status' => $withdraw->getStatus(),
            ],
        ];

        $this->notificationService->notifyAdmin(
            $account,
            'affiliate/notification/admin/withdraw_new_template',
            $vars
        );

        return $withdraw;
    }
}
