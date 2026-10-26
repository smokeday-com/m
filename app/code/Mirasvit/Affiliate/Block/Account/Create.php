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

namespace Mirasvit\Affiliate\Block\Account;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Mirasvit\Affiliate\Model\ConfigProvider;

class Create extends Template
{
    private $customerSession;

    private $configProvider;

    public function __construct(
        CustomerSession $customerSession,
        ConfigProvider  $configProvider,
        Context         $context
    ) {
        $this->customerSession = $customerSession;
        $this->configProvider  = $configProvider;

        parent::__construct($context);
    }

    public function getCustomer(): ?Customer
    {
        $customer = $this->customerSession->getCustomer();

        return $customer->getId() ? $customer : null;
    }

    public function getFormData(): DataObject
    {
        return new DataObject((array)$this->customerSession->getData('affiliate_create_form'));
    }

    public function getAgreementText(): string
    {
        return $this->configProvider->getAgreementText();
    }
}
