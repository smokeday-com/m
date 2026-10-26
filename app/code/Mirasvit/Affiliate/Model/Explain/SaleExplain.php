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

use Magento\Sales\Api\Data\OrderItemInterface;
use Mirasvit\Affiliate\Model\ConfigProvider;

class SaleExplain implements CalculationExplainInterface
{
    private $npp = 0;

    private $nppProductList = 0;

    private $nppProductItem = 0;

    private $nppProduct = 0;

    private $nppCurrentProduct = 0;

    private $data = [self::TOTAL => 0.0, self::FORMULA => [], self::ROWS => []];

    public function startProduct(OrderItemInterface $orderItem): void
    {
        if (!$this->nppProductList) {
            $this->npp++;
            $this->data[self::ROWS][$this->npp][self::TYPE]  = self::TYPE_LIST;
            $this->data[self::ROWS][$this->npp][self::ITEMS] = [];
            $this->nppProductList                            = $this->npp;
        }

        $this->nppProduct++;
        $this->nppCurrentProduct = $this->nppProduct;
        $this->nppProductItem    = 0;

        $message = 'Product: ' . $orderItem->getName() . ' (ID: ' . $orderItem->getProductId() . ', SKU: ' . $orderItem->getSku() . ')';

        $this->data[self::ROWS][$this->nppProductList][self::ITEMS][$this->nppCurrentProduct][self::TYPE]  = self::TYPE_LIST;
        $this->data[self::ROWS][$this->nppProductList][self::ITEMS][$this->nppCurrentProduct][self::MSG]   = $message;
        $this->data[self::ROWS][$this->nppProductList][self::ITEMS][$this->nppCurrentProduct][self::ITEMS] = [];
    }

    public function stopProduct(): void
    {
        $this->nppCurrentProduct = 0;
        $this->nppProductItem    = 0;
    }

    public function addMessage(string $message, float $amount = null, bool $addAmountToFormulaStr = true): void
    {
        $data = [self::MSG => $message];
        if ($amount !== null) {
            $data[self::AMOUNT] = $amount;
            if ($addAmountToFormulaStr) {
                $this->addToFormulaStr($amount);
            }

        }

        if ($this->nppCurrentProduct) {
            $this->nppProductItem++;
            $this->data[self::ROWS][$this->nppProductList][self::ITEMS][$this->nppCurrentProduct][self::ITEMS][$this->nppProductItem] = $data;
        } else {
            $this->npp++;
            $this->data[self::ROWS][$this->npp] = $data;
        }
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getFormulaStr(): string
    {
        return $this->data[self::FORMULA][self::FORMULA_STR] ?? '';
    }

    public function getFormulaResult(): float
    {
        return $this->data[self::FORMULA][self::FORMULA_RESULT] ?? 0.0;
    }

    public function setFormula(string $formula): void
    {
        $this->data[self::FORMULA] = $formula;
    }

    public function setFormulaStr(string $formulaStr): void
    {
        $this->data[self::FORMULA][self::FORMULA_STR] = $formulaStr;
    }

    public function setFormulaResult(float $formulaResult): void
    {
        $this->data[self::FORMULA][self::FORMULA_RESULT] = $formulaResult;
    }

    public function getTotal(): float
    {
        return $this->data[self::TOTAL] ?? 0.0;
    }

    public function setTotal(float $amount): void
    {
        $this->data[self::TOTAL] = $amount;
    }

    public function getRows(): array
    {
        return $this->data[self::ROWS] ?? [];
    }

    private function addToFormulaStr(float $amount): void
    {
        if (abs($amount) < ConfigProvider::FLOAT_EPSILON) {
            return;
        }

        $formula = $this->data[self::FORMULA][self::FORMULA_STR] ?? '';

        if ($amount > ConfigProvider::FLOAT_EPSILON) {
            $formula .= ($formula) ? ' + ' : '';
        }

        if ($amount < -ConfigProvider::FLOAT_EPSILON) {
            $formula .= ($formula) ? ' - ' : '-';
        }

        $formula .= abs($amount);

        $this->data[self::FORMULA][self::FORMULA_STR] = $formula;
    }
}
