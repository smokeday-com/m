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

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Mirasvit\Affiliate\Api\Data\AccountCustomerInterface;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Api\Data\CouponInterface;
use Mirasvit\Affiliate\Api\Data\ProgramInterface;
use Mirasvit\Affiliate\Api\Data\TransactionInterface;
use Mirasvit\Affiliate\Model\ConfigProvider;
use Mirasvit\Affiliate\Model\Transaction;
use Mirasvit\Affiliate\Repository\AccountCustomerRepository;
use Mirasvit\Affiliate\Repository\AccountRepository;
use Mirasvit\Affiliate\Repository\CouponRepository;
use Mirasvit\Affiliate\Repository\ProgramRepository;
use Mirasvit\Affiliate\Repository\TransactionRepository;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AccountService
{
    private $accountRepository;

    private $cookieManager;

    private $request;

    private $state;

    private $couponRepository;

    private $customerRepository;

    private $customerSession;

    private $accountCustomerRepository;

    private $transactionRepository;

    private $programRepository;

    private $configProvider;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        AccountRepository           $accountRepository,
        CookieManagerInterface      $cookieManager,
        RequestInterface            $request,
        CouponRepository            $couponRepository,
        State                       $state,
        CustomerRepositoryInterface $customerRepository,
        CustomerSession             $customerSession,
        AccountCustomerRepository   $accountCustomerRepository,
        TransactionRepository       $transactionRepository,
        ProgramRepository           $programRepository,
        ConfigProvider              $configProvider
    ) {
        $this->accountRepository         = $accountRepository;
        $this->cookieManager             = $cookieManager;
        $this->request                   = $request;
        $this->couponRepository          = $couponRepository;
        $this->state                     = $state;
        $this->customerRepository        = $customerRepository;
        $this->customerSession           = $customerSession;
        $this->accountCustomerRepository = $accountCustomerRepository;
        $this->transactionRepository     = $transactionRepository;
        $this->programRepository         = $programRepository;
        $this->configProvider            = $configProvider;
    }

    public function createAccount(AccountInterface $account): AccountInterface
    {
        if ($this->configProvider->isApprovalRequired()) {
            $account->setStatus(AccountInterface::STATUS_PENDING);
        } else {
            $account->setStatus(AccountInterface::STATUS_APPROVED);
        }

        $account->setCustomerId((int)$this->customerSession->getCustomerId());

        return $this->accountRepository->save($account);
    }

    public function getCustomer(AccountInterface $account): ?CustomerInterface
    {
        try {
            return $this->customerRepository->getById($account->getCustomerId());
        } catch (\Exception $e) {
            return null;
        }
    }

    public function ensureCustomerRelation(string $accountIdentifier, int $customerId): ?AccountCustomerInterface
    {
        $account = $this->accountRepository->getByIdentifier($accountIdentifier);
        if (!$account) {
            return null;
        }

        if ($account->getCustomerId() == $customerId) {
            return null;
        }

        $relation = $this->accountCustomerRepository->getByCustomerId($customerId);
        if (!$relation) {
            $relation = $this->accountCustomerRepository->create()
                ->setAccountId($account->getId())
                ->setCustomerId($customerId);
            $relation = $this->accountCustomerRepository->save($relation);

            return $relation;
        }

        if ($relation->getAccountId() != $account->getId() && $this->configProvider->getCommissionAssignPriority() == ConfigProvider::ASSIGN_PRIORITY_LAST) {
            $relation->setAccountId($account->getId())
                ->setCreatedAt(date('Y-m-d H:i:s'))
                ->setUpdatedAt(date('Y-m-d H:i:s'))
                ->setSumCommission(0);

            $relation = $this->accountCustomerRepository->save($relation);

            return $relation;
        }

        return $relation;
    }

    public function removeOldRelation(): void
    {
        $lifetime = $this->configProvider->getCookieLifetimeSeconds();
        if (!$lifetime) {
            return;
        }

        $collection = $this->accountCustomerRepository->getCollection()
            ->addFieldToFilter(AccountCustomerInterface::CREATED_AT, ['lt' => date('Y-m-d H:i:s', time() - $lifetime)]);
        foreach ($collection as $relation) {
            $this->accountCustomerRepository->delete($relation);
        }
    }

    public function getInHoldCommissionsAmount(AccountInterface $account): float
    {
        $total = 0;

        $collection = $this->transactionRepository->getCollection()
            ->addFieldToFilter('account_id', $account->getId())
            ->addStoreFilter()
            ->inHoldTransactions();

        /** @var Transaction $transaction */
        foreach ($collection as $transaction) {
            $total += (float)$transaction->getAmount();
        }

        return $total;
    }

    public function getRelatedCustomersIds(AccountInterface $account): array
    {
        $ids = [];

        $relatedCustomers = $this->accountCustomerRepository
            ->getCollection()
            ->addFieldToFilter(AccountCustomerInterface::ACCOUNT_ID, $account->getId());

        foreach ($relatedCustomers as $accountCustomer) {
            $ids[] = $accountCustomer->getCustomerId();
        }

        return array_unique($ids);
    }

    public function findAccount(int $customerId): ?AccountInterface
    {
        if (!$this->checkArea()) {
            return null;
        }

        if (!$customerId) {
            return $this->findNewAccountByIdentifier();
        }

        // get assigned account
        $account = $this->getAccountByCustomerId($customerId);

        // try to assign the customer to another account
        $newAccount = $this->findNewAccountByIdentifier();
        if ($newAccount) {
            $relation = $this->ensureCustomerRelation($newAccount->getIdentifier(), $customerId);
            if (!$relation) {
                return null;
            }

            if ($relation->getAccountId() == $newAccount->getId()) {
                $account = $newAccount;
            }
        }

        if (!$account || $account->getCustomerId() == $customerId) {
            return null;
        }

        return $account;
    }

    public function findNewAccountByIdentifier(): ?AccountInterface
    {
        $identifier = $this->request->getParam('identifier');
        if (!$identifier) {
            $identifier = $this->request->getParam($this->configProvider->getReferralParam());
        }

        if (!$identifier) {
            $identifier = $this->cookieManager->getCookie('affiliate_identifier') ?: '';
        }

        if ($identifier) {
            return $this->accountRepository->getByIdentifier($identifier);
        }

        return null;
    }

    public function getAccountByIdentifier(): ?AccountInterface
    {
        // Priority 1. Cookie
        $identifier = $this->cookieManager->getCookie('affiliate_identifier') ?: '';

        if (!$identifier) {
            $identifier = $this->cookieManager->getCookie('affiliate_identifier');
        }

        // Priority 2. Request
        if (!$identifier) {
            $identifier = $this->request->getParam('identifier');
        }

        if (!$identifier) {
            $identifier = $this->request->getParam($this->configProvider->getReferralParam());
        }

        if ($identifier) {
            return $this->accountRepository->getByIdentifier($identifier);
        }

        return null;
    }

    public function getAccountByCustomerId(int $customerId): ?AccountInterface
    {
        $relation = $this->accountCustomerRepository->getByCustomerId($customerId);

        if ($relation) {
            $endTs = strtotime($relation->getCreatedAt()) + $this->configProvider->getCookieLifetimeSeconds();

            if ($endTs > time()) {
                return $this->accountRepository->get($relation->getAccountId());
            }
        }

        return null;
    }

    public function getAccountByCoupon(string $code): ?AccountInterface
    {
        /** @var CouponInterface $coupon */
        $coupon = $this->couponRepository->getCollection()
            ->addFieldToFilter(CouponInterface::CODE, $code)
            ->getFirstItem();

        if ($coupon->getAccountId()) {
            return $this->accountRepository->get($coupon->getAccountId());
        }

        return null;
    }

    public function deleteProgramFromAllAccounts(ProgramInterface $program)
    {
        $deleteProgramId   = $program->getId();
        $accountCollection = $this->accountRepository->getCollection()->addFieldToFilter(AccountInterface::PROGRAM_IDS, ['finset' => $deleteProgramId]);
        foreach ($accountCollection as $account) {
            $programIds = $account->getProgramIds();
            $programIds = array_filter($programIds, function ($programId) use ($deleteProgramId) {
                return $programId != $deleteProgramId;
            });
            $account->setProgramIds($programIds);
            $this->accountRepository->save($account);
        }
    }

    public function filterMissingProgram(AccountInterface $account)
    {
        $programIds        = [];
        $programCollection = $this->programRepository->getCollection()->addFieldToFilter(ProgramInterface::ID, ['in' => $account->getProgramIds()]);
        foreach ($programCollection as $program) {
            $programIds[] = $program->getId();
        }
        $account->setProgramIds($programIds);
    }

    public function refreshSumCommission(): void
    {
        $tCollection = $this->transactionRepository->getCollection()
            ->addFieldToFilter(TransactionInterface::CUSTOMER_ID, ['neq' => 'NULL'])
            ->addFieldToFilter(TransactionInterface::TYPE, TransactionInterface::TYPE_COMMISSION)
            ->addFieldToFilter(TransactionInterface::STATUS, TransactionInterface::STATUS_COMPLETE);

        $tCollection->getSelect()
            ->group([TransactionInterface::ACCOUNT_ID, TransactionInterface::CUSTOMER_ID])
            ->columns([
                AccountCustomerInterface::SUM_COMMISSION => new \Zend_Db_Expr('SUM(' . TransactionInterface::AMOUNT . ')'),
            ]);

        $sumItems = $tCollection->getItems();

        foreach ($this->accountCustomerRepository->getCollection() as $relation) {
            $sum = $this->getSum($sumItems, $relation->getAccountId(), $relation->getCustomerId());
            $relation->setSumCommission($sum);
            $this->accountCustomerRepository->save($relation);
        }
    }

    private function getSum(array $sumItems, int $accountId, int $customerId): float
    {
        foreach ($sumItems as $item) {
            if ($item->getAccountId() == $accountId && $item->getCustomerId() == $customerId) {
                return (float)$item->getSumCommission();
            }
        }

        return 0.0;
    }

    private function checkArea(): bool
    {
        try {
            if (in_array($this->state->getAreaCode(), ['backend', 'adminhtml'])) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            return true;
        }
    }
}
