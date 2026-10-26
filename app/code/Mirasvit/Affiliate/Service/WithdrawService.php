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

use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Api\Data\WithdrawInterface;
use Mirasvit\Affiliate\Api\Data\WithdrawInterfaceFactory;
use Mirasvit\Affiliate\Model\ConfigProvider;
use Mirasvit\Affiliate\Repository\AccountRepository;
use Mirasvit\Affiliate\Repository\WithdrawRepository;
use Mirasvit\Affiliate\Ui\Config\Form\WithdrawPayment\BackendModel;

/**
 * @SuppressWarnings(PHPMD)
 */
class WithdrawService
{

    private $currencyFactory;

    private $withdrawFactory;

    private $configProvider;

    private $withdrawRepository;

    private $accountRepository;

    private $storeManager;

    public function __construct(
        CurrencyFactory          $currencyFactory,
        WithdrawInterfaceFactory $withdrawFactory,
        WithdrawRepository       $withdrawRepository,
        AccountRepository        $accountRepository,
        StoreManagerInterface    $storeManager,
        ConfigProvider           $configProvider
    ) {
        $this->currencyFactory    = $currencyFactory;
        $this->withdrawFactory    = $withdrawFactory;
        $this->withdrawRepository = $withdrawRepository;
        $this->accountRepository  = $accountRepository;
        $this->configProvider     = $configProvider;
        $this->storeManager       = $storeManager;
    }

    public function createRequest(AccountInterface $account, float $amount, string $currencyCode = null): ?WithdrawInterface
    {
        $baseAmount = $currencyCode ? $this->convertToBaseCurrency($amount, $currencyCode) : $amount;

        if ($baseAmount > $account->getBalance()) {
            throw new LocalizedException(__('Withdraw cannot exceed current balance.'));
        }

        $baseMinAmount = $this->configProvider->getWithdrawMinAmount();
        if ($baseMinAmount > $baseAmount) {
            $minAmount = $currencyCode ? $this->convertToCurrency($baseMinAmount, $currencyCode) : $baseMinAmount;
            throw new LocalizedException(__('Minimum withdrawal amount is %1.', $minAmount));
        }

        $pendingRequests = $this->withdrawRepository->getCollection()
            ->addFieldToFilter(WithdrawInterface::ACCOUNT_ID, $account->getId())
            ->addFieldToFilter(WithdrawInterface::STATUS, WithdrawInterface::STATUS_PENDING);

        if ($pendingRequests->getSize()) {
            throw new LocalizedException(__('You already have a pending withdrawal request.'));
        }

        $withdraw = $this->withdrawFactory->create();
        $withdraw->setAccountId($account->getId())
            ->setAmount($baseAmount)
            ->setFee(0)
            ->setStatus(WithdrawInterface::STATUS_PENDING);

        return $this->withdrawRepository->save($withdraw);
    }

    public function acceptWithdraw(WithdrawInterface $withdraw): void
    {
        $withdraw->setStatus(WithdrawInterface::STATUS_ACCEPTED);
        $this->withdrawRepository->save($withdraw);
    }

    public function completeWithdraw(WithdrawInterface $withdraw): void
    {
        $withdraw->setStatus(WithdrawInterface::STATUS_COMPLETE);
        $this->withdrawRepository->save($withdraw);
    }

    public function denyWithdraw(WithdrawInterface $withdraw): void
    {
        $withdraw->setStatus(WithdrawInterface::STATUS_DENIED);
        $this->withdrawRepository->save($withdraw);
    }

    public function getAccount(WithdrawInterface $withdraw): ?AccountInterface
    {
        return $this->accountRepository->get($withdraw->getAccountId());
    }

    public function sendToStoreCredit(WithdrawInterface $withdraw): void
    {
        $account = $this->getAccount($withdraw);
        if ($account->getPaymentMethod() != BackendModel::PAYMENT_METHOD_CREDIT) {
            throw new LocalizedException(__('Unexpected payment method: %1', $account->getPaymentMethod()));
        }

        if (!$this->configProvider->isModuleEnabled('Mirasvit_Credit')) {
            throw new LocalizedException(__('Store Credit extension not found'));
        }

        $balanceFactory = ObjectManager::getInstance()->create('Mirasvit\Credit\Model\BalanceFactory'); // phpcs:ignore
        $currency       = $this->storeManager->getStore()->getCurrentCurrencyCode();
        $balance        = $balanceFactory->create()
            ->loadByCustomer($account->getCustomerId(), $currency);

        $balance->addTransaction(
            $withdraw->getAmount(),
            $withdraw->getAmount(),
            'manual',
            __('Affiliate Withdrawal')
        );

        $this->completeWithdraw($withdraw);
    }

    private function convertToBaseCurrency(float $amount, string $fromCurrencyCode): float
    {
        $baseCurrencyCode = $this->storeManager->getStore()->getBaseCurrencyCode();
        $rate             = (float)$this->currencyFactory->create()->load($baseCurrencyCode)->getAnyRate($fromCurrencyCode);

        return $amount / $rate;
    }

    private function convertToCurrency(float $amount, string $toCurrencyCode): float
    {
        $baseCurrencyCode = $this->storeManager->getStore()->getBaseCurrencyCode();
        $rate             = (float)$this->currencyFactory->create()->load($baseCurrencyCode)->getAnyRate($toCurrencyCode);

        return $amount * $rate;
    }
}
