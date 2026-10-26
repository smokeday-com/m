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

namespace Mirasvit\Affiliate\Model;

use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Api\Data\EventInterface;
use Mirasvit\Affiliate\Api\Data\ProgramInterface;
use Mirasvit\Affiliate\Model\Explain\TransactionExplainFactory;
use Mirasvit\Affiliate\Program\CalculationFactory;
use Mirasvit\Affiliate\Repository\EventRepository;
use Mirasvit\Affiliate\Service\ProgramService;
use Mirasvit\Affiliate\Service\TransactionService;

class EventHandler
{
    private $eventRepository;

    private $programService;

    private $transactionService;

    private $calculationFactory;

    private $explainFactory;

    private $account;

    private $event;

    public function __construct(
        EventRepository           $eventRepository,
        ProgramService            $programService,
        TransactionService        $transactionService,
        CalculationFactory        $calculationFactory,
        TransactionExplainFactory $explainFactory,
        AccountInterface          $account,
        EventInterface            $event
    ) {
        $this->eventRepository    = $eventRepository;
        $this->programService     = $programService;
        $this->transactionService = $transactionService;
        $this->calculationFactory = $calculationFactory;
        $this->explainFactory     = $explainFactory;
        $this->account            = $account;
        $this->event              = $event;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     *
     * @return void
     */
    public function processing(): void
    {
        $calculation = $this->calculationFactory->create($this->account, $this->event);
        if (!$calculation) {
            $this->saveZeroCommissionToEvent();

            return;
        }

        if ($this->transactionService->isExceededTransactionLimit($this->account, $this->event)) {
            $this->saveZeroCommissionToEvent();

            return;
        }

        $programs = $this->programService->getProgramCollection($this->event);
        if (!$programs->getSize()) {
            $this->saveZeroCommissionToEvent();

            return;
        }

        if ($calculation->isSkip()) {
            return;
        }

        $transactionExplain = $this->explainFactory->create();
        $transactionExplain->addMessage('EventId: ' . $this->event->getId() . ', AffiliateId: ' . $this->account->getId());
        $commissionAmount = 0.0;
        /** @var ProgramInterface $program */
        foreach ($programs as $program) {
            $transactionExplain->startProgram($program);
            if (!$this->programService->isApplicableForAccount($program, $this->account)) {
                $transactionExplain->addMessage('This program is not applicable');
                $transactionExplain->stopProgram();
                continue;
            }

            $calculationResult  = $calculation->execute($program);
            $programCommission  = $calculationResult->getAmount();
            $calculationExplain = $calculationResult->getExplain();
            if ($calculationExplain) {
                $transactionExplain->addCalculationExplain($calculationExplain);
                if ($calculationExplain->getFormulaStr()) {
                    $transactionExplain->addMessage($calculationExplain->getFormulaStr(), $calculationExplain->getFormulaResult(), false);
                }
            }
            $transactionExplain->addMessage('Program commission: ', $programCommission, false);

            if (abs($programCommission) < ConfigProvider::FLOAT_EPSILON) {
                $transactionExplain->stopProgram();
                continue;
            }

            $commissionAmount += $programCommission;
            if ($program->getIsStopProcessing()) {
                $transactionExplain->addMessage('Discard subsequent programs');
                $transactionExplain->stopProgram();
                break;
            }
            $transactionExplain->stopProgram();
        }
        $transactionExplain->stopProgramList();
        if (strpos($transactionExplain->getFormulaStr(), '+') !== false) {
            $transactionExplain->setFormulaResult($commissionAmount);
            $transactionExplain->addMessage($transactionExplain->getFormulaStr() . ' = ', $transactionExplain->getFormulaResult(), false);
        }
        $transactionExplain->addMessage('Total Commission: ', $commissionAmount, false);
        $transactionExplain->setTotal($commissionAmount);

        $this->event->setCommission($commissionAmount);
        $this->event->setIsProcessed(true);
        $this->eventRepository->save($this->event);

        $this->transactionService->addCommissionTransaction(
            $this->account,
            $this->event,
            $commissionAmount,
            (string)__($calculation->getTransactionMessage()),
            $transactionExplain
        );
    }

    private function saveZeroCommissionToEvent(): void
    {
        $this->event->setCommission(0.0);
        $this->event->setIsProcessed(true);
        $this->eventRepository->save($this->event);
    }
}
