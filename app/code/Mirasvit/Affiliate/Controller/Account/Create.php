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

namespace Mirasvit\Affiliate\Controller\Account;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Controller\AbstractAccount;
use Mirasvit\Affiliate\Service\SessionService;

class Create extends AbstractAccount
{
    private $sessionService;

    public function __construct(
        SessionService $sessionService,
        Context $context
    ) {
        $this->sessionService = $sessionService;

        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        if (!$this->sessionService->getCustomer()) {
            return $resultRedirect->setPath('customer/account/login');
        }

        $account = $this->sessionService->getAccount();

        if ($account) {
            if ($account->getStatus() == AccountInterface::STATUS_APPROVED) {
                return $resultRedirect->setPath('*/*/dashboard');
            } elseif ($account->getStatus() == AccountInterface::STATUS_PENDING) {
                $this->messageManager->addNoticeMessage((string)__("We'll review your registration and notify you."));

                return $resultRedirect->setPath('*/*/welcome');
            } elseif ($account->getStatus() == AccountInterface::STATUS_NOT_APPROVED) {
                $this->messageManager->addNoticeMessage((string)__("Your request to sign up to the affiliate program has been rejected."));

                return $resultRedirect->setPath('*/*/welcome');
            }
        }

        if (!$this->sessionService->getCustomer()) {
            $resultPage->addHandle('customer_account_create');
        }

        if ($head = $resultPage->getLayout()->getBlock('head')) {
            $head->setTitle((string)__('Sign In to Affiliates'));
        }

        $resultPage->getConfig()->getTitle()->set((string)__('Sign In to Affiliates'));

        return $resultPage;
    }
}
