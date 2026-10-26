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

namespace Mirasvit\Affiliate\Api\Data;

use Magento\Framework\Model\AbstractModel;

interface TransactionInterface
{
    public const TABLE_NAME = 'mst_affiliate_transaction';

    public const TYPE_COMMISSION = 'commission';
    public const TYPE_WITHDRAW   = 'withdraw';

    public const STATUS_COMPLETE = 'complete';
    public const STATUS_IN_HOLD  = 'in_hold';

    public const ID          = 'transaction_id';
    public const ACCOUNT_ID  = 'account_id';
    public const ENTITY_ID   = 'entity_id';
    public const CUSTOMER_ID = 'customer_id';
    public const EVENT_ID    = 'event_id';
    public const AMOUNT      = 'amount';
    public const BALANCE     = 'balance';
    public const TYPE        = 'type';
    public const MESSAGE     = 'message';
    public const STATUS      = 'status';
    public const HOLD_TO     = 'hold_to';
    public const CREATED_AT  = 'created_at';
    public const UPDATED_AT  = 'updated_at';
    public const STORE_ID    = 'store_id';
    public const EXPLAIN     = 'explain';

    public function getId(): int;

    public function getAccountId(): int;

    public function setAccountId(int $id): self;

    public function getEntityId(): int;

    /**
     * @param int $id
     * @return TransactionInterface|AbstractModel
     */
    public function setEntityId($id);

    public function getCustomerId(): ?int;

    public function setCustomerId(int $value = null): self;

    public function getEventId(): int;

    public function setEventId(int $id): self;

    public function getAmount(): float;

    public function setAmount(float $amount): self;

    public function getBalance(): float;

    public function setBalance(float $balance): self;

    public function getType(): string;

    public function setType(string $type): self;

    public function getMessage(): string;

    public function setMessage(string $message): self;

    public function getStatus(): string;

    public function setStatus(string $status): self;

    public function getHoldTo(): string;

    public function setHoldTo(?string $holdTo): self;

    public function getCreatedAt(): string;

    public function setCreatedAt(string $createdAt): self;

    public function getUpdatedAt(): string;

    public function setUpdatedAt(string $updatedAt): self;

    /**
     * @param string $key
     * @return mixed
     *
     * @deprecated
     */
    public function getOrigData($key = null);

    public function getStoreId(): int;

    public function setStoreId(int $value): self;

    public function getExplain(): array;

    public function setExplain(array $explain): self;
}
