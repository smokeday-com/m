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

interface WithdrawInterface
{
    public const TABLE_NAME = 'mst_affiliate_withdraw';

    public const STATUS_COMPLETE = 'complete';
    public const STATUS_PENDING  = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DENIED   = 'denied';

    public const ID         = 'withdraw_id';
    public const ACCOUNT_ID = 'account_id';
    public const AMOUNT     = 'amount';
    public const FEE        = 'fee';
    public const MESSAGE    = 'message';
    public const STATUS     = 'status';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    public function getId(): int;

    public function getAccountId(): int;

    public function setAccountId(int $accountId): self;

    public function getAmount(): float;

    public function setAmount(float $amount): self;

    public function getFee(): float;

    public function setFee(float $fee): self;

    public function getMessage(): string;

    public function setMessage(string $message): self;

    public function getStatus(): string;

    public function setStatus(string $status): self;

    public function getCreatedAt(): string;

    public function setCreatedAt(string $createdAt): self;
}
