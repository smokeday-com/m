<?php

namespace Mage4\PaymentRestriction\Plugin\Payment\Model;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Customer\Model\Session;

class MethodList
{

    /**
     * @var Session
     */
    protected Session $customerSession;

    /**
     * MethodList constructor.
     *
     * @param Session $customerSession
     */
    public function __construct(
        Session $customerSession
    ) {
        $this->customerSession = $customerSession;
    }

    /**
     * @param \Magento\Payment\Model\MethodList $subject
     * @param $availableMethods
     * @param CartInterface|null $quote
     */
    public function afterGetAvailableMethods(
        \Magento\Payment\Model\MethodList $subject,
        $availableMethods,
        CartInterface                     $quote = null
    ) {
        if ($this->customerSession->isLoggedIn()) {
            $customerGroupId = $this->customerSession->getCustomer()->getGroupId();
            if ($customerGroupId == 2) {
                $disallowedPaymentMethodCode = ['sezzlepay'];
                foreach ($availableMethods as $key => $method) {
                    if (in_array($method->getCode(), $disallowedPaymentMethodCode)) {
                        unset($availableMethods[$key]);
                    }
                }
            }
        }
        return $availableMethods;
    }
}
