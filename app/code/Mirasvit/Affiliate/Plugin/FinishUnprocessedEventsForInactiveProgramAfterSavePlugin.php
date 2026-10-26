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

namespace Mirasvit\Affiliate\Plugin;

use Mirasvit\Affiliate\Api\Data\EventInterface;
use Mirasvit\Affiliate\Api\Data\ProgramInterface;
use Mirasvit\Affiliate\Repository\EventRepository;

/**
 * @see \Mirasvit\Affiliate\Repository\ProgramRepository::save()
 */
class FinishUnprocessedEventsForInactiveProgramAfterSavePlugin
{
    private $eventRepository;

    public function __construct(EventRepository $eventRepository)
    {
        $this->eventRepository = $eventRepository;
    }

    /**
     * @param mixed            $repository
     * @param ProgramInterface $program
     *
     * @return ProgramInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function afterSave($repository, ProgramInterface $program)
    {
        if (!$program->getIsVisible() && !$program->getIsActive()) {
            return $program;
        }

        $collection = $this->eventRepository->getCollection();
        $collection
            ->addStoreFilter()
            ->addFieldToFilter(EventInterface::TYPE, $program->getType())
            ->addFieldToFilter(EventInterface::IS_PROCESSED, false);

        foreach ($collection as $event) {
            $event->setIsProcessed(true);
            $this->eventRepository->save($event);
        }

        return $program;
    }
}
