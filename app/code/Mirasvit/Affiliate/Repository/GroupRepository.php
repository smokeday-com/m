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
use Mirasvit\Affiliate\Api\Data\GroupInterface;
use Mirasvit\Affiliate\Model\GroupFactory;
use Mirasvit\Affiliate\Model\ResourceModel\Group\Collection as GroupCollection;
use Mirasvit\Affiliate\Model\ResourceModel\Group\CollectionFactory as GroupCollectionFactory;

class GroupRepository
{
    private $groupFactory;

    private $collectionFactory;

    private $entityManager;

    public function __construct(
        GroupFactory           $groupFactory,
        GroupCollectionFactory $collectionFactory,
        EntityManager          $entityManager
    ) {
        $this->groupFactory      = $groupFactory;
        $this->collectionFactory = $collectionFactory;
        $this->entityManager     = $entityManager;
    }

    public function create(): GroupInterface
    {
        return $this->groupFactory->create();
    }

    /**
     * @return GroupCollection|GroupInterface[]
     */
    public function getCollection()
    {
        return $this->collectionFactory->create();
    }

    /**
     * @return GroupCollection|GroupInterface[]
     */
    public function getAutoUpdateGroupsCollection()
    {
        return $this->getCollection()
            ->addFieldToFilter(GroupInterface::IS_AUTO_UPDATE, true)
            ->setOrder(GroupInterface::PRIORITY, 'ASC');
    }

    public function getManualUpdateGroupsIds(): array
    {
        $ids = [];

        $collection = $this->getCollection()
            ->addFieldToFilter(GroupInterface::IS_AUTO_UPDATE, 0)
            ->addFieldToSelect(GroupInterface::ID);

        foreach ($collection as $group) {
            $ids[] = $group->getId();
        }

        return $ids;
    }

    /**
     * @return GroupCollection|GroupInterface[]
     */
    public function getVisibleGroupsCollection()
    {
        return $this->getCollection()->addFieldToFilter(GroupInterface::IS_VISIBLE, true);
    }

    public function get(int $id): ?GroupInterface
    {
        $model = $this->create();

        $this->entityManager->load($model, $id);

        return $model->getId() ? $model : null;
    }

    public function getDefaultGroup(): ?GroupInterface
    {
        $defaultGroup = $this->getCollection()
            ->addFieldToFilter(GroupInterface::IS_DEFAULT, true)
            ->getFirstItem();

        return $defaultGroup->getId() ? $defaultGroup : null;
    }

    public function save(GroupInterface $model): GroupInterface
    {
        return $this->entityManager->save($model);
    }

    public function delete(GroupInterface $model): void
    {
        $this->entityManager->delete($model);
    }
}
