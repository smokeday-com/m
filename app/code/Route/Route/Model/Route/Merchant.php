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

/**
 * Merchant Route API client
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
class Merchant extends Client
{
    const API_ENDPOINT = 'merchants';
    const ROUTE_MERCHANT_DATA = 'route_merchant_data';
    # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
    # "Fix broken Route_Route QuoteApi caching and FulfilledPromise bug, increase Merchant cache TTL to 86400s, use Magento config cache in Setup::getSetupCheckWidget, and eliminate duplicate checkWidget calls and full-screen loader blocking":
    # https://github.com/smokeday-com/m/issues/23
    const CACHE_LIFETIME = 86400;
    const ERROR_CACHE_LIFETIME = 10;

    const INVALID = "invalid";
    const MERCHANT_UNINSTALLED_STATUS = "Uninstalled";
    const MERCHANT_ACTIVE_STATUS = "Active";

    /**
     * Create new merchant Account
     *
     * @param array $merchantData
     * @param string $userToken
     *
     * @return mixed
     */
    public function createMerchant($merchantData, $userToken)
    {
        try {
            $response = $this
                ->setUnauthorizedRequest()
                ->addHeader('token', 'token: ' . $userToken)
                ->postMethod(
                    $this->getRouteApiUrl(self::API_ENDPOINT),
                    $merchantData
                )->execute([parent::CREATED_HTTP_CODE, parent::SUCCESS_HTTP_CODE]);

            return $response;
        } catch (\Exception $exception) {
            $this->logger->error(
                ($this->hasConflicted() ? "Merchant already exists." : "Merchant account creation has failed") . $exception->getMessage(),
                $exception->getTrace(),
                [
                    'params' => $this->getData(),
                    'method' => $this->getCurrentMethod(),
                    'endpoint' => $this->getUrl(),
                ]
            );
            return false;
        }
    }

    /**
     * Update merchant Account
     *
     * @param array $merchantData
     * @param string $merchantId
     *
     * @return mixed
     */
    public function updateMerchant($merchantData, $merchantId)
    {
        try {
            $response = $this
                ->postMethod(
                    $this->getRouteApiUrl(self::API_ENDPOINT . '/' . $merchantId),
                    array_merge($merchantData, ['create_tokens' => true])
                )->execute([parent::CREATED_HTTP_CODE, parent::SUCCESS_HTTP_CODE]);

            return $response;
        } catch (\Exception $exception) {

            $this->logger->error(
                "Merchant update has failed reason:" . $exception->getMessage(),
                $exception->getTrace(),
                [
                    'params' => $this->getData(),
                    'method' => $this->getCurrentMethod(),
                    'endpoint' => $this->getUrl(),
                ]
            );

            return false;
        }
    }

    /**
     * Get merchant data
     *
     * @param $escapeCache bool escape response cache
     *
     * @return mixed
     */
    public function getMerchant($escapeCache = false)
    {
        $cachedMerchant = $this->cache->load(self::ROUTE_MERCHANT_DATA);

        /** avoid repetitive invalid requests */
        if (!empty($cachedMerchant) && $cachedMerchant == self::INVALID) {
            return false;
        }

        if (!$escapeCache && !empty($cachedMerchant)) {
            return $this->helper->unserialize($cachedMerchant);
        }

        try {
            $response = $this
                ->getMethod(
                    $this->getRouteApiUrl(self::API_ENDPOINT)
                )->execute(self::SUCCESS_HTTP_CODE);

            $merchantFound = $this->getMerchantByDomain($response);

            //check for merchant status, mark as active if it's Uninstalled
            if (isset($merchantFound['status']) && $merchantFound['status'] == self::MERCHANT_UNINSTALLED_STATUS) {
                $this->updateMerchantStatus(self::MERCHANT_ACTIVE_STATUS, $merchantFound);
                $merchantFound['status'] = self::MERCHANT_ACTIVE_STATUS;
            }

            $this->cache->save($this->helper->serialize($merchantFound), self::ROUTE_MERCHANT_DATA, [], self::CACHE_LIFETIME);

            return $merchantFound;
        } catch (\Exception $exception) {

            $this->cache->save(self::INVALID, self::ROUTE_MERCHANT_DATA, [], self::ERROR_CACHE_LIFETIME);

            if ($this->isForbidden()) {
                $this->helper->setMerchantAsInvalid();
            }

            $this->logger->error(
                "Error getting merchant information: " . $exception->getMessage(),
                $exception->getTrace(),
                [
                    'params' => $this->getData(),
                    'method' => $this->getCurrentMethod(),
                    'endpoint' => $this->getUrl(),
                ]
            );

            return false;
        }
    }

    /**
     * Check if merchant is Route Plus
     *
     * @return bool
     */
    public function isRoutePlus()
    {
        $response = $this->getMerchant();
        return isset($response['has_route_plus']) && $response['has_route_plus'];
    }

    /**
     * Check if merchant is Full Coverage
     *
     * @return bool
     */
    public function isFullCoverage()
    {
        $response = $this->getMerchant();
        return isset($response['merchant_preferences']['merchant_supplied_insurance']) && $response['merchant_preferences']['merchant_supplied_insurance'];
    }

    public function getMerchantId()
    {
        $response = $this->getMerchant();
        return $response['id'] ?? '';
    }

    /**
     * Check if it's opt-in
     *
     * @return bool
     */
    public function isOptIn()
    {
        $response = $this->getMerchant();
        return isset($response['has_route_plus']) && $response['has_route_plus'] && isset($response['merchant_preferences']['opt_in']) && $response['merchant_preferences']['opt_in'];
    }

    public function isOptOut()
    {
        $response = $this->getMerchant();
        return isset($response['has_route_plus']) && $response['has_route_plus'] &&
            (
                (isset($response['merchant_preferences']['opt_out']) && $response['merchant_preferences']['opt_out']) ||
                (!isset($response['merchant_preferences']['opt_in']) && !isset($response['merchant_preferences']['opt_out']))
            );
    }

    /**
     * @param $responseFromRouteApi
     *
     * @return mixed
     */
    protected function getMerchantByDomain($responseFromRouteApi)
    {
        if (!$responseFromRouteApi) {
            return false;
        }

        $merchantFound = false;

        foreach ($responseFromRouteApi as $merchant) {
            if (!$merchantFound) {
                $merchantFound = $merchant;
            }

            if ($merchant['store_domain'] == $this->helper->getCurrentStoreDomain()) {
                $merchantFound = $merchant;
            }
        }

        return $merchantFound;
    }

    /**
     * Update merchant status
     *
     * @param $status
     * @param $merchantData
     * @return mixed
     */
    public function updateMerchantStatus($status, $merchantData = false)
    {
        if (!$merchantData) {
            $merchantData = $this->getMerchant();
        }
        if (isset($merchantData['id'])) {
            try {
                //change merchant status on Route API
                $response = $this
                    ->postMethod(
                        $this->getRouteApiUrl(self::API_ENDPOINT . '/' . $merchantData['id']),
                        ['status' => $status]
                    )->execute([parent::CREATED_HTTP_CODE, parent::SUCCESS_HTTP_CODE]);
                return $response;
            } catch (\Exception $exception) {
                $this->logger->error(
                    "Merchant update has failed reason:" . $exception->getMessage(),
                    $exception->getTrace(),
                    [
                        'params' => $this->getData(),
                        'method' => $this->getCurrentMethod(),
                        'endpoint' => $this->getUrl(),
                    ]
                );
                return false;
            }
        }
        return true;
    }
}
