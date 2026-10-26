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

namespace Mirasvit\Affiliate\Repository;

use Magento\Framework\EntityManager\EntityManager;
use Mirasvit\Affiliate\Api\Data\AccountCustomerInterface;
use Mirasvit\Affiliate\Api\Data\AccountCustomerInterfaceFactory;
use Mirasvit\Affiliate\Model\ResourceModel\AccountCustomer\CollectionFactory;

class AccountCustomerRepository
{
    private $entityManager;

    private $accountCustomerFactory;

    private $collectionFactory;

    public function __construct(
        EntityManager $entityManager,
        AccountCustomerInterfaceFactory $accountCustomerFactory,
        CollectionFactory $collectionFactory
    ) {
        $this->entityManager          = $entityManager;
        $this->accountCustomerFactory = $accountCustomerFactory;
        $this->collectionFactory      = $collectionFactory;
    }

    public function create(): AccountCustomerInterface
    {
        return $this->accountCustomerFactory->create();
    }

    public function get(int $id): ?AccountCustomerInterface
    {
        $relation = $this->create();

        $this->entityManager->load($relation, $id);

        return $relation->getId() ? $relation : null;
    }

    /**
     * @return \Mirasvit\Affiliate\Model\ResourceModel\AccountCustomer\Collection | AccountCustomerInterface[]
     */
    public function getCollection()
    {
        return $this->collectionFactory->create();
    }

    public function save(AccountCustomerInterface $relation): AccountCustomerInterface
    {
        return $this->entityManager->save($relation);
    }

    public function delete(AccountCustomerInterface $relation): bool
    {
        $this->entityManager->delete($relation);

        return true;
    }

    public function getByCustomerId(int $customerId): ?AccountCustomerInterface
    {
        $model = $this->create();
        $model->load($customerId, AccountCustomerInterface::CUSTOMER_ID);
        if (!$model->getId()) {
            return null;
        }

        return $model;
    }
}
