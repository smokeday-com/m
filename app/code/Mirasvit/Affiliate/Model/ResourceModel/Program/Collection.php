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

namespace Mirasvit\Affiliate\Model\ResourceModel\Program;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\Affiliate\Api\Data\ProgramInterface;
use Psr\Log\LoggerInterface;

class Collection extends AbstractCollection
{
    private $storeManager;

    public function __construct(
        EntityFactoryInterface $entityFactory,
        LoggerInterface        $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface       $eventManager,
        StoreManagerInterface  $storeManager,
        AdapterInterface       $connection = null,
        AbstractDb             $resource = null
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
        $this->storeManager = $storeManager;
    }

    protected function _construct(): void
    {
        $this->_init(\Mirasvit\Affiliate\Model\Program::class, \Mirasvit\Affiliate\Model\ResourceModel\Program::class);
    }

    /**
     * @param null|string|bool|int|Store $store
     * @return $this
     */
    public function addStoreFilter($store = null)
    {
        if ($store === null) {
            $storeId = $this->storeManager->getStore()->getId();
        } elseif ($store instanceof Store) {
            $storeId = $store->getId();
        } else {
            $storeId = $store;
        }

        $this->addFieldToFilter(ProgramInterface::STORE_IDS, [
            ['finset' => 0],
            ['finset' => $storeId],
        ]);

        return $this;
    }

    public function addActiveFilter(): Collection
    {
        $this->addFieldToFilter(ProgramInterface::IS_ACTIVE, ['eq' => 1]);

        $date = date('Y-m-d H:i:s');
        $this->addFieldToFilter(ProgramInterface::ACTIVE_FROM, [
            ['null' => true],
            ['eq' => 0],
            ['lteq' => $date],
        ]);
        $this->addFieldToFilter(ProgramInterface::ACTIVE_TO, [
            ['null' => true],
            ['eq' => 0],
            ['gteq' => $date],
        ]);

        return $this;
    }
}
