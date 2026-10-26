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

use Exception;
use Magento\Framework\Notification\MessageInterface;
use Route\Route\Helper\Data;
use Route\Route\Model\Route\Version;

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
class VersionCheckNotification implements MessageInterface
{
    const CHANGELOG_URI = 'https://marketplace.magento.com/route-route-m2.html#product.info.details.release_notes';

    protected $routeVersion;
    protected $helper;
    protected $latestVersion;

    /**
     * VersionCheckNotification constructor.
     * @param Version $routeVersion
     * @param Data $helper
     * @throws Exception
     */
    public function __construct(
        Version $routeVersion,
        Data $helper
    ) {
        $this->routeVersion = $routeVersion;
        $this->helper = $helper;
        $this->latestVersion = $this->routeVersion->getLatestVersion();
    }

    public function getIdentity()
    {
        return 'module_version_check';
    }

    public function isDisplayed()
    {
        if (version_compare($this->helper->getVersion(), $this->latestVersion) < 0) {
            return true;
        }
        return false;
    }

    public function getText()
    {
        return '<span>Route Module requires an upgrade. Latest version available: ' . $this->latestVersion .
            ' . Please see the <a href="' . $this->getChangeLogLink() . '" target="_blank"> release notes </a></span>';
    }

    /**
     * @return string
     */
    protected function getChangeLogLink()
    {
        return self::CHANGELOG_URI;
    }

    public function getSeverity()
    {
        return self::SEVERITY_CRITICAL;
    }
}
