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

interface CouponInterface
{
    public const TABLE_NAME = 'mst_affiliate_coupon';

    public const ID         = 'coupon_id';
    public const ACCOUNT_ID = 'account_id';
    public const PROGRAM_ID = 'program_id';
    public const CODE       = 'code';

    public function getId(): int;

    public function getAccountId(): int;

    public function setAccountId(int $input): self;

    public function getProgramId(): int;

    public function setProgramId(int $input): self;

    public function getCode(): string;

    public function setCode(string $input): self;
}
