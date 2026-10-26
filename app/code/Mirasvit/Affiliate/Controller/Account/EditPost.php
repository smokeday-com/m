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
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Controller\AbstractAccount;
use Mirasvit\Affiliate\Repository\AccountRepository;
use Mirasvit\Affiliate\Service\SessionService;
use Mirasvit\Core\Service\SerializeService;

class EditPost extends AbstractAccount
{
    private $sessionService;

    private $accountRepository;

    public function __construct(
        SessionService    $sessionService,
        AccountRepository $accountRepository,
        Context           $context
    ) {
        $this->sessionService    = $sessionService;
        $this->accountRepository = $accountRepository;

        parent::__construct($context);
    }

    public function execute()
    {
        $account = $this->sessionService->getAccount();

        try {
            $account->setIsSubscribed((bool)$this->getRequest()->getParam(AccountInterface::IS_SUBSCRIBED))
                ->setPaymentMethod((string)$this->getRequest()->getParam(AccountInterface::PAYMENT_METHOD))
                ->setPaymentEmail((string)$this->getRequest()->getParam(AccountInterface::PAYMENT_EMAIL));

            $additionalData = $this->getRequest()->getParam(AccountInterface::ADDITIONAL_DATA_SERIALIZED);

            if (is_array($additionalData)) {
                $account->setAdditionalDataSerialized(SerializeService::encode($additionalData));
            }

            $this->accountRepository->save($account);

            $this->messageManager->addSuccessMessage((string)__('Account information successfully updated.'));
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        $this->_redirect('*/*/edit');
    }
}
