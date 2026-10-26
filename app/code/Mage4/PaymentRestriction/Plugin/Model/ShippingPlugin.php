<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprestriction
 */


namespace Mage4\PaymentRestriction\Plugin\Model;

use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Shipping\Model\Shipping;
use function PHPUnit\Framework\exactly;

class ShippingPlugin
{
    /**
     * @var Session
     */
    protected Session $customerSession;

    /**
     * @var ErrorFactory
     */
    protected $rateErrorFactory;

    /**
     * @var RateRequest|null
     */
    protected $request = null;

    public function __construct(
        ErrorFactory $rateErrorFactory,
        Session      $session
    )
    {
        $this->rateErrorFactory = $rateErrorFactory;
        $this->customerSession = $session;
    }

    public function beforeCollectRates(
        Shipping    $subject,
        RateRequest $request
    )
    {
        $this->request = $request;
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function afterCollectRates(Shipping $subject, $result)
    {
        $result = $subject->getResult();

        if (!($rates = $result->getAllRates())) {
            return $subject;
        }

        $result->reset();
        $LoggedIn = $this->customerSession->isLoggedIn();
        $CustomerGroup = $this->customerSession->getCustomerGroupId();
        /** @var Method $rate */
        foreach ($rates as $rate) {
            if (!$LoggedIn || $CustomerGroup != 2) {
                if ($rate['method'] != 'customshipping') {
                    $result->append($rate);
                }
            } elseif($LoggedIn && $CustomerGroup ==2) {
                if ($rate['method'] == 'customshipping') {
                    $result->append($rate);
                }
            }
        }
        return $subject;
    }
}
