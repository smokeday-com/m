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

use Magento\Framework\Controller\ResultInterface;
use Mirasvit\Affiliate\Api\Data\TransactionInterface;
use Mirasvit\Affiliate\Controller\Adminhtml\Transaction;

class Save extends Transaction
{
    public function execute(): ResultInterface
    {
        $id = $this->getRequest()->getParam(TransactionInterface::ID);
        $resultRedirect = $this->resultRedirectFactory->create();

        $data = $this->getRequest()->getPostValue();

        if ($data) {
            $model = $this->initModel();

            if ($id && !$model) {
                $this->messageManager->addErrorMessage((string)__('This transaction no longer exists.'));

                return $resultRedirect->setPath('*/*/');
            }

            $storeId = isset($data[TransactionInterface::STORE_ID])
                ? (int)$data[TransactionInterface::STORE_ID]
                : 0;

            $model->setAccountId((int)$data[TransactionInterface::ACCOUNT_ID])
                ->setAmount((float)$data[TransactionInterface::AMOUNT])
                ->setMessage((string)$data[TransactionInterface::MESSAGE])
                ->setType((string)TransactionInterface::TYPE_COMMISSION)
                ->setStoreId($storeId);

            try {
                $this->transactionRepository->save($model);

                $this->messageManager->addSuccessMessage((string)__('You have saved the transaction.'));

                if ($this->getRequest()->getParam('back') == 'edit') {
                    return $resultRedirect->setPath('*/*/edit', [TransactionInterface::ID => $model->getId()]);
                }

                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());

                return $resultRedirect->setPath('*/*/edit', [TransactionInterface::ID => $id]);
            }
        } else {
            $resultRedirect->setPath('*/*/');
            $this->messageManager->addErrorMessage((string)__('No data to save.'));

            return $resultRedirect;
        }
    }
}
