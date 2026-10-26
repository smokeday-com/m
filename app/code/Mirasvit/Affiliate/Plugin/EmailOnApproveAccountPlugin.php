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

use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Service\NotificationService;

/**
 * @see \Mirasvit\Affiliate\Repository\AccountRepository::save()
 */
class EmailOnApproveAccountPlugin
{
    private $notificationService;

    public function __construct(
        NotificationService $notificationService
    ) {
        $this->notificationService = $notificationService;
    }

    /**
     * @param mixed            $repository
     * @param AccountInterface $account
     *
     * @return \Magento\Framework\Model\AbstractModel|AccountInterface
     */
    public function afterSave($repository, AccountInterface $account)
    {
        /** @var AccountInterface $account */

        if ($account->getOrigData(AccountInterface::STATUS) != $account->getStatus()
            && $account->getStatus() == AccountInterface::STATUS_APPROVED
        ) {
            $vars = [
                'account' => [
                    'id'    => $account->getId(),
                    'name'  => $account->getName(),
                    'email' => $account->getEmail(),
                ],
            ];

            $this->notificationService->notifyAccount(
                $account,
                'affiliate/notification/customer/account_approved_template',
                $vars
            );
        }

        return $account;
    }
}
