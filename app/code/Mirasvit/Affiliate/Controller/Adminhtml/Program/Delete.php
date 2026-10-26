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

namespace Mirasvit\Affiliate\Controller\Adminhtml\Program;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Mirasvit\Affiliate\Controller\Adminhtml\Program;
use Mirasvit\Affiliate\Repository\ProgramRepository;
use Mirasvit\Affiliate\Service\AccountService;

class Delete extends Program
{
    private $accountService;

    public function __construct(
        AccountService    $accountService,
        ProgramRepository $accountRepository,
        Registry          $registry,
        Context           $context
    ) {
        $this->accountService = $accountService;
        parent::__construct($accountRepository, $registry, $context);
    }

    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        $model = $this->initModel();

        if (!$model) {
            $this->messageManager->addErrorMessage((string)__('This program no longer exists.'));

            return $resultRedirect->setPath('*/*/');
        }

        try {
            $this->programRepository->delete($model);

            $this->messageManager->addSuccessMessage((string)__('Program has been removed.'));
            $this->accountService->deleteProgramFromAllAccounts($model);

            return $resultRedirect->setPath('*/*/');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());

            return $resultRedirect->setPath('*/*/');
        }
    }
}
