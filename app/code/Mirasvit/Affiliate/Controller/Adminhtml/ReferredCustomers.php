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
use Mirasvit\Affiliate\Api\Data\AccountCustomerInterface;
use Mirasvit\Affiliate\Repository\AccountCustomerRepository;

abstract class ReferredCustomers extends Action
{
    private $accountCustomerRepository;

    private $context;

    public function __construct(
        AccountCustomerRepository $accountCustomerRepository,
        Context                   $context
    ) {
        $this->accountCustomerRepository = $accountCustomerRepository;
        $this->context                   = $context;

        parent::__construct($context);
    }

    protected function initPage(Page $resultPage): Page
    {
        $resultPage->setActiveMenu('Magento_Backend::marketing');
        $resultPage->getConfig()->getTitle()->prepend((string)__('Affiliates'));
        $resultPage->getConfig()->getTitle()->prepend((string)__('Referred Customers'));

        return $resultPage;
    }

    public function initModel(): AccountCustomerInterface
    {
        $model = $this->accountCustomerRepository->create();

        if ($this->getRequest()->getParam(AccountCustomerInterface::ID)) {
            $model = $this->accountCustomerRepository->get((int)$this->getRequest()->getParam(AccountCustomerInterface::ID));
        }

        return $model;
    }

    protected function _isAllowed(): bool
    {
        return $this->context->getAuthorization()->isAllowed('Mirasvit_Affiliate::affiliate_referred_customers');
    }
}
