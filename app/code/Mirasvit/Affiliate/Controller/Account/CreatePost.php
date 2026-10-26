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

use Exception;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Controller\AbstractAccount;
use Mirasvit\Affiliate\Repository\AccountRepository;
use Mirasvit\Affiliate\Service\AccountService;
use Mirasvit\Core\Service\SerializeService;

class CreatePost extends AbstractAccount
{
    private $accountRepository;

    private $accountService;

    public function __construct(
        AccountRepository $accountRepository,
        AccountService    $accountService,
        Context           $context
    ) {
        $this->accountRepository = $accountRepository;
        $this->accountService    = $accountService;

        parent::__construct($context);
    }

    public function execute()
    {
        $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $account = $this->accountRepository->create()
            ->setIsSubscribed((bool)$this->getRequest()->getParam('is_subscribed'));

        $additionalData = $this->getRequest()->getParam(AccountInterface::ADDITIONAL_DATA_SERIALIZED);

        if (is_array($additionalData)) {
            $account->setAdditionalDataSerialized(SerializeService::encode($additionalData));
        }

        try {
            $this->accountService->createAccount($account);

            if ($account->getStatus() == AccountInterface::STATUS_PENDING) {
                $this->messageManager->addSuccessMessage((string)__("We'll review your registration and notify you."));
            } elseif ($account->getStatus() == AccountInterface::STATUS_APPROVED) {
                $this->messageManager->addSuccessMessage((string)__('You have successfully signed up for an affiliate account.'));
            }

            return $this->resultRedirectFactory->create()->setPath('*/*');
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());

            return $this->resultRedirectFactory->create()->setPath('*/*/create');
        }
    }
}
