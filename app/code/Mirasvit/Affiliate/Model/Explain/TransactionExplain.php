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

use Mirasvit\Affiliate\Api\Data\ProgramInterface;
use Mirasvit\Affiliate\Model\ConfigProvider;

class TransactionExplain implements ExplainInterface
{
    private $npp = 0;

    private $nppProgramList = 0;

    private $nppProgramItem = 0;

    private $nppProgram = 0;

    private $nppCurrentProgram = 0;

    private $data = [self::TOTAL => 0.0, self::FORMULA => [], self::ROWS => []];

    public function addMessage(string $message, float $amount = null, bool $addAmountToFormulaStr = true): void
    {
        $data = [self::MSG => $message];
        if ($amount !== null) {
            $data[self::AMOUNT] = $amount;
            if ($addAmountToFormulaStr) {
                $this->addToFormulaStr($amount);
            }
        }

        if ($this->nppCurrentProgram) {
            $this->nppProgramItem++;
            $this->data[self::ROWS][$this->nppProgramList][self::ITEMS][$this->nppCurrentProgram][self::ITEMS][$this->nppProgramItem] = $data;
        } else {
            if ($this->nppProgramList) {
                $this->nppProgram++;
                $this->data[self::ROWS][$this->nppProgramList][self::ITEMS][$this->nppProgram] = $data;
            } else {
                $this->npp++;
                $this->data[self::ROWS][$this->npp] = $data;
            }
        }
    }

    public function stopProgramList(): void
    {
        $this->nppProgramList = 0;
    }

    public function startProgram(ProgramInterface $program): void
    {
        if (!$this->nppProgramList) {
            $this->npp++;
            $this->data[self::ROWS][$this->npp][self::TYPE]  = self::TYPE_LIST;
            $this->data[self::ROWS][$this->npp][self::ITEMS] = [];
            $this->nppProgramList                            = $this->npp;
        }

        $this->nppProgram++;
        $this->nppCurrentProgram = $this->nppProgram;
        $this->nppProgramItem    = 0;
        $message                 = 'Program: ' . $program->getName() . ' (id: ' . $program->getId() . ', priority: ' . $program->getPriority() . ')';

        $this->data[self::ROWS][$this->nppProgramList][self::ITEMS][$this->nppCurrentProgram][self::TYPE]  = self::TYPE_LIST;
        $this->data[self::ROWS][$this->nppProgramList][self::ITEMS][$this->nppCurrentProgram][self::MSG]   = $message;
        $this->data[self::ROWS][$this->nppProgramList][self::ITEMS][$this->nppCurrentProgram][self::ITEMS] = [];
    }

    public function stopProgram(): void
    {
        $this->nppCurrentProgram = 0;
        $this->nppProgramItem    = 0;
    }

    public function addCalculationExplain(CalculationExplainInterface $explain): void
    {
        if ($this->nppCurrentProgram) {

            foreach ($explain->getRows() as $row) {
                $this->nppProgramItem++;
                $this->data[self::ROWS][$this->nppProgramList][self::ITEMS][$this->nppCurrentProgram][self::ITEMS][$this->nppProgramItem] = $row;
            }
            $this->addToFormulaStr($explain->getTotal());

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
