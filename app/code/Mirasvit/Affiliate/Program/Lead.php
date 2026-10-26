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

namespace Mirasvit\Affiliate\Program;

use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Api\Data\EventInterface;
use Mirasvit\Affiliate\Api\Data\ProgramInterface;
use Mirasvit\Affiliate\Service\EventService;
use Mirasvit\Affiliate\Service\TierService;

class Lead implements CalculationInterface
{
    private $account;

    private $event;

    private $eventService;

    private $tierService;

    private $resultFactory;

    public function __construct(
        EventService             $eventService,
        TierService              $tierService,
        CalculationResultFactory $resultFactory,
        AccountInterface         $account,
        EventInterface           $event
    ) {
        $this->eventService  = $eventService;
        $this->tierService   = $tierService;
        $this->resultFactory = $resultFactory;
        $this->account       = $account;
        $this->event         = $event;
    }

    public function execute(ProgramInterface $program): CalculationResult
    {
        $result = $this->resultFactory->create();
        if (!$this->eventService->isUniqueEvent($this->event)) {
            return $result->setAmount(0.0);
        }

        $amount = $this->tierService->getCommissionAmountForGroup($this->account, $program);

        return $result->setAmount($amount);
    }

    public function getTransactionMessage(): string
    {
        return ucfirst($this->event->getType());
    }

    public function isSkip(): bool
    {
        return false;
    }
}
