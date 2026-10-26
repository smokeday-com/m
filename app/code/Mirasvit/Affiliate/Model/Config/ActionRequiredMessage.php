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

namespace Mirasvit\Affiliate\Model\Config;

use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Api\Data\WithdrawInterface;
use Mirasvit\Affiliate\Repository\AccountRepository;
use Mirasvit\Affiliate\Repository\WithdrawRepository;

class ActionRequiredMessage implements MessageInterface
{
    private $urlBuilder;

    private $accountRepository;

    private $withdrawRepository;

    public function __construct(
        UrlInterface $urlBuilder,
        AccountRepository $accountRepository,
        WithdrawRepository $withdrawRepository
    ) {
        $this->urlBuilder         = $urlBuilder;
        $this->accountRepository  = $accountRepository;
        $this->withdrawRepository = $withdrawRepository;
    }

    public function isDisplayed(): bool
    {
        if ($this->getPendingAccounts() || $this->getPendingWithdraws()) {
            return true;
        }

        return false;
    }

    public function getIdentity(): string
    {
        return hash("sha256", __CLASS__);
    }

    public function getSeverity(): int
    {
        return self::SEVERITY_MAJOR;
    }

    public function getText(): string
    {
        $text = '';

        if ($this->getPendingAccounts()) {
            $url  = $this->urlBuilder->getUrl('affiliate/account/index');
            $text .= __('One or more <a href="%1">affiliate accounts</a> are currently in pending status.', $url);
        }

        if ($this->getPendingWithdraws()) {
            $url  = $this->urlBuilder->getUrl('affiliate/withdraw/index');
            $text .= __('One or more <a href="%1">withdrawal requests</a> are currently in pending status.', $url);
        }

        return $text;
    }

    private function getPendingAccounts(): int
    {
        $collection = $this->accountRepository->getCollection();
        $collection->addFieldToFilter(AccountInterface::STATUS, AccountInterface::STATUS_PENDING);

        return (int)$collection->getSize();
    }

    private function getPendingWithdraws(): int
    {
        $collection = $this->withdrawRepository->getCollection();
        $collection->addFieldToFilter(WithdrawInterface::STATUS, WithdrawInterface::STATUS_PENDING);

        return (int)$collection->getSize();
    }
}
