<?php
/**
 * A Route Magento Extension that adds secure shipping
 * insurance to your orders
 *
 * Php version 7.0^
 *
 * @category  Model
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */

namespace Route\Route\Model\Route;

use Exception;

/**
 * User Route API version
 *
 * Php version 7.0^
 *
 * @category  Model
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Version extends Client
{
    const API_ENDPOINT = 'https://route-cdn.s3.amazonaws.com/route-magento-extension/magento2-version.json';

    /**
     * Get module's latest version from Route API
     *
     * @return bool|mixed
     * @throws Exception
     */
    public function getLatestVersion()
    {
        $version = $this->helper->getLatestVersionCheckVersion();

        if ($this->shouldPerformApiCall()) {
            $version = $this->getMostRecentVersion();
            $this->helper->saveConfig($this->helper::LATEST_VERSION_CHECK_VERSION, $version);
            $currentDate = new \DateTime();
            $this->helper->saveConfig($this->helper::LATEST_VERSION_CHECK_DATE, $currentDate->format('Y-m-d'));
        }

        return $version;
    }

    private function getMostRecentVersion()
    {
        try {
            $versionJson = file_get_contents(self::API_ENDPOINT);
            if (!empty($versionJson)) {
                $decodedVersion = $this->helper->unserialize($versionJson);
                return $decodedVersion['latest_version'];
            }
        } catch (\Exception $exception) {
            return $this->helper->getLatestVersionCheckVersion();
        }
    }
    /**
     * Check if we should make API call to get latest version based on date
     *
     * @return bool
     * @throws Exception
     */
    private function shouldPerformApiCall()
    {
        $currentDate = new \DateTime();
        $latestRequest = $this->helper->getLatestVersionCheckDate() ?
            new \DateTime($this->helper->getLatestVersionCheckDate()) :
            false;
        if (!$latestRequest || ($currentDate->format('Y-m-d')!==$latestRequest->format('Y-m-d'))) {
            return true;
        }
        return false;
    }
}
