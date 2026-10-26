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

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Model\Account;
use Mirasvit\Affiliate\Model\ConfigProvider;
use Mirasvit\Affiliate\Repository\AccountRepository;

class SessionService
{
    private $customerSession;

    private $account;

    private $accountRepository;

    private $configProvider;

    public function __construct(
        CustomerSession $customerSession,
        AccountRepository $accountRepository,
        ConfigProvider $configProvider
    ) {
        $this->customerSession   = $customerSession;
        $this->accountRepository = $accountRepository;
        $this->configProvider    = $configProvider;
    }

    public function getAccount(): ?AccountInterface
    {
        if (!$this->account) {
            $this->account = $this->accountRepository
                ->getByCustomerId((int)$this->customerSession->getCustomer()->getId());

            if (!$this->account && $this->configProvider->isAutoSignUpEnabled()) {
                if (!$this->customerSession->getCustomer()->getId()) {
                    return null;
                }
                $account = $this->accountRepository->create();
                $account->setCustomerId((int)$this->customerSession->getCustomer()->getId())
                    ->setStatus(AccountInterface::STATUS_APPROVED);

                $this->account = $this->accountRepository->save($account);
            }
        }

        return $this->account;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customerSession->getCustomer()->getId() ? $this->customerSession->getCustomer() : null;
    }
}
