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
    # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
    # "Eliminate 5.18s admin notification overhead in Mirasvit_Core, Route_Route, and Klevu_Search MessageList checks and remove render-blocking Google Fonts on Dashboard":
    # https://github.com/smokeday-com/m/issues/16
    private const CACHE_KEY = 'route_route_latest_version_check';
    private const CACHE_TTL = 86400;

    /**
     * Get module's latest version from Route API
     *
     * @return bool|mixed
     * @throws Exception
     */
    public function getLatestVersion()
    {
        $cache = \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\App\CacheInterface::class);
        $cachedVersion = $cache->load(self::CACHE_KEY);
        if ($cachedVersion !== false && $cachedVersion !== '') {
            return $cachedVersion;
        }

        $version = $this->helper->getLatestVersionCheckVersion();

        if ($this->shouldPerformApiCall()) {
            $version = $this->getMostRecentVersion();
            $this->helper->saveConfig($this->helper::LATEST_VERSION_CHECK_VERSION, $version);
            $currentDate = new \DateTime();
            $this->helper->saveConfig($this->helper::LATEST_VERSION_CHECK_DATE, $currentDate->format('Y-m-d'));
        }

        if ($version) {
            $cache->save((string)$version, self::CACHE_KEY, [], self::CACHE_TTL);
        }

        return $version;
    }

    private function getMostRecentVersion()
    {
        try {
            $ctx = stream_context_create(['http' => ['timeout' => 2], 'ssl' => ['handshake_timeout' => 2]]);
            $versionJson = @file_get_contents(self::API_ENDPOINT, false, $ctx);
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
