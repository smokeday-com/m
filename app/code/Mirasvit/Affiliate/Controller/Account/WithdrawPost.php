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
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\Affiliate\Controller\AbstractAccount;
use Mirasvit\Affiliate\Service\SessionService;
use Mirasvit\Affiliate\Service\WithdrawService;

class WithdrawPost extends AbstractAccount
{
    private $storeManager;

    private $sessionService;

    private $withdrawService;

    public function __construct(
        StoreManagerInterface $storeManager,
        SessionService        $sessionService,
        WithdrawService       $withdrawService,
        Context               $context
    ) {
        $this->storeManager    = $storeManager;
        $this->sessionService  = $sessionService;
        $this->withdrawService = $withdrawService;

        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $account = $this->sessionService->getAccount();
        $amount  = (float)$this->getRequest()->getParam('amount');

        try {
            $currentCurrencyCode = (string)$this->storeManager->getStore()->getCurrentCurrencyCode();
            $this->withdrawService->createRequest($account, $amount, $currentCurrencyCode);

            $this->messageManager->addSuccessMessage((string)__('Withdrawal request has been submitted.'));

            return $this->resultRedirectFactory->create()->setPath('*/*/withdraw');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());

            return $this->resultRedirectFactory->create()->setPath('*/*/withdraw');
        }
    }
}
