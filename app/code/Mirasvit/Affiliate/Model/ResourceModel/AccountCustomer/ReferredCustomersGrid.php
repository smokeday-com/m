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

namespace Mirasvit\Affiliate\Model\ResourceModel\AccountCustomer;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Mirasvit\Affiliate\Api\Data\AccountCustomerInterface;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Model\ResourceModel\AccountCustomer;
use Psr\Log\LoggerInterface as Logger;

class ReferredCustomersGrid extends SearchResult
{
    protected $document = \Mirasvit\Affiliate\Model\AccountCustomer::class;

    public function __construct(
        EntityFactory $entityFactory,
        Logger        $logger,
        FetchStrategy $fetchStrategy,
        EventManager  $eventManager,
        string        $mainTable = null,
        string        $resourceModel = null
    ) {
        $mainTable     = AccountCustomerInterface::TABLE_NAME;
        $resourceModel = AccountCustomer::class;

        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
    }

    protected function _construct()
    {
        $this->_init(
            \Magento\Framework\View\Element\UiComponent\DataProvider\Document::class,
            \Mirasvit\Affiliate\Model\ResourceModel\AccountCustomer::class
        );
    }

    protected function _initSelect()
    {
        parent::_initSelect();

        $this->getSelect()->joinLeft(
            ['customer' => $this->getTable('customer_entity')],
            'main_table.customer_id = customer.entity_id',
            [
                'customer_email'     => 'customer.' . CustomerInterface::EMAIL,
                'customer_firstname' => 'customer.' . CustomerInterface::FIRSTNAME,
                'customer_lastname'  => 'customer.' . CustomerInterface::LASTNAME,
            ]
        )->joinLeft(
            ['affiliate' => $this->getTable(AccountInterface::TABLE_NAME)],
            'main_table.account_id = affiliate.' . AccountInterface::ID,
            []
        )->joinLeft(
            ['affiliate_customer' => $this->getTable('customer_entity')],
            'affiliate.customer_id = affiliate_customer.entity_id',
            [
                'affiliate_email'     => 'affiliate_customer.' . CustomerInterface::EMAIL,
                'affiliate_firstname' => 'affiliate_customer.' . CustomerInterface::FIRSTNAME,
                'affiliate_lastname'  => 'affiliate_customer.' . CustomerInterface::LASTNAME,
            ]
        );

        $this->addFilterToMap('customer_email', 'customer.' . CustomerInterface::EMAIL);
        $this->addFilterToMap('customer_firstname', 'customer.' . CustomerInterface::FIRSTNAME);
        $this->addFilterToMap('customer_lastname', 'customer.' . CustomerInterface::LASTNAME);
        $this->addFilterToMap('affiliate_email', 'affiliate_customer.' . CustomerInterface::EMAIL);
        $this->addFilterToMap('affiliate_firstname', 'affiliate_customer.' . CustomerInterface::FIRSTNAME);
        $this->addFilterToMap('affiliate_lastname', 'affiliate_customer.' . CustomerInterface::LASTNAME);

        return $this;
    }
}
