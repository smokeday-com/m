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

namespace Mirasvit\Affiliate\Program;

use Magento\Framework\DataObject;
use Mirasvit\Affiliate\Model\Explain\CalculationExplainInterface;

class CalculationResult extends DataObject
{
    private const AMOUNT  = 'amount';
    private const EXPLAIN = 'explain';

    public function getAmount(): float
    {
        return (float)$this->getData(self::AMOUNT);
    }

    public function setAmount(float $amount): CalculationResult
    {
        return $this->setData(self::AMOUNT, $amount);
    }

    public function getExplain(): ?CalculationExplainInterface
    {
        return $this->getData(self::EXPLAIN);
    }

    public function setExplain(CalculationExplainInterface $explain): CalculationResult
    {
        return $this->setData(self::EXPLAIN, $explain);
    }
}
