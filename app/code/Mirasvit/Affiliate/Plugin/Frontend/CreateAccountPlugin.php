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

namespace Mirasvit\Affiliate\Plugin\Frontend;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Repository\AccountRepository;
use Mirasvit\Affiliate\Service\AccountService;
use Mirasvit\Affiliate\Service\EventService;
use Mirasvit\Core\Service\SerializeService;

class CreateAccountPlugin
{
    private $request;

    private $accountService;

    private $accountRepository;

    private $customerSession;

    private $messageManager;

    private $eventService;

    public function __construct(
        RequestInterface  $request,
        AccountService    $accountService,
        AccountRepository $accountRepository,
        CustomerSession   $customerSession,
        MessageManager    $messageManager,
        EventService      $eventService
    ) {
        $this->request           = $request;
        $this->accountService    = $accountService;
        $this->accountRepository = $accountRepository;
        $this->customerSession   = $customerSession;
        $this->messageManager    = $messageManager;
        $this->eventService      = $eventService;
    }

    /**
     * @param mixed $subject
     * @param mixed $result
     *
     * @return mixed
     */
    public function afterExecute($subject, $result)
    {
        if ($customerId = $this->customerSession->getCustomerId()) {
            $this->eventService->registerLead((int)$customerId);
        }

        if ($this->request->getParam('affiliate')) {
            if (!$customerId) {
                return $result;
            }

            if (!$this->accountRepository->getByCustomerId((int)$customerId)) {
                $account = $this->accountRepository->create()
                    ->setIsSubscribed((bool)$this->request->getParam('is_subscribed'));

                $additionalData = $this->request->getParam(AccountInterface::ADDITIONAL_DATA_SERIALIZED);

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
                } catch (\Exception $e) {
                    $this->messageManager->addErrorMessage($e->getMessage());
                }
            }
        }

        return $result;
    }
}
