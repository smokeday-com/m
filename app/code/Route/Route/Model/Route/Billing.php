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
 * Get billing information from merchant account
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
class Billing extends Client
{
    const API_ENDPOINT = 'billing';

    /**
     * Get Merchant's billing information from Route API
     *
     * @return bool|mixed
     */
    public function getBilling()
    {
        try {
            return $this->getMethod(
                $this->getRouteApiUrl(self::API_ENDPOINT)
            )->execute();
        } catch (\Exception $exception) {
            $this->logger->error(
                "Error getting billing information: " . $exception->getMessage(),
                $exception->getTrace(),
                [
                    'params' => $this->getData(),
                    'method' => $this->getCurrentMethod(),
                    'endpoint' => $this->getUrl(),
                ]
            );
        }
    }
}
