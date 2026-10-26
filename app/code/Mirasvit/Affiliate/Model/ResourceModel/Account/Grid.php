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

namespace Mirasvit\Affiliate\Model\ResourceModel\Account;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Mirasvit\Affiliate\Api\Data\TransactionInterface;

/**
 * @SuppressWarnings(PHPMD)
 */
class Grid extends SearchResult
{
    protected $document = \Mirasvit\Affiliate\Model\Account::class;

    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        string $mainTable = null,
        string $resourceModel = null
    ) {
        $mainTable     = AccountInterface::TABLE_NAME;
        $resourceModel = \Mirasvit\Affiliate\Model\ResourceModel\Account::class;

        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
    }

    /**
     * {@inheritDoc}
     */
    public function addFieldToFilter($field, $condition = null)
    {
        $expr = 'CONCAT(customer_entity.firstname, " ", customer_entity.lastname)';

        if ($field === 'main_table.email') {
            $field = 'customer_entity.email';
        }

        if ($field === 'main_table.name') {
            $field = new \Zend_Db_Expr($expr);
        }

        return parent::addFieldToFilter($field, $condition);
    }

    /**
     * {@inheritDoc}
     */
    protected function _initSelect()
    {
        $expr = 'CONCAT(customer_entity.firstname, " ", customer_entity.lastname)';

        $sqlSumHold = sprintf(
            'SELECT %2$s, SUM(%3$s) as in_hold FROM %1$s WHERE %4$s="%5$s" GROUP BY %2$s',
            $this->getTable(TransactionInterface::TABLE_NAME),
            TransactionInterface::ACCOUNT_ID,
            TransactionInterface::AMOUNT,
            TransactionInterface::STATUS,
            TransactionInterface::STATUS_IN_HOLD
        );

        $this->_select->joinLeft(
            ['customer_entity' => $this->getTable('customer_entity')],
            'customer_entity.entity_id = main_table.customer_id',
            ['email' => 'email', 'name' => new \Zend_Db_Expr($expr)]
        )->joinLeft(
            ['t' => new \Zend_Db_Expr("($sqlSumHold)")],
            't.account_id=main_table.account_id',
            ['in_hold' => 'in_hold']
        );

        return parent::_initSelect();
    }
}
