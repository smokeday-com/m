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
namespace Route\Route\Block\Adminhtml;

use Route\Route\Helper\Setup;
use \Magento\Backend\Block\Template\Context;

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
class Notification extends \Magento\Backend\Block\Template
{
    const PORTAL_URL  = "https://dashboard.route.com/login?redirect=onboarding";
    const PORTAL_URL_STAGING = "https://dashboard-stage.route.com/login?redirect=onboarding";

    private $setupHelper;

    /**
     * Notification constructor.
     *
     * @param Context $context
     * @param Setup $setupHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Setup $setupHelper,
        array $data = []
    ) {
        $this->setupHelper = $setupHelper;
        parent::__construct($context, $data);
    }

    /**
     * Get portal URL
     *
     * @return string
     */
    public function getRoutePortalUrl()
    {
        if ($this->hasUserLoginSucceed()) {
            $portalUrl = self::PORTAL_URL;
            $custom_env = getenv('ROUTEAPP_ENVIRONMENT_ENDPOINT');
            if (is_null($custom_env) || !$custom_env) {
                $custom_env = isset($_SERVER['ROUTEAPP_ENVIRONMENT_ENDPOINT']) ? $_SERVER['ROUTEAPP_ENVIRONMENT_ENDPOINT'] : '';
            }
            if ($custom_env=='stage') {
                $portalUrl = self::PORTAL_URL_STAGING;
            }

            return $portalUrl;
        }
        return $this->setupHelper->getActivationLink();
    }

    protected function _toHtml()
    {
        if ($this->canShowRedirectAlert()) {
            return parent::_toHtml();
        }
    }


    /**
     * If it's fresh new Installation it'll trigger
     * User and Merchant creation account
     *
     * @return bool
     */
    public function canShowRedirectAlert()
    {
        return ($this->hasToBeRedirected())
            && $this->setupHelper->setRedirected() && $this->setupHelper->isRouteModuleEnable();
    }

    /**
     * @return bool
     */
    public function hasToBeRedirected()
    {
        return $this->setupHelper->isInstalled()
            && !$this->setupHelper->wasRedirected();
    }

    /**
     * Get message according to installation step and status
     *
     * @return bool
     */
    public function getMessage()
    {
        if ($this->hasUserCreationConflicted()) {
            return "Your user account already exists. We're redirecting you to Route website to finalize your account setup... ";
        }
        return "You're almost there! We're redirecting you to Route website to finalize your account setup... ";
    }

    /**
     * Check if merchant creation has failed by conflict reason
     *
     * @return bool
     */
    public function hasMerchantCreationConflicted()
    {
        return $this->setupHelper->hasMerchantCreationConflicted();
    }

    /**
     * Check if user creation has failed by conflict reason
     *
     * @return bool
     */
    public function hasUserCreationConflicted()
    {
        return $this->setupHelper->hasUserCreationConflicted();
    }

    /**
     * Check if user creation has failed by conflict reason
     *
     * @return bool
     */
    public function hasUserLoginSucceed()
    {
        return $this->setupHelper->hasUserLoginSucceed();
    }
}
