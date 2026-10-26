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

namespace Mirasvit\Affiliate\Block\Adminhtml\Customer\Edit\Tab;

use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Form;
use Magento\Backend\Model\Url;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Customer\Model\Customer;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Repository\AccountRepository;

class Affiliate extends Form
{
    private $date;

    private $formFactory;

    private $urlManager;

    private $registry;

    private $context;

    private $customerRepository;

    private $currency;

    private $accountRepository;

    public function __construct(
        AccountRepository $accountRepository,
        DateTime $date,
        FormFactory $formFactory,
        Url $backendUrlManager,
        Registry $registry,
        Context $context,
        Customer $customerRepository,
        CurrencyInterface $currency,
        array $data = []
    ) {
        $this->accountRepository  = $accountRepository;
        $this->date               = $date;
        $this->formFactory        = $formFactory;
        $this->urlManager         = $backendUrlManager;
        $this->registry           = $registry;
        $this->context            = $context;
        $this->customerRepository = $customerRepository;
        $this->currency           = $currency;

        parent::__construct($context, $data);
    }

    public function getTabLabel(): string
    {
        return (string)$this->getTitle();
    }

    public function getTabTitle(): string
    {
        return (string)$this->getTitle();
    }

    public function canShowTab(): bool
    {
        return true;
    }

    public function isHidden(): bool
    {
        if ($this->getCustomer()) {
            return false;
        }

        return true;
    }

    public function getAfter(): string
    {
        return 'tags';
    }

    public function getCustomer(): ?Customer
    {
        if ($customerId = $this->registry->registry(RegistryConstants::CURRENT_CUSTOMER_ID)) {
            $customerData = $this->customerRepository->load($customerId);

            return $customerData;
        }

        return null;
    }

    public function getAffiliate(): ?AccountInterface
    {
        $customer = $this->getCustomer();

        if (!$customer) {
            return null;
        }

        return $this->accountRepository->getByCustomerId((int)$customer->getId());
    }

    public function getCreateUrl(): ?string
    {
        if (!$this->getCustomer()) {
            return null;
        }

        return (string)$this->urlManager->getUrl('affiliate/account/new', ['customer_id' => $this->getCustomer()->getId()]);
    }

    public function getBalance(): string
    {
        $affiliate = $this->getAffiliate();

        $balance = $affiliate ? $affiliate->getBalance() : 0;

        return $this->currency->getCurrency($this->context->getStoreManager()->getStore()->getBaseCurrencyCode())
            ->toCurrency($balance);
    }

    public function getHoldedComissions(): string
    {
        $affiliate = $this->getAffiliate();

        $holded = $affiliate ? $affiliate->getTotalHoldedCommissions() : 0;

        return $this->currency->getCurrency($this->context->getStoreManager()->getStore()->getBaseCurrencyCode())
            ->toCurrency($holded);
    }

    /**
     * Tab should be loaded trough Ajax call.
     */
    public function isAjaxLoaded(): bool
    {
        return false;
    }

    protected function _construct(): void
    {
        parent::_construct();

        $this->setTitle(__('Affiliate'));

        $this->setTemplate('customer/edit/tab/affiliate.phtml');
    }
}
