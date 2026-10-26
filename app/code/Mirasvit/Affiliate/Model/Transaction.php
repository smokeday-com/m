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
use Mirasvit\Affiliate\Api\Data\TransactionInterface;
use Mirasvit\Core\Service\SerializeService;

class Transaction extends AbstractModel implements TransactionInterface
{
    protected function _construct(): void
    {
        $this->_init(\Mirasvit\Affiliate\Model\ResourceModel\Transaction::class);
    }

    public function getId(): int
    {
        return (int)$this->getData(self::ID);
    }

    public function getAccountId(): int
    {
        return (int)$this->getData(self::ACCOUNT_ID);
    }

    public function setAccountId(int $id): TransactionInterface
    {
        return $this->setData(self::ACCOUNT_ID, $id);
    }

    public function getEntityId(): int
    {
        return (int)$this->getData(self::ENTITY_ID);
    }

    /**
     * {@inheritDoc}
     */
    public function setEntityId($id)
    {
        return $this->setData(self::ENTITY_ID, $id);
    }

    public function getCustomerId(): ?int
    {
        return $this->getData(self::CUSTOMER_ID)
            ? (int)$this->getData(self::CUSTOMER_ID)
            : null;
    }

    public function setCustomerId(int $value = null): TransactionInterface
    {
        return $this->setData(self::CUSTOMER_ID, $value);
    }

    public function getEventId(): int
    {
        return (int)$this->getData(self::EVENT_ID);
    }

    public function setEventId(int $id): TransactionInterface
    {
        return $this->setData(self::EVENT_ID, $id);
    }

    public function getAmount(): float
    {
        return (float)$this->getData(self::AMOUNT);
    }

    public function setAmount(float $amount): TransactionInterface
    {
        return $this->setData(self::AMOUNT, $amount);
    }

    public function getBalance(): float
    {
        return (float)$this->getData(self::BALANCE);
    }

    public function setBalance(float $balance): TransactionInterface
    {
        return $this->setData(self::BALANCE, $balance);
    }

    public function getType(): string
    {
        return (string)$this->getData(self::TYPE);
    }

    public function setType(string $type): TransactionInterface
    {
        return $this->setData(self::TYPE, $type);
    }

    public function getMessage(): string
    {
        return (string)$this->getData(self::MESSAGE);
    }

    public function setMessage(string $message): TransactionInterface
    {
        return $this->setData(self::MESSAGE, $message);
    }

    public function getStatus(): string
    {
        return (string)$this->getData(self::STATUS);
    }

    public function setStatus(string $status): TransactionInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    public function getHoldTo(): string
    {
        return (string)$this->getData(self::HOLD_TO);
    }

    public function setHoldTo(?string $holdTo): TransactionInterface
    {
        return $this->setData(self::HOLD_TO, $holdTo);
    }

    public function getCreatedAt(): string
    {
        return (string)$this->getData(self::CREATED_AT);
    }

    public function setCreatedAt(string $createdAt): TransactionInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    public function getUpdatedAt(): string
    {
        return (string)$this->getData(self::UPDATED_AT);
    }

    public function setUpdatedAt(string $updatedAt): TransactionInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    public function getStoreId(): int
    {
        return (int)$this->getData(self::STORE_ID);
    }

    public function setStoreId(int $storeId): TransactionInterface
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    public function getExplain(): array
    {
        $json = $this->getData(self::EXPLAIN);

        return SerializeService::decode($json ?? '[]');
    }

    public function setExplain(array $explain): TransactionInterface
    {
        $json = SerializeService::encode($explain);

        return $this->setData(self::EXPLAIN, $json ?? '[]');
    }
}
