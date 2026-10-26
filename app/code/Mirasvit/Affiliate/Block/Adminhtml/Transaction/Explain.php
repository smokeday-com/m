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

namespace Mirasvit\Affiliate\Block\Adminhtml\Transaction;

use Magento\Backend\Block\Template;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Mirasvit\Affiliate\Api\Data\TransactionInterface;
use Mirasvit\Affiliate\Model\ConfigProvider;
use Mirasvit\Affiliate\Model\Explain\ExplainInterface;
use Mirasvit\Affiliate\Repository\TransactionRepository;

class Explain extends Template
{
    private const CLASS_POSITIVE = 'positive';
    private const CLASS_NEGATIVE = 'negative';

    private $priceCurrency;

    private $transactionRepository;

    private $dataObjectFactory;

    private $level = 0;

    public function __construct(
        PriceCurrencyInterface $priceCurrency,
        TransactionRepository  $transactionRepository,
        DataObjectFactory      $dataObjectFactory,
        Template\Context       $context
    ) {
        $this->priceCurrency         = $priceCurrency;
        $this->transactionRepository = $transactionRepository;
        $this->dataObjectFactory     = $dataObjectFactory;
        parent::__construct($context);
    }

    public function getExplainData(): array
    {
        $transaction = $this->getTransaction();
        if (!$transaction) {
            return [];
        }

        $data        = $transaction->getExplain();
        $explainRows = $data[ExplainInterface::ROWS] ?? [];
        $explainData = [];
        foreach ($explainRows as $item) {
            $rows = $this->getRows($item);
            foreach ($rows as $row) {
                $explainData[] = $row;
            }
        }

        return $explainData;
    }

    /**
     * @param array $item
     * @return DataObject[]
     */
    private function getRows(array $item): array
    {
        $rows = [];
        if (isset($item[ExplainInterface::MSG])) {
            $rows[] = $this->getRow($item);
        }

        if (($item[ExplainInterface::TYPE] ?? '') == ExplainInterface::TYPE_LIST) {

            $this->level++;
            foreach ($item[ExplainInterface::ITEMS] ?? [] as $listItem) {
                foreach ($this->getRows($listItem) as $row) {
                    $rows[] = $row;
                }
            }
            $this->level--;
        }

        return $rows;
    }

    private function getTransaction(): ?TransactionInterface
    {
        $transactionId = (int)$this->_request->getParam(TransactionInterface::ID);
        if (!$transactionId) {
            return null;
        }

        $transaction = $this->transactionRepository->get($transactionId);
        if (!$transaction->getId()) {
            return null;
        }

        return $transaction;
    }

    private function getAmountSignCssClass(float $amount): string
    {
        if ($amount > ConfigProvider::FLOAT_EPSILON) {
            return self::CLASS_POSITIVE;
        }
        if ($amount < -ConfigProvider::FLOAT_EPSILON) {
            return self::CLASS_NEGATIVE;
        }

        return '';
    }

    private function getRow(array $item): DataObject
    {
        $data = [
            'level' => $this->level,
            'msg'   => $item[ExplainInterface::MSG],
        ];

        if (array_key_exists(ExplainInterface::AMOUNT, $item)) {
            $amount         = $item[ExplainInterface::AMOUNT] ?? 0.0;
            $data['amount'] = $this->priceCurrency->format($amount, false, 4);
            $data['sign']   = $this->getAmountSignCssClass((float)$amount);
        }

        return $this->dataObjectFactory->create(['data' => $data,]);
    }
}
