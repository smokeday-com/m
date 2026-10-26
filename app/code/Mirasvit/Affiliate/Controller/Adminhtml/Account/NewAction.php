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

namespace Mirasvit\Affiliate\Controller\Adminhtml\Account;

use Magento\Framework\Controller\ResultInterface;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Controller\Adminhtml\Account;

class NewAction extends Account
{
    public function execute(): ResultInterface
    {
        $customerId = (int)$this->getRequest()->getParam('customer_id');
        if (!$customerId) {
            return $this->resultRedirectFactory->create()->setPath('*/*');
        }

        $account = $this->accountRepository->create();
        $account->setCustomerId($customerId)
            ->setStatus(AccountInterface::STATUS_APPROVED);

        $this->accountRepository->save($account);

        return $this->resultRedirectFactory->create()->setPath('*/*/edit', [AccountInterface::ID => $account->getId()]);
    }
}
