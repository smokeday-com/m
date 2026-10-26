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

interface AccountCustomerInterface
{
    public const TABLE_NAME = 'mst_affiliate_account_customer';

    public const ID             = 'id';
    public const ACCOUNT_ID     = 'account_id';
    public const CUSTOMER_ID    = 'customer_id';
    public const SUM_COMMISSION = 'sum_commission';
    public const CREATED_AT     = 'created_at';
    public const UPDATED_AT     = 'updated_at';

    public function getId(): int;

    public function getAccountId(): int;

    public function setAccountId(int $id): self;

    public function getCustomerId(): int;

    public function setCustomerId(int $id): self;

    public function getSumCommission(): float;

    public function setSumCommission(float $sumCommission): self;

    public function getCreatedAt(): string;

    public function setCreatedAt(string $createdAt): self;

    public function getUpdatedAt(): string;

    public function setUpdatedAt(string $updatedAt): self;
}
