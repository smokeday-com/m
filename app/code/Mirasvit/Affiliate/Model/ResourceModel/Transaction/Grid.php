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

namespace Mirasvit\Affiliate\Model\ResourceModel\Transaction;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Mirasvit\Affiliate\Api\Data\TransactionInterface;
use Mirasvit\Affiliate\Repository\AccountRepository;
use Psr\Log\LoggerInterface as Logger;

/**
 * @SuppressWarnings(PHPMD)
 */
class Grid extends SearchResult
{
    private $accountRepository;

    protected $document = \Mirasvit\Affiliate\Model\Transaction::class;

    public function __construct(
        AccountRepository $accountRepository,
        EntityFactory     $entityFactory,
        Logger            $logger,
        FetchStrategy     $fetchStrategy,
        EventManager      $eventManager,
        string            $mainTable = null,
        string            $resourceModel = null
    ) {
        $mainTable     = TransactionInterface::TABLE_NAME;
        $resourceModel = \Mirasvit\Affiliate\Model\ResourceModel\Transaction::class;

        $this->accountRepository = $accountRepository;

        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
    }

    protected function _construct()
    {
        $this->_init(
            \Magento\Framework\View\Element\UiComponent\DataProvider\Document::class,
            \Mirasvit\Affiliate\Model\ResourceModel\Transaction::class
        );
    }

    /**
     * {@inheritDoc}
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ($field === 'account') {
            $field = 'customer_entity.email';
        } else {
            if (strpos((string)$field, '.') === false) {
                $field = 'main_table.' . $field;
            }
        }

        return parent::addFieldToFilter($field, $condition);
    }

    /**
     * {@inheritDoc}
     */
    protected function _initSelect()
    {
        $this->_select->joinLeft(
            ['account' => $this->getTable('mst_affiliate_account')],
            'account.account_id = main_table.account_id',
            null
        )->joinLeft(
            ['customer_entity' => $this->getTable('customer_entity')],
            'customer_entity.entity_id = account.customer_id',
            ['email']
        );

        return parent::_initSelect();
    }

    /**
     * {@inheritDoc}
     */
    public function getItems()
    {
        $items = parent::getItems();

        foreach ($items as $item) {
            $accountId = (int)$item->getAccountId();
            $account   = $this->accountRepository->get($accountId);
            $email     = $account ? $account->getEmail() : '';
            $item->setData('account', $email);
        }

        return $items;
    }
}
