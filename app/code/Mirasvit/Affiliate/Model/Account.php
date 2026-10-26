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

namespace Mirasvit\Affiliate\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Service\AccountService;
use Mirasvit\Core\Service\SerializeService;

class Account extends AbstractModel implements AccountInterface
{

    private $accountService;

    public function __construct(
        AccountService $accountService,
        Context        $context,
        Registry       $registry
    ) {
        $this->accountService = $accountService;

        parent::__construct($context, $registry);
    }

    public function getId(): ?int
    {
        return $this->getData(self::ID)
            ? (int)$this->getData(self::ID)
            : null;
    }

    public function getCustomerId(): int
    {
        return (int)$this->getData(self::CUSTOMER_ID);
    }

    public function setCustomerId(int $id): AccountInterface
    {
        return $this->setData(self::CUSTOMER_ID, $id);
    }

    public function getIdentifier(): string
    {
        return (string)$this->getData(self::IDENTIFIER);
    }

    public function setIdentifier(string $identifier): AccountInterface
    {
        return $this->setData(self::IDENTIFIER, $identifier);
    }

    public function getStatus(): int
    {
        return (int)$this->getData(self::STATUS);
    }

    public function setStatus(int $status): AccountInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    public function getBalance(): float
    {
        return (float)$this->getData(self::BALANCE);
    }

    public function setBalance(float $balance): AccountInterface
    {
        return $this->setData(self::BALANCE, $balance);
    }

    public function getTotalCommissions(): float
    {
        return (float)$this->getData(self::TOTAL_COMMISSIONS);
    }

    public function setTotalCommissions(float $total): AccountInterface
    {
        return $this->setData(self::TOTAL_COMMISSIONS, $total);
    }

    public function getTotalWithdrawals(): float
    {
        return (float)$this->getData(self::TOTAL_WITHDRAWALS);
    }

    public function setTotalWithdrawals(float $total): AccountInterface
    {
        return $this->setData(self::TOTAL_WITHDRAWALS, $total);
    }

    public function getProgramIds(): array
    {
        $programIds = [];

        if ($this->getData(self::PROGRAM_IDS)) {
            $programIds = array_filter(explode(',', $this->getData(self::PROGRAM_IDS)));
        }

        return $programIds;
    }

    public function setProgramIds(array $value): AccountInterface
    {
        return $this->setData(self::PROGRAM_IDS, implode(',', $value));
    }

    public function getCreatedAt(): string
    {
        return (string)$this->getData(self::CREATED_AT);
    }

    public function setCreatedAt(string $createdAt): AccountInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    public function getUpdatedAt(): string
    {
        return (string)$this->getData(self::UPDATED_AT);
    }

    public function setUpdatedAt(string $updatedAt): AccountInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    public function getIsSubscribed(): bool
    {
        return (bool)$this->getData(self::IS_SUBSCRIBED);
    }

    public function setIsSubscribed(bool $isSubscribed): AccountInterface
    {
        return $this->setData(self::IS_SUBSCRIBED, $isSubscribed);
    }

    public function getWebsite(): string
    {
        $additionalData = $this->getAdditionalData();

        return $additionalData[self::WEBSITE] ?? '';
    }

    public function setWebsite(string $url): AccountInterface
    {
        return $this->setData(self::WEBSITE, $url);
    }

    public function getPaymentMethod(): string
    {
        return (string)$this->getData(self::PAYMENT_METHOD);
    }

    public function setPaymentMethod(string $method): AccountInterface
    {
        return $this->setData(self::PAYMENT_METHOD, $method);
    }

    public function getPaymentEmail(): string
    {
        return (string)$this->getData(self::PAYMENT_EMAIL);
    }

    public function setPaymentEmail(string $email): AccountInterface
    {
        return $this->setData(self::PAYMENT_EMAIL, $email);
    }

    public function getParentId(): ?int
    {
        return $this->getData(self::PARENT_ID)
            ? (int)$this->getData(self::PARENT_ID)
            : null;
    }

    public function setParentId(int $value): AccountInterface
    {
        return $this->setData(self::PARENT_ID, $value);
    }

    public function getName(): string
    {
        $customer = $this->accountService->getCustomer($this);

        return $customer ? $customer->getFirstname() . ' ' . $customer->getLastname() : '';
    }

    public function getEmail(): string
    {
        $customer = $this->accountService->getCustomer($this);

        return $customer ? $customer->getEmail() : '';
    }

    public function getTotalHoldedCommissions(): float
    {
        return (float)$this->accountService->getInHoldCommissionsAmount($this);
    }

    public function getGroupId(): ?int
    {
        return $this->getData(self::GROUP_ID)
            ? (int)$this->getData(self::GROUP_ID)
            : null;
    }

    public function setGroupId(int $value = null): AccountInterface
    {
        return $this->setData(AccountInterface::GROUP_ID, $value);
    }

    public function getAdditionalDataSerialized(): ?string
    {
        $value = $this->getData(self::ADDITIONAL_DATA_SERIALIZED);

        return is_array($value) ? SerializeService::encode($value) : $value;
    }

    public function getAdditionalData(): array
    {
        $value = $this->getData(self::ADDITIONAL_DATA_SERIALIZED);
        $data  = is_array($value) ? $value : SerializeService::decode($value);

        return $data ? : [];
    }

    public function setAdditionalDataSerialized(?string $additionalData): AccountInterface
    {
        return $this->setData(self::ADDITIONAL_DATA_SERIALIZED, $additionalData);
    }

    protected function _construct(): void
    {
        $this->_init(ResourceModel\Account::class);
    }
}
