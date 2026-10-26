<?php

namespace Route\Route\Service;

use Magento\Framework\App\CacheInterface;
use Route\Route\Helper\Data;
use Route\Route\Service\Api\OrdersApi;
use Route\Route\Service\Api\PublicSettingsApi;
use Route\Route\Service\Api\QuoteApi;
use Route\Route\Service\Api\ShipmentsApi;
use GuzzleHttp\Client as GuzzClient;

class ApiClientBuilder
{
    private Data $helper;
    private CacheInterface $cache;

    public function __construct(
        Data $helper,
        CacheInterface $cache,
    ) {
        $this->helper = $helper;
        $this->cache = $cache;
    }
    public function buildOrderApiClient(): OrdersApi
    {
        $orderApiConfiguration = Configuration::getDefaultConfiguration()->setApiKey('token', $this->helper->getMerchantSecretToken());
        return new OrdersApi(new GuzzClient([]), $orderApiConfiguration);
    }

    public function buildShipmentApiClient(): ShipmentsApi
    {
        $shipmentApiConfiguration = Configuration::getDefaultConfiguration()->setApiKey('token', $this->helper->getMerchantSecretToken());
        return new ShipmentsApi(new GuzzClient([]), $shipmentApiConfiguration);
    }


    public function buildQuoteApiClient(): QuoteApi
    {
        return new QuoteApi(
            new GuzzClient(),
            null,
            null,
            $this->cache,
            $this->helper
        );
    }

    public function buildPublicSettingsApiClient(): PublicSettingsApi
    {
        return new PublicSettingsApi(
            new GuzzClient()
        );
    }
}
