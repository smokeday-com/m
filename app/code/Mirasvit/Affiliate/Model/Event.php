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
use Mirasvit\Affiliate\Api\Data\EventInterface;

class Event extends AbstractModel implements EventInterface
{
    protected function _construct(): void
    {
        $this->_init(\Mirasvit\Affiliate\Model\ResourceModel\Event::class);
    }

    public function getId(): int
    {
        return (int)$this->getData(self::ID);
    }

    public function getAccountId(): int
    {
        return (int)$this->getData(self::ACCOUNT_ID);
    }

    public function setAccountId(int $accountId): EventInterface
    {
        return $this->setData(self::ACCOUNT_ID, $accountId);
    }

    public function getCustomerId(): ?int
    {
        return $this->getData(self::CUSTOMER_ID)
            ? (int)$this->getData(self::CUSTOMER_ID)
            : null;
    }

    public function setCustomerId(int $value = null): EventInterface
    {
        return $this->setData(self::CUSTOMER_ID, $value);
    }

    public function getType(): string
    {
        return (string)$this->getData(self::TYPE);
    }

    public function setType(string $type): EventInterface
    {
        return $this->setData(self::TYPE, $type);
    }

    public function getVisitorIdentifier(): string
    {
        return (string)$this->getData(self::VISITOR_IDENTIFIER);
    }

    public function setVisitorIdentifier(string $identifier): EventInterface
    {
        return $this->setData(self::VISITOR_IDENTIFIER, $identifier);
    }

    public function getEntityId(): int
    {
        return (int)$this->getData(self::ENTITY_ID);
    }

    /**
     * {@inheritDoc}
     */
    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    public function getArgsSerialized(): string
    {
        return (string)$this->getData(self::ARGS_SERIALIZED);
    }

    public function setArgsSerialized(string $args): EventInterface
    {
        return $this->setData(self::ARGS_SERIALIZED, $args);
    }

    public function isProcessed(): bool
    {
        return (bool)$this->getData(self::IS_PROCESSED);
    }

    public function setIsProcessed(bool $flag): EventInterface
    {
        return $this->setData(self::IS_PROCESSED, $flag);
    }

    public function getAmount(): float
    {
        return (float)$this->getData(self::AMOUNT);
    }

    public function setAmount(float $amount): EventInterface
    {
        return $this->setData(self::AMOUNT, $amount);
    }

    public function getReferer(): string
    {
        return (string)$this->getData(self::REFERER);
    }

    public function setReferer(string $referer): EventInterface
    {
        return $this->setData(self::REFERER, $referer);
    }

    public function getCommission(): float
    {
        return (float)$this->getData(self::COMMISSION);
    }

    public function setCommission(float $commission): EventInterface
    {
        return $this->setData(self::COMMISSION, $commission);
    }

    public function getStoreId(): int
    {
        return (int)$this->getData(self::STORE_ID);
    }

    public function setStoreId(int $storeId): EventInterface
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    public function getCreatedAt(): string
    {
        return (string)$this->getData(self::CREATED_AT);
    }
}
