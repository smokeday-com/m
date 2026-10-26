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
 * Compatibility report
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
class Compatibility extends Client
{

    public function send($data)
    {
        try {
            $response = $this->postMethod(
                $this->getRouteApiUrl('partner-integration/plugin-status'),
                $data
            )->execute([parent::CREATED_HTTP_CODE, parent::SUCCESS_HTTP_CODE]);
            if ($this->hasFailed()) {
                $this->logger->debug("Compatibility API has failed: " . print_r($this->getInfo(), 1));
            } else {
                $this->logger->debug("Successfully sent compatibility report.");
                $this->helperSetup->setSetupCheckWidget($this->helperSetup::SETUP_CHECK_WIDGET_API_CALL);
            }
            return $response;
        } catch (\Exception $exception) {
            $this->logger->debug("Compatibility API has failed reason: " . $exception->getMessage(), $exception->getTrace());
            return false;
        }
    }
}
