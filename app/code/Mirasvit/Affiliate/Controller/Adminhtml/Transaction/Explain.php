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

namespace Mirasvit\Affiliate\Controller\Adminhtml\Transaction;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\View\Result\LayoutFactory as ResultLayoutFactory;
use Mirasvit\Affiliate\Controller\Adminhtml\Transaction;
use Mirasvit\Affiliate\Repository\TransactionRepository;

class Explain extends Transaction
{
    private $resultLayoutFactory;

    public function __construct(
        ResultLayoutFactory   $resultLayoutFactory,
        TransactionRepository $transactionRepository,
        Context               $context
    ) {
        $this->resultLayoutFactory = $resultLayoutFactory;
        parent::__construct($transactionRepository, $context);
    }

    public function execute(): ResultInterface
    {
        $transaction = $this->initModel();
        if (!$transaction->getId()) {
            throw new NotFoundException(__('Transaction not found'));
        }

        return $this->resultLayoutFactory->create();
    }
}
