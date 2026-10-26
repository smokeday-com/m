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

namespace Mirasvit\Affiliate\Cron;

use Magento\Framework\Data\Collection;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Api\Data\EventInterface;
use Mirasvit\Affiliate\Api\Data\ProgramInterface;
use Mirasvit\Affiliate\Model\EventHandlerFactory;
use Mirasvit\Affiliate\Repository\AccountRepository;
use Mirasvit\Affiliate\Repository\EventRepository;

class HandleEventsCron
{
    private $eventRepository;

    private $accountRepository;

    private $eventHandlerFactory;

    public function __construct(
        AccountRepository   $accountRepository,
        EventRepository     $eventRepository,
        EventHandlerFactory $eventHandlerFactory
    ) {
        $this->accountRepository   = $accountRepository;
        $this->eventRepository     = $eventRepository;
        $this->eventHandlerFactory = $eventHandlerFactory;
    }

    public function execute(): void
    {
        // IMPRESSION events
        $groupedCollection = $this->eventRepository->getCollection()
            ->addFieldToFilter(EventInterface::TYPE, ProgramInterface::TYPE_PAY_PER_IMPRESSION)
            ->addFieldToFilter(EventInterface::IS_PROCESSED, false);
        $groupedCollection->getSelect()
            ->group([EventInterface::ACCOUNT_ID, EventInterface::VISITOR_IDENTIFIER])
            ->having('count>1000')
            ->columns([
                EventInterface::ACCOUNT_ID,
                EventInterface::VISITOR_IDENTIFIER,
                'count' => new \Zend_Db_Expr('COUNT(' . EventInterface::VISITOR_IDENTIFIER . ')'),
            ])
            ->limitPage(1, 1000);

        foreach ($groupedCollection as $item) {
            $collection = $this->eventRepository->getCollection()
                ->addFieldToFilter(EventInterface::TYPE, ProgramInterface::TYPE_PAY_PER_IMPRESSION)
                ->addFieldToFilter(EventInterface::IS_PROCESSED, false)
                ->addFieldToFilter(EventInterface::ACCOUNT_ID, $item->getData(EventInterface::ACCOUNT_ID))
                ->addFieldToFilter(EventInterface::VISITOR_IDENTIFIER, $item->getData(EventInterface::VISITOR_IDENTIFIER))
                ->setOrder(EventInterface::ID, Collection::SORT_ORDER_ASC)
                ->setPageSize(1000)
                ->setCurPage(1);

            $event = null;
            foreach ($collection as $event) {
                $event->setIsProcessed(true);
                $this->eventRepository->save($event);
            }
            if ($event) {
                $this->handleEvent($event);
            }
        }

        // events except IMPRESSION
        $collection = $this->eventRepository->getCollection()
            ->addFieldToFilter(EventInterface::TYPE, ['neq' => ProgramInterface::TYPE_PAY_PER_IMPRESSION])
            ->addFieldToFilter(EventInterface::IS_PROCESSED, false)
            ->setOrder(EventInterface::ID, Collection::SORT_ORDER_ASC);
        foreach ($collection as $event) {
            $this->handleEvent($event);
        }
    }

    private function handleEvent(EventInterface $event): void
    {
        $account = $this->accountRepository->get($event->getAccountId());
        if (!$account || $account->getStatus() != AccountInterface::STATUS_APPROVED) {
            $event->setIsProcessed(true);
            $event->setCommission(0);
            $this->eventRepository->save($event);

            return;
        }

        $handler = $this->eventHandlerFactory->create(['account' => $account, 'event' => $event]);
        if (!$handler) {
            $event->setIsProcessed(true);
            $event->setCommission(0);
            $this->eventRepository->save($event);

            return;
        }

        $handler->processing();
    }
}
