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
use Mirasvit\Affiliate\Api\Data\ProgramInterface;
use Mirasvit\Affiliate\Api\Data\ProgramInterfaceFactory;
use Mirasvit\Affiliate\Model\ResourceModel\Program\CollectionFactory;

class ProgramRepository
{
    private $entityManager;

    private $factory;

    private $collectionFactory;

    public function __construct(
        EntityManager $entityManager,
        ProgramInterfaceFactory $factory,
        CollectionFactory $collectionFactory
    ) {
        $this->entityManager     = $entityManager;
        $this->factory           = $factory;
        $this->collectionFactory = $collectionFactory;
    }

    public function create(): ProgramInterface
    {
        return $this->factory->create();
    }

    /**
     * @return ProgramInterface[]|\Mirasvit\Affiliate\Model\ResourceModel\Program\Collection
     */
    public function getCollection()
    {
        return $this->collectionFactory->create();
    }

    public function get(int $id): ?ProgramInterface
    {
        $program = $this->create();

        $this->entityManager->load($program, $id);

        return $program->getId() ? $program : null;
    }

    public function delete(ProgramInterface $program): bool
    {
        $this->entityManager->delete($program);

        return true;
    }

    public function save(ProgramInterface $program): ProgramInterface
    {
        return $this->entityManager->save($program);
    }
}
