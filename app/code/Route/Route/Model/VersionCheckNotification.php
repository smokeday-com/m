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
        # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
        # "Eliminate 5.18s admin notification overhead in Mirasvit_Core, Route_Route, and Klevu_Search MessageList checks and remove render-blocking Google Fonts on Dashboard":
        # https://github.com/smokeday-com/m/issues/16
        $this->latestVersion = null;
    }

    /**
     * Lazily resolve latest Route module version outside constructor.
     *
     * @return string
     */
    private function resolveLatestVersion(): string
    {
        if ($this->latestVersion === null) {
            $this->latestVersion = (string)$this->routeVersion->getLatestVersion();
        }
        return (string)$this->latestVersion;
    }

    public function getIdentity()
    {
        return 'module_version_check';
    }

    public function isDisplayed()
    {
        $latestVersion = $this->resolveLatestVersion();
        if ($latestVersion !== '' && version_compare((string)$this->helper->getVersion(), $latestVersion) < 0) {
            return true;
        }
        return false;
    }

    public function getText()
    {
        return '<span>Route Module requires an upgrade. Latest version available: ' . $this->resolveLatestVersion() .
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
