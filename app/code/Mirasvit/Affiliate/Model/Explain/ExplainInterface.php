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

namespace Mirasvit\Affiliate\Model\Explain;

interface ExplainInterface
{
    public const FORMULA        = 'formula';
    public const FORMULA_STR    = 'formula_str';
    public const FORMULA_RESULT = 'formula_result';

    public const ROWS      = 'rows';
    public const TOTAL     = 'total';
    public const MSG       = 'msg';
    public const AMOUNT    = 'amount';
    public const ITEMS     = 'items';
    public const TYPE      = 'type';
    public const TYPE_LIST = 'list';

    public function addMessage(string $message, float $amount = null, bool $addAmountToFormulaStr = true): void;

    public function getTotal(): float;

    public function setTotal(float $amount): void;

    public function getData(): array;

    public function getRows(): array;
}
