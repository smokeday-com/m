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
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Service\NotificationService;

class EmailOnUpdateBalancePlugin
{
    private $pricingHelper;

    private $notificationService;

    public function __construct(
        NotificationService $notificationService,
        PricingHelper $pricingHelper
    ) {
        $this->notificationService = $notificationService;
        $this->pricingHelper       = $pricingHelper;
    }

    /**
     * @param mixed            $repository
     * @param AccountInterface $account
     *
     * @return AccountInterface
     */
    public function afterSave($repository, AccountInterface $account)
    {
        if ($account->getOrigData(AccountInterface::BALANCE) != $account->getBalance()) {
            $vars = [
                'account' => [
                    'id'    => $account->getId(),
                    'name'  => $account->getName(),
                    'email' => $account->getEmail(),
                ],
                'balance' => $this->pricingHelper->currency($account->getBalance(), true, false),
            ];

            $this->notificationService->notifyAccount(
                $account,
                'affiliate/notification/customer/update_balance_template',
                $vars
            );
        }

        return $account;
    }
}
