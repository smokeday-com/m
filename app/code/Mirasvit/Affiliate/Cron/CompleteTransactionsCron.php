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

use Mirasvit\Affiliate\Api\Data\TransactionInterface;
use Mirasvit\Affiliate\Repository\TransactionRepository;
use Mirasvit\Affiliate\Service\TransactionService;

class CompleteTransactionsCron
{
    private $transactionRepository;

    private $transactionService;

    public function __construct(
        TransactionRepository $transactionRepository,
        TransactionService $transactionService
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->transactionService    = $transactionService;
    }

    public function execute(): void
    {
        $collection = $this->transactionRepository->getCollection();
        $collection->addFieldToFilter(TransactionInterface::STATUS, TransactionInterface::STATUS_IN_HOLD);

        foreach ($collection as $transaction) {
            $this->transactionService->completeTransaction($transaction);
        }
    }
}
