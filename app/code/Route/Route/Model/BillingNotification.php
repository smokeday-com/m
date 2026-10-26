<?php
/**
 * A Route Magento Extension that adds secure shipping
 * insurance to your orders
 *
 * Php version 7.0^
 *
 * @category
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */

namespace Route\Route\Model;

use Magento\Framework\Notification\MessageInterface;
use Route\Route\Model\Route\Billing;
use Route\Route\Helper\Data;

/**
 *
 * Php version 7.0^
 *
 * @category
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class BillingNotification implements MessageInterface
{
    const BILLING_DASHBOARD_URL = "https://dashboard.routeapp.io/";

    protected $billingClient;
    protected $helper;

    public function __construct(
        Billing $billingClient,
        Data $helper
    ) {
        $this->billingClient = $billingClient;
        $this->helper = $helper;
    }

    public function getIdentity()
    {
        return 'empty_billing_information';
    }

    public function isDisplayed()
    {

        if ($this->helper->isBillingChecked() && $this->helper->isBillingFilled()) {
            return false;
        }

        try {
            $isBillingEmpty = empty($this->billingClient->getBilling());
            $this->helper->setBillingFilled(!$isBillingEmpty);
            return $isBillingEmpty;
        } catch (\Exception $e) {
            return true;
        }
    }

    public function getText()
    {
        $messageDetails = '<span>Billing Information Required.  Please click ' .
            '<a href="' . self::BILLING_DASHBOARD_URL . '" target="_blank"> here </a>' .
            'to update your billing information.</span>';
        return $messageDetails;
    }

    public function getSeverity()
    {
        return self::SEVERITY_CRITICAL;
    }
}
