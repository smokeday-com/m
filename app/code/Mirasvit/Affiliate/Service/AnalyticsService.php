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

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ResourceConnection;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Api\Data\BannerInterface;
use Mirasvit\Affiliate\Api\Data\EventInterface;
use Mirasvit\Affiliate\Api\Data\TransactionInterface;
use Mirasvit\Affiliate\Api\Data\WithdrawInterface;
use Mirasvit\Affiliate\Repository\AccountRepository;
use Mirasvit\Affiliate\Repository\EventRepository;

class AnalyticsService
{
    private $eventRepository;

    private $searchCriteriaBuilder;

    private $resource;

    private $connection;

    private $accountRepository;

    public function __construct(
        EventRepository $eventRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ResourceConnection $resource,
        AccountRepository $accountRepository
    ) {
        $this->eventRepository       = $eventRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->resource              = $resource;
        $this->connection            = $resource->getConnection();
        $this->accountRepository     = $accountRepository;
    }

    public function getBannerClicks(BannerInterface $banner, AccountInterface $account = null): int
    {
        $collection = $this->eventRepository->getCollection();

        $collection
            ->addStoreFilter()
            ->addFieldToFilter('type', EventInterface::TYPE_CLICK)
            ->addFieldToFilter('entity_id', $banner->getId());

        if ($account) {
            $collection->addFieldToFilter('account_id', $account->getId());
        }

        return (int)$collection->getSize();
    }

    public function getBannerImpression(BannerInterface $banner, AccountInterface $account = null): int
    {
        $collection = $this->eventRepository->getCollection();

        $collection
            ->addStoreFilter()
            ->addFieldToFilter('type', EventInterface::TYPE_IMPRESSION)
            ->addFieldToFilter('entity_id', $banner->getId());

        if ($account) {
            $collection->addFieldToFilter('account_id', $account->getId());
        }

        return (int)$collection->getSize();
    }

    public function getAccountClicks(AccountInterface $account): int
    {
        $collection = $this->eventRepository->getCollection();

        $collection
            ->addStoreFilter()
            ->addFieldToFilter(EventInterface::TYPE, EventInterface::TYPE_CLICK)
            ->addFieldToFilter(EventInterface::ACCOUNT_ID, $account->getId());

        return (int)$collection->getSize();
    }

    public function getActiveAccounts(string $from): int
    {
        $select = $this->connection->select()
            ->from(
                $this->resource->getTableName(EventInterface::TABLE_NAME),
                [new \Zend_Db_Expr('COUNT(DISTINCT(account_id))')]
            )->where('created_at > ?', $from);

        return (int)$this->connection->fetchOne($select);
    }

    public function getTotalAffiliates(): int
    {
        $select = $this->connection->select()
            ->from(
                $this->resource->getTableName(EventInterface::TABLE_NAME),
                [new \Zend_Db_Expr('COUNT(DISTINCT(account_id))')]
            );

        return (int)$this->connection->fetchOne($select);
    }

    public function getVisits(string $from): int
    {
        $select = $this->connection->select()
            ->from(
                $this->resource->getTableName(EventInterface::TABLE_NAME),
                [new \Zend_Db_Expr('COUNT(event_id)')]
            )->where('created_at > ?', $from)
            ->where('type = ?', 'click');

        return (int)$this->connection->fetchOne($select);
    }

    public function getRevenue(string $from = null): float
    {
        $select = $this->connection->select()
            ->from(
                $this->resource->getTableName(EventInterface::TABLE_NAME),
                [new \Zend_Db_Expr('SUM(amount)')]
            )->where('type = ?', EventInterface::TYPE_SALE);

        if ($from) {
            $select->where('created_at > ?', $from);
        }

        return (float)$this->connection->fetchOne($select);
    }

    public function getCommissions(string $from = null): float
    {
        $select = $this->connection->select()
            ->from(
                $this->resource->getTableName(EventInterface::TABLE_NAME),
                [new \Zend_Db_Expr('SUM(commission)')]
            );

        if ($from) {
            $select->where('created_at > ?', $from);
        }

        return (float)$this->connection->fetchOne($select);
    }

    public function getHoldedComissions(): float
    {
        $select = $this->connection->select()
            ->from(
                $this->resource->getTableName(TransactionInterface::TABLE_NAME),
                [new \Zend_Db_Expr('SUM(amount)')]
            )->where('status = ?', TransactionInterface::STATUS_IN_HOLD);

        return (float)$this->connection->fetchOne($select);
    }

    public function getTotalWithdrawals(): float
    {
        $select =  $this->connection->select()
            ->from(
                $this->resource->getTableName(WithdrawInterface::TABLE_NAME),
                [new \Zend_Db_Expr('SUM(amount)')]
            );

        return (float)$this->connection->fetchOne($select);
    }
}
