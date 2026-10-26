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

use Magento\Framework\DataObject;
use Mirasvit\Affiliate\Api\Data\TierInterface;

class Tier extends DataObject implements TierInterface
{
    public function loadArray(array $data): TierInterface
    {
        foreach ($data as $key => $value) {
            $this->setData($key, $value);
        }

        return $this;
    }

    public function getGroupId(): int
    {
        return (int)$this->getData(self::GROUP_ID);
    }

    public function getType(): string
    {
        return (string)$this->getData(self::TYPE);
    }

    public function getAmount(array $events = null): float
    {
        return (float)$this->getData(self::AMOUNT);
    }

    public function getRecordId(): int
    {
        return (int)$this->getData(self::RECORD_ID);
    }

    public function setRecordId(int $id): TierInterface
    {
        return $this->setData(self::RECORD_ID, $id);
    }

    public function getAmountFirst(): float
    {
        return (float)$this->getData(self::AMOUNT_FIRST);
    }

    public function getAmountOther(): float
    {
        return (float)$this->getData(self::AMOUNT_OTHER);
    }
}
