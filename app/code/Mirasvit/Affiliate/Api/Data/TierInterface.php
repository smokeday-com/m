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

interface TierInterface
{
    public const RECORD_ID = 'record_id';
    public const GROUP_ID  = 'group_id';
    public const TYPE      = 'type';
    public const AMOUNT    = 'amount';

    public const AMOUNT_FIRST = 'amount_1';
    public const AMOUNT_OTHER = 'amount_n';

    public function loadArray(array $data): self;

    public function getRecordId(): int;

    public function setRecordId(int $id): self;

    public function getGroupId(): int;

    public function getType(): string;

    public function getAmount(array $events = null): float;

    public function getAmountFirst(): float;

    public function getAmountOther(): float;
}
