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

namespace Mirasvit\Affiliate\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Mirasvit\Affiliate\Api\Data\TransactionInterface;
use Mirasvit\Affiliate\Repository\TransactionRepository;

abstract class Transaction extends Action
{
    protected $transactionRepository;

    private $context;

    public function __construct(
        TransactionRepository $transactionRepository,
        Context $context
    ) {
        $this->transactionRepository = $transactionRepository;

        $this->context = $context;

        parent::__construct($context);
    }

    protected function initPage(Page $resultPage): Page
    {
        $resultPage->setActiveMenu('Magento_Backend::marketing');
        $resultPage->getConfig()->getTitle()->prepend((string)__('Affiliates'));
        $resultPage->getConfig()->getTitle()->prepend((string)__('Transactions'));

        return $resultPage;
    }

    public function initModel(): TransactionInterface
    {
        $model = $this->transactionRepository->create();

        if ($this->getRequest()->getParam(TransactionInterface::ID)) {
            $model = $this->transactionRepository->get((int)$this->getRequest()->getParam(TransactionInterface::ID));
        }

        return $model;
    }

    protected function _isAllowed(): bool
    {
        return $this->context->getAuthorization()->isAllowed('Mirasvit_Affiliate::affiliate_transaction');
    }
}
