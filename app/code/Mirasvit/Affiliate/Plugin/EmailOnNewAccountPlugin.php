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

use Magento\Framework\Data\OptionSourceInterface;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Model\Config\Source\Account\StatusSource;
use Mirasvit\Affiliate\Service\NotificationService;

class EmailOnNewAccountPlugin
{
    private $statusSource;

    private $notificationService;

    public function __construct(
        NotificationService $notificationService,
        StatusSource        $statusSource
    ) {
        $this->notificationService = $notificationService;
        $this->statusSource        = $statusSource;
    }

    /**
     * @param mixed            $repository
     * @param AccountInterface $account
     *
     * @return AccountInterface
     */
    public function afterSave($repository, AccountInterface $account)
    {
        if ($account->getOrigData(AccountInterface::ID)) {
            return $account;
        }

        $vars = [
            'account'   => [
                'id'      => $account->getId(),
                'name'    => $account->getName(),
                'email'   => $account->getEmail(),
                'website' => $account->getWebsite(),
            ],
            'isPending' => $account->getStatus() == AccountInterface::STATUS_PENDING,
            'status'    => (string)$this->toOptionHash($this->statusSource)[$account->getStatus()],
        ];

        $this->notificationService->notifyAccount(
            $account,
            'affiliate/notification/customer/account_new_template',
            $vars
        );

        $this->notificationService->notifyAdmin(
            $account,
            'affiliate/notification/admin/account_new_template',
            $vars
        );

        return $account;
    }

    private function toOptionHash(OptionSourceInterface $source): array
    {
        $options = [];

        foreach ($source->toOptionArray() as $item) {
            $options[$item['value']] = $item['label'];
        }

        return $options;
    }
}
