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

namespace Mirasvit\Affiliate\Controller\Adminhtml\Withdraw;

use Magento\Framework\Controller\ResultInterface;
use Mirasvit\Affiliate\Api\Data\WithdrawInterface;
use Mirasvit\Affiliate\Controller\Adminhtml\Withdraw;

class Save extends Withdraw
{
    public const ACTION_DENY     = 'deny';
    public const ACTION_ACCEPT   = 'accept';
    public const ACTION_COMPLETE = 'complete';
    public const ACTION_CREDIT   = 'credit';

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute(): ResultInterface
    {
        $id             = $this->getRequest()->getParam(WithdrawInterface::ID);
        $action         = $this->getRequest()->getParam('action');
        $resultRedirect = $this->resultRedirectFactory->create();

        $data = $this->getRequest()->getPostValue();

        if ($data) {
            $model = $this->initModel();

            if ($id && !$model) {
                $this->messageManager->addErrorMessage((string)__('This withdrawal no longer exists.'));

                return $resultRedirect->setPath('*/*/');
            }

            $model->setAccountId((int)$data[WithdrawInterface::ACCOUNT_ID])
                ->setAmount((float)$data[WithdrawInterface::AMOUNT])
                ->setFee((float)$data[WithdrawInterface::FEE]);

            try {
                $this->withdrawRepository->save($model);

                $this->messageManager->addSuccessMessage((string)__('You have saved the withdrawal.'));

                switch ($action) {
                    case self::ACTION_DENY:
                        $this->withdrawService->denyWithdraw($model);
                        $this->messageManager->addSuccessMessage((string)__('Withdrawal was denied.'));
                        break;
                    case self::ACTION_ACCEPT:
                        $this->withdrawService->acceptWithdraw($model);
                        $this->messageManager->addSuccessMessage((string)__('Withdrawal was accepted.'));
                        break;
                    case self::ACTION_COMPLETE:
                        $this->withdrawService->completeWithdraw($model);
                        $this->messageManager->addSuccessMessage((string)__('Withdrawal was completed.'));
                        break;
                    case self::ACTION_CREDIT:
                        if ($model->getStatus() == WithdrawInterface::STATUS_PENDING) {
                            $this->withdrawService->sendToStoreCredit($model);
                            $this->messageManager->addSuccessMessage((string)__('Withdrawal was sent to Store Credit.'));
                        }
                        break;
                }

                if ($this->getRequest()->getParam('back') == 'edit') {
                    return $resultRedirect->setPath('*/*/edit', [WithdrawInterface::ID => $model->getId()]);
                }

                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());

                return $resultRedirect->setPath('*/*/edit', [WithdrawInterface::ID => $id]);
            }
        } else {
            $resultRedirect->setPath('*/*/');
            $this->messageManager->addErrorMessage((string)__('No data to save.'));

            return $resultRedirect;
        }
    }
}
