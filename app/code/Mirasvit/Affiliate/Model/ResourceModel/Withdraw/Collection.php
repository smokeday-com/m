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

namespace Mirasvit\Affiliate\Model\ResourceModel\Withdraw;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Collection extends AbstractCollection
{
    private $storeManager;

    public function __construct(
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        StoreManagerInterface $storeManager,
        AdapterInterface $connection = null,
        AbstractDb $resource = null
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
        $this->storeManager = $storeManager;
    }

    protected function _construct(): void
    {
        $this->_init(\Mirasvit\Affiliate\Model\Withdraw::class, \Mirasvit\Affiliate\Model\ResourceModel\Withdraw::class);
    }

    public function joinAccount(): self
    {
        $this->getSelect()
            ->joinLeft(
                ['account' => $this->getTable('mst_affiliate_account')],
                'main_table.account_id = account.account_id',
                []
            )
            ->joinLeft(
                ['customer' => $this->getTable('customer_entity')],
                'account.customer_id = customer.entity_id',
                [
                    'email' => 'email',
                    'name'  => new \Zend_Db_Expr('CONCAT(customer.firstname, " ", customer.lastname)')
                ]
            );

        return $this;
    }
}
