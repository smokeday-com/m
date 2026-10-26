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

namespace Mirasvit\Affiliate\Service;

use DateTime;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Api\Data\EventInterface;
use Mirasvit\Affiliate\Api\Data\TransactionInterface;
use Mirasvit\Affiliate\Model\Chart;
use Mirasvit\Affiliate\Model\ChartFactory;
use Mirasvit\Affiliate\Model\ConfigProvider;
use Mirasvit\Affiliate\Repository\EventRepository;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ChartService
{
    private $currencyFactory;

    private $configProvider;

    private $sessionService;

    private $eventRepository;

    private $resourceConnection;

    private $storeManager;

    private $chartFactory;

    public function __construct(
        CurrencyFactory       $currencyFactory,
        ConfigProvider        $configProvider,
        SessionService        $sessionService,
        EventRepository       $eventRepository,
        ResourceConnection    $resourceConnection,
        StoreManagerInterface $storeManager,
        ChartFactory          $chartFactory
    ) {
        $this->currencyFactory    = $currencyFactory;
        $this->configProvider     = $configProvider;
        $this->sessionService     = $sessionService;
        $this->eventRepository    = $eventRepository;
        $this->resourceConnection = $resourceConnection;
        $this->storeManager       = $storeManager;
        $this->chartFactory       = $chartFactory;
    }

    public function getChartConfig(DateTime $from, DateTime $to, string $currencyCode = null, bool $isCurrentSite = true): array
    {
        $baseCurrencyCode = $this->storeManager->getStore()->getBaseCurrencyCode();
        $rate             = $currencyCode ? (float)$this->currencyFactory->create()->load($baseCurrencyCode)->getAnyRate($currencyCode) : 1.0;

        $chart = $this->chartFactory->create([
            'data' => [
                Chart::FROM          => $from,
                Chart::TO            => $to,
                Chart::CURRENCY_RATE => $rate,
            ],
        ]);

        $chart->addDatasetData(
            'Balance',
            Chart::TYPE_LINE,
            '#2da562',
            $this->getBalanceDatasetData($chart, $isCurrentSite)
        );

        $chart->addDatasetData(
            'Earned',
            Chart::TYPE_BAR,
            '#FF6A51',
            $this->getEarnedDatasetData($chart, $isCurrentSite)
        );

        return $chart->getChartConfig();
    }

    private function getBalanceDatasetData(Chart $chart, bool $isCurrentSite): array
    {
        $account = $this->getAccount();
        if (!$account) {
            return [];
        }

        $storeId              = (int)$this->storeManager->getStore()->getId();
        $accountId            = $account->getId();
        $from                 = $chart->getFrom()->format('Y-m-d');
        $to                   = $chart->getTo()->format('Y-m-d');
        $transactionStatus    = TransactionInterface::STATUS_COMPLETE;
        $selectDateExpression = (string)$this->getDateExpression($chart, 'main_table');
        $groupDateExpression  = (string)$this->getDateExpression($chart);
        $transactionTable     = $this->resourceConnection->getTableName('mst_affiliate_transaction');

        $connection = $this->resourceConnection->getConnection();

        $subQuery = $connection->select()
            ->from($transactionTable)
            ->reset(Select::COLUMNS)
            ->columns(['max_id' => new \Zend_Db_Expr('MAX(transaction_id)')]);
        if ($isCurrentSite) {
            $subQuery->where('(store_id = 0) OR (store_id = ?)', (int)$storeId);
        }
        $subQuery->where('account_id = ?', $accountId)
            ->where('status = ?', $transactionStatus)
            ->where('created_at >= ?', $from)
            ->where('created_at <= ?', $to)
            ->group(new \Zend_Db_Expr($groupDateExpression));

        $query = $connection->select()
            ->from(['main_table' => $transactionTable])
            ->join(['t2' => $subQuery], 'main_table.transaction_id = t2.max_id')
            ->reset(Select::COLUMNS)
            ->columns([
                'x' => $selectDateExpression,
                'y' => 'main_table.balance',
            ]);

        $data = [];
        foreach ($connection->fetchAll($query) as ['x' => $x, 'y' => $y]) {
            $data[] = [
                'x' => $x,
                'y' => (float)$y * $chart->getCurrencyRate(),
            ];
        }

        return $data;
    }

    private function getEarnedDatasetData(Chart $chart, bool $isCurrentSite): array
    {
        $account = $this->getAccount();
        if (!$account) {
            return [];
        }

        $dateExpression = (string)$this->getDateExpression($chart, 'main_table');
        $collection     = $this->eventRepository->getCollection();
        if ($isCurrentSite) {
            $collection->addStoreFilter();
        }
        $collection->addFieldToFilter(EventInterface::ACCOUNT_ID, $account->getId())
            ->addFieldToFilter(EventInterface::CREATED_AT, ['gteq' => $chart->getFrom()])
            ->addFieldToFilter(EventInterface::CREATED_AT, ['lteq' => $chart->getTo()]);
        $collection->getSelect()->group([
            $dateExpression,
        ])->columns([
            'x' => $dateExpression,
            'y' => (string)(new \Zend_Db_Expr("SUM(commission)")),
        ])->having('y>?', 0);

        $data = [];
        foreach ($collection as $item) {
            $data[] = [
                'x' => $item->getData('x'),
                'y' => (float)$item->getData('y') * $chart->getCurrencyRate(),
            ];
        }

        return $data;
    }

    private function getDateExpression(Chart $chart, string $tableAlias = '', string $dateFieldName = 'created_at'): \Zend_Db_Expr
    {
        $yearWeekMode       = $this->configProvider->isFirstDayMonday() ? 1 : 0;
        $firstDay           = $this->configProvider->isFirstDayMonday() ? 'Monday' : 'Sunday';
        $fieldNameWithTable = trim("$tableAlias.$dateFieldName", '.');

        switch ($chart->getPeriod()) {
            case Chart::PERIOD_DAY:
                return new \Zend_Db_Expr("DATE($fieldNameWithTable)");
            case Chart::PERIOD_WEEK:
                return new \Zend_Db_Expr("STR_TO_DATE(CONCAT(YEARWEEK($fieldNameWithTable, $yearWeekMode),' $firstDay'), '%x%v %W')");
            default:
                return new \Zend_Db_Expr("STR_TO_DATE(DATE_FORMAT(DATE_ADD($fieldNameWithTable, interval 1 month), '%Y-%m-01'), '%Y-%m-%d')");
        }
    }

    public function getAccount(): ?AccountInterface
    {
        return $this->sessionService->getAccount();
    }
}
