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

use Exception as ExceptionAlias;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\HTTP\ZendClientFactory;
use Route\Route\Logger\Sentry;
use Route\Route\Helper\Data;
use Route\Route\Helper\Setup;
use Route\Route\Model\Route\Operation\FallbackFactory;
use Magento\Framework\App\ProductMetadataInterface;

/**
 * Order Route API client
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
class Widget extends Client
{
    const API_ENDPOINT = 'asset-settings';

    protected $helper;

    /**
     * Order constructor.
     * @param ZendClientFactory $httpClientFactory
     * @param Data $helper
     * @param Sentry $logger
     * @param OrderParser $parser
     * @param FallbackFactory $fallback
     * @param Shipment $shipmentClient
     * @param Setup $helperSetup Route helper setup
     * @param CacheInterface $cache cache responses
     * @param ProductMetadataInterface $productMetadata Product Metadata interface
     */
    public function __construct(
        ZendClientFactory $httpClientFactory,
        Data $helper,
        Sentry $logger,
        OrderParser $parser,
        FallbackFactory $fallback,
        Shipment $shipmentClient,
        Setup $helperSetup,
        CacheInterface $cache,
        ProductMetadataInterface $productMetadata
    ) {
        $this->helper = $helper;
        parent::__construct($httpClientFactory, $helper, $logger, $fallback, $helperSetup, $cache, $productMetadata);
    }

    /**
     * Get a thank you page widget
     *
     * @param $host
     *
     * @return mixed
     * @throws ExceptionAlias
     */
    public function getThankYouPageWidget($host)
    {
        try {
            $response = $this
                ->setUnauthorizedRequest()
                ->getMethod(
                    $this->getRouteApiUrl(self::API_ENDPOINT . '/' . $host)
                )->execute([self::SUCCESS_HTTP_CODE]);
        } catch (\Exception $exception) {
            $this->logger->error(
                "Error while trying to get thank you page asset: " . $exception->getMessage(),
                $exception->getTrace(),
                [
                    'params' => $this->getData(),
                    'method' => $this->getCurrentMethod(),
                    'endpoint' => $this->getUrl(),
                ]
            );
            return false;
        }

        if ($response) {
            $response = isset($response['asset_settings']['asset_live']) && $response['asset_settings']['asset_live'] ?
                $response :
                false;
        }

        return $response;
    }
}
