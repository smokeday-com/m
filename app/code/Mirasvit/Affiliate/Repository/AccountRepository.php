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
use Magento\Framework\Filter\FilterManager;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Model\Account;
use Mirasvit\Affiliate\Model\AccountFactory;
use Mirasvit\Affiliate\Model\ResourceModel\Account\Collection;
use Mirasvit\Affiliate\Model\ResourceModel\Account\CollectionFactory as AccountCollectionFactory;

class AccountRepository
{
    private $entityManager;

    private $accountFactory;

    private $accountCollectionFactory;

    private $filterManager;

    public function __construct(
        EntityManager            $entityManager,
        AccountFactory           $accountFactory,
        AccountCollectionFactory $accountCollectionFactory,
        FilterManager            $filterManager
    ) {
        $this->entityManager            = $entityManager;
        $this->accountFactory           = $accountFactory;
        $this->accountCollectionFactory = $accountCollectionFactory;
        $this->filterManager            = $filterManager;
    }

    /**
     * @return AccountInterface[]|Collection
     */
    public function getCollection()
    {
        return $this->accountCollectionFactory->create();
    }

    public function create(): AccountInterface
    {
        return $this->accountFactory->create();
    }

    public function get(int $id): ?AccountInterface
    {
        $account = $this->create();
        $this->entityManager->load($account, $id);

        if ($account->getId()) {
            $account->setOrigData($account->getOrigData());
        }

        return $account->getId() ? $account : null;
    }

    public function getByIdentifier(string $identifier): ?AccountInterface
    {
        $account = $this->accountFactory->create();
        $account->load($identifier, AccountInterface::IDENTIFIER);

        return $account->getId() ? $account : null;
    }

    public function getByCustomerId(int $customerId): ?AccountInterface
    {
        $account = $this->accountFactory->create();
        $account->load($customerId, AccountInterface::CUSTOMER_ID);

        return $account->getId() ? $account : null;
    }

    /**
     * @param int $groupId
     *
     * @return AccountInterface[]|Collection
     */
    public function getByGroupId(int $groupId)
    {
        $collection = $this->getCollection()->addFieldToFilter('group_id', $groupId);

        return $collection;
    }

    public function delete(AccountInterface $account): bool
    {
        $this->entityManager->delete($account);

        return true;
    }

    public function save(AccountInterface $account): AccountInterface
    {
        /** @var Account $account */
        if (!$account->getIdentifier()) {
            $account->setIdentifier(hash('md5', (string)$account->getCustomerId()));
        }

        $account->setPaymentEmail($this->filterManager->stripTags($account->getPaymentEmail()));
        $account->setPaymentMethod($this->filterManager->stripTags($account->getPaymentMethod()));

        $additionalData = $account->getAdditionalDataSerialized();
        if ($additionalData) {
            $account->setAdditionalDataSerialized($this->filterManager->stripTags($additionalData));
        }

        /**
         * @var AccountInterface
         */
        return $this->entityManager->save($account);
    }
}
