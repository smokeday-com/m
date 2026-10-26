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

interface AccountInterface
{
    public const TABLE_NAME = 'mst_affiliate_account';

    public const STATUS_PENDING      = 1;
    public const STATUS_APPROVED     = 2;
    public const STATUS_NOT_APPROVED = 3;

    public const ID                         = 'account_id';
    public const CUSTOMER_ID                = 'customer_id';
    public const GROUP_ID                   = 'group_id';
    public const IDENTIFIER                 = 'identifier';
    public const STATUS                     = 'status';
    public const BALANCE                    = 'balance';
    public const TOTAL_COMMISSIONS          = 'total_commissions';
    public const TOTAL_WITHDRAWALS          = 'total_withdrawals';
    public const IS_SUBSCRIBED              = 'is_subscribed';
    public const WEBSITE                    = 'website';
    public const PAYMENT_METHOD             = 'payment_method';
    public const PAYMENT_EMAIL              = 'payment_email';
    public const PROGRAM_IDS                = 'program_ids';
    public const PARENT_ID                  = 'parent_id';
    public const ADDITIONAL_DATA_SERIALIZED = 'additional_data_serialized';
    public const CREATED_AT                 = 'created_at';
    public const UPDATED_AT                 = 'updated_at';

    public function getId(): ?int;

    public function getCustomerId(): int;

    public function setCustomerId(int $id): self;

    public function getIdentifier(): string;

    public function setIdentifier(string $identifier): self;

    public function getStatus(): int;

    public function setStatus(int $status): self;

    public function getBalance(): float;

    public function setBalance(float $balance): self;

    public function getTotalCommissions(): float;

    public function setTotalCommissions(float $total): self;

    public function getTotalWithdrawals(): float;

    public function setTotalWithdrawals(float $total): self;

    public function getProgramIds(): array;

    public function setProgramIds(array $value): self;

    public function getCreatedAt(): string;

    public function setCreatedAt(string $createdAt): self;

    public function getUpdatedAt(): string;

    public function setUpdatedAt(string $updatedAt): self;

    public function getIsSubscribed(): bool;

    public function setIsSubscribed(bool $isSubscribed): self;

    public function getWebsite(): string;

    public function setWebsite(string $url): self;

    public function getPaymentMethod(): string;

    public function setPaymentMethod(string $method): self;

    public function getPaymentEmail(): string;

    public function setPaymentEmail(string $email): self;

    public function getParentId(): ?int;

    public function setParentId(int $value): self;

    public function getName(): string;

    public function getEmail(): string;

    /**
     * @param string|null $key
     *
     * @return mixed
     * @deprecated
     */
    public function getOrigData(?string $key = null);

    public function getGroupId(): ?int;

    public function setGroupId(int $value = null): self;

    public function getAdditionalDataSerialized(): ?string;

    public function getAdditionalData(): array;

    public function setAdditionalDataSerialized(?string $additionalData): self;
}
