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
use Magento\Framework\UrlInterface;
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
class WrongSettingsNotification implements MessageInterface
{
    const BILLING_DASHBOARD_URL = "https://dashboard.routeapp.io/";

    protected $helper;
    protected $urlBuilder;

    public function __construct(
        Data $helper,
        UrlInterface $urlBuilder
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->helper = $helper;
    }

    public function getIdentity()
    {
        return 'wrong_settings_notification';
    }

    public function isDisplayed()
    {
        return $this->helper->isInvalidMerchant();
    }

    public function getText()
    {
        $messageDetails = '<span>Route is installed but you have no tokens or wrong tokens set up. Please click ' .
            '<a href="' . $this->urlBuilder->getUrl('admin/system_config/edit/section/route') . '" target="_blank"> here </a>' .
            'to add your tokens.</span>';
        return $messageDetails;
    }

    public function getSeverity()
    {
        return self::SEVERITY_CRITICAL;
    }
}
