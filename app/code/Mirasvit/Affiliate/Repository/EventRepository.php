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
use Magento\Framework\Exception\NoSuchEntityException;
use Mirasvit\Affiliate\Api\Data\EventInterface;
use Mirasvit\Affiliate\Model\EventFactory;
use Mirasvit\Affiliate\Model\ResourceModel\Event\CollectionFactory as EventCollectionFactory;

class EventRepository
{
    private $entityManager;

    private $eventFactory;

    private $eventCollectionFactory;

    public function __construct(
        EntityManager          $entityManager,
        EventFactory           $eventFactory,
        EventCollectionFactory $eventCollectionFactory
    ) {
        $this->entityManager          = $entityManager;
        $this->eventFactory           = $eventFactory;
        $this->eventCollectionFactory = $eventCollectionFactory;
    }

    /**
     * @return EventInterface[]|\Mirasvit\Affiliate\Model\ResourceModel\Event\Collection
     */
    public function getCollection()
    {
        return $this->eventCollectionFactory->create();
    }

    public function create(): EventInterface
    {
        return $this->eventFactory->create();
    }

    public function get(int $eventId): ?EventInterface
    {
        $event = $this->create();
        $this->entityManager->load($event, $eventId);
        if (!$event->getId()) {
            throw NoSuchEntityException::singleField('id', $eventId);
        }

        return $event;
    }

    public function save(EventInterface $event): EventInterface
    {
        return $this->entityManager->save($event);
    }
}
