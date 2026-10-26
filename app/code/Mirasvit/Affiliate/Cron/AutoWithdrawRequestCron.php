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

namespace Mirasvit\Affiliate\Cron;

use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Model\ConfigProvider;
use Mirasvit\Affiliate\Repository\AccountRepository;
use Mirasvit\Affiliate\Service\WithdrawService;

class AutoWithdrawRequestCron
{
    private $withdrawService;

    private $configProvider;

    private $accountRepository;

    public function __construct(
        WithdrawService   $withdrawService,
        AccountRepository $accountRepository,
        ConfigProvider    $configProvider
    ) {
        $this->withdrawService   = $withdrawService;
        $this->accountRepository = $accountRepository;
        $this->configProvider    = $configProvider;
    }

    public function execute(): void
    {
        $this->createAutoWithdrawRequest();
    }

    private function createAutoWithdrawRequest()
    {
        if (!$this->isNeedCreateRequest()) {
            return;
        }

        $withdrawMinAmount = $this->configProvider->getWithdrawMinAmount();
        $accountCollection = $this->accountRepository->getCollection()
            ->addFieldToFilter(AccountInterface::STATUS, AccountInterface::STATUS_APPROVED)
            ->addFieldToFilter(AccountInterface::BALANCE, ['gteq' => $withdrawMinAmount]);

        foreach ($accountCollection as $account) {
            $withdrawAmount = $this->getAutoWithdrawAmount($account);
            if ($withdrawAmount < $withdrawMinAmount) {
                continue;
            }

            try {
                $withdraw = $this->withdrawService->createRequest($account, $withdrawAmount);
            } catch (\Exception $e) {
                continue;
            }

            if ($withdraw) {
                $this->withdrawService->acceptWithdraw($withdraw);
            }
        }
    }

    private function isNeedCreateRequest(): bool
    {
        if (!$this->configProvider->isAutoWithdrawEnabled()) {
            return false;
        }

        $Schedule = $this->configProvider->getAutoWithdrawSchedule();
        if ($Schedule == ConfigProvider::AUTOWITHDRAW_SCHEDULE_MONTH) {
            return (date('j') == '1');
        }

        if ($Schedule == ConfigProvider::AUTOWITHDRAW_SCHEDULE_WEEK) {
            return $this->isFirstDayOfWeek();
        }

        return false;
    }

    private function isFirstDayOfWeek(): bool
    {
        return ($this->configProvider->getFirstDayNum() == date('N') % 7);
    }

    private function getAutoWithdrawAmount(AccountInterface $account): float
    {
        $balance   = $account->getBalance();
        $roundType = $this->configProvider->getAutoWithdrawRound();
        switch ($roundType) {
            case ConfigProvider::AUTOWITHDRAW_ROUND_10:
                return 10 * floor($balance / 10);
            case ConfigProvider::AUTOWITHDRAW_ROUND_100:
                return 100 * floor($balance / 100);
        }

        return $balance;
    }
}
