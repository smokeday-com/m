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

use Magento\Framework\App\CacheInterface;
use Magento\Framework\HTTP\ZendClientFactory;
use Route\Route\Logger\Sentry;
use Route\Route\Helper\Data;
use Route\Route\Helper\Setup;
use Route\Route\Model\Route\Operation\FallbackFactory;
use Magento\Framework\App\ProductMetadataInterface;

/**
 * Generic Route API client
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
abstract class Client
{
    const LIVE_API_URL = 'https://api.route.com';
    const V1 = 'v1';
    const V2 = 'v2';
    const SANDBOX_API_URL = "https://api-stage.route.com";
    const SUCCESS_HTTP_CODE = 200;
    const CREATED_HTTP_CODE = 201;
    const CONFLICT_HTTP_CODE = 409;
    const FORBIDDEN_HTTP_CODE = 403;
    const UNAUTHORIZED_HTTP_CODE = 401;
    const NOT_FOUND_HTTP_CODE = 404;
    const DEFAULT_TIMEOUT = 3;
    const GET_METHOD = "GET";
    const POST_METHOD = "POST";
    const ROUTEAPP_ENVIRONMENT_ENDPOINT = 'ROUTEAPP_ENVIRONMENT_ENDPOINT';
    const STAGE = 'stage';

    protected $httpClientFactory;
    protected $helper;
    protected $json;
    protected $client;
    protected $logger;
    protected $isRetrying;
    protected $operation;
    protected $fallback;
    protected $helperSetup;
    protected $cache;
    protected $productMetadata;
    private $auth = true;
    private $usePublicToken = false;
    private $headers = [];
    private $failed = false;
    private $lastInfo = false;
    private $_method;
    private $_data = [];
    private $_url = '';

    /**
     * Client constructor
     *
     * @param ZendClientFactory $httpClientFactory http client
     * @param Data $helper Route helper
     * @param Sentry $logger Logger
     * @param FallbackFactory $fallback Fallback Factory
     * @param Setup $helperSetup Route helper setup
     * @param CacheInterface $cache cache responses
     * @param ProductMetadataInterface $productMetadata Product Metadata interface
     */
    public function __construct(
        ZendClientFactory $httpClientFactory,
        Data $helper,
        Sentry $logger,
        FallbackFactory $fallback,
        Setup $helperSetup,
        CacheInterface $cache,
        ProductMetadataInterface $productMetadata
    ) {
        $this->fallback = $fallback;
        $this->helper = $helper;
        $this->logger = $logger;
        $this->cache = $cache;
        $this->httpClientFactory = $httpClientFactory;
        $this->helperSetup = $helperSetup;
        $this->productMetadata = $productMetadata;
    }

    /**
     * Initialize rest client to Route API defining basic headers
     *
     * @return void
     */
    public function initClient()
    {
        $this->client = curl_init();

        curl_setopt($this->client, CURLOPT_TIMEOUT, self::DEFAULT_TIMEOUT);

        $this->addHeader('Content-Type', 'Content-Type: application/json');

        if ($this->auth) {
            $token = $this->usePublicToken ? $this->helper->getMerchantPublicToken() : $this->helper->getMerchantSecretToken();
            $this->addHeader('token', 'token: ' . $token);
        }

        //platform
        $this->addHeader('platform', 'platform: magento2');

        //magento version
        $platformVersion = $this->productMetadata->getName() . ' ';
        $platformVersion.= $this->productMetadata->getEdition() . ' ';
        $platformVersion.= $this->productMetadata->getVersion();
        $this->addHeader('platform_version', 'platform_version: ' . $platformVersion);

        //module version
        $this->addHeader('module_version', 'module_version: ' . $this->helper->getVersion());

        curl_setopt($this->client, CURLOPT_HTTPHEADER, $this->getHeaders());
    }

    /**
     * Set authentication as not required
     *
     * @return Client
     */
    public function setUnauthorizedRequest()
    {
        $this->auth = false;
        return $this;
    }

    /**
     * Use public token instead private
     *
     * @return Client
     */
    public function usePublicToken()
    {
        $this->usePublicToken = true;
        return $this;
    }

    /**
     * Add extra header
     *
     * @param $name
     * @param $header
     *
     * @return Client
     */
    public function addHeader($name, $header)
    {
        $this->headers[$name] = $header;
        return $this;
    }

    /**
     * Get headers
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }
    /**
     * Change scope if needed
     *
     * @param $scopeCode
     * @return $this
     */
    public function setScopeCode($scopeCode)
    {
        if ($scopeCode) {
            $this->helper->setScopeCode($scopeCode);
        }
        return $this;
    }

    /**
     * Build query string based on array passed by param
     *
     * @param array $params params to be converted in query string
     *
     * @return string
     */
    public function buildQuery($params)
    {
        return http_build_query($params);
    }

    /**
     * Get Route Api Url
     *
     * @param string $path path to be concatenated to the base url
     *
     * @return string
     */
    public function getRouteApiUrl($path, $version = self::V1)
    {
        $custom_env = getenv(self::ROUTEAPP_ENVIRONMENT_ENDPOINT);

        if (!$custom_env) {
            $custom_env = $_SERVER[self::ROUTEAPP_ENVIRONMENT_ENDPOINT] ?? '';
        }

        $routeUrl = $custom_env == self::STAGE ? self::SANDBOX_API_URL : self::LIVE_API_URL;

        return $routeUrl . '/' . $version . '/'. $path;
    }

    /**
     * Prepare GET http Method to be executed
     *
     * @param string $url url to be reached
     * @param null $data data to be passed as param
     *
     * @return Client
     */
    protected function getMethod($url, $data = null)
    {
        $queryString = isset($data) ? '?' . $this->buildQuery($data) : '';
        return $this->_prepareHttpMethod(
            $url . $queryString,
            self::GET_METHOD
        );
    }

    /**
     * Prepare POST http Method to be executed
     *
     * @param string $url url to be reached
     * @param array $data data to be passed as param
     *
     * @return Client
     */
    protected function postMethod($url, $data = [])
    {
        return $this->_prepareHttpMethod($url, self::POST_METHOD, $data);
    }

    /**
     * Prepare http method
     *
     * @param string $url base url
     * @param string $method method to be executed
     * @param array $data data to be passed as param
     *
     * @return $this
     */
    private function _prepareHttpMethod($url, $method, $data = [])
    {
        $this->_method = $method;
        $this->_data = $data;
        $this->_url = $url;

        try {
            $options = [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false
            ];

            if ($method == self::POST_METHOD) {
                $options[CURLOPT_POST] = true;
                $options[CURLOPT_POSTFIELDS] = $this->helper->serialize($data);
            } elseif ($method == self::GET_METHOD) {
                $options[CURLOPT_HTTPGET] = true;
            }

            curl_setopt_array($this->getClient(), $options);
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage(), $exception->getTrace());
        }

        return $this;
    }

    /**
     * Get current client if it exists or create a new one
     *
     * @return mixed
     */
    public function getClient()
    {
        if (empty($this->client)) {
            $this->initClient();
        }
        return $this->client;
    }

    /**
     * @return mixed
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * @param mixed $operation
     */
    public function setOperation($operation)
    {
        $this->operation = $operation;
    }

    /**
     * Executed prepared method
     *
     * @param array $acceptedSuccessCode code expected to success
     *
     * @return bool|string|array
     * @throws \Exception
     */
    protected function execute($acceptedSuccessCode = [self::SUCCESS_HTTP_CODE])
    {
        $this->setFailed(false);

        /** Add validation to avoid invalid requests */
        if ($this->auth && ($this->helper->isInvalidMerchant() || !$this->helper->isRouteModuleEnable())) {
            return false;
        }

        $response = curl_exec($this->getClient());
        $info = $this->getInfo();
        $this->_close();

        if (!is_array($acceptedSuccessCode)) {
            $acceptedSuccessCode = [$acceptedSuccessCode];
        }

        if (in_array($info['http_code'], $acceptedSuccessCode)) {
            return $this->helper->unserialize($response);
        }

        $this->setFailed(true);

        throw new \Exception('Response code: ' . $info['http_code'], $info['http_code']);
    }

    /**
     * Get info from curl request
     *
     * @return mixed
     *
     * @throws Exception
     */
    protected function getInfo()
    {
        if (is_null($this->client)) {
            return $this->lastInfo;
        }
        return curl_getinfo($this->getClient());
    }

    /**
     * Close current curl connection
     *
     * @return mixed
     *
     * @throws Exception
     */
    private function _close()
    {
        $this->lastInfo = $this->getInfo();
        curl_close($this->getClient());
        $this->client = null;
    }

    public function hasConflicted()
    {
        $info = $this->getInfo();
        return $info['http_code'] == self::CONFLICT_HTTP_CODE;
    }

    public function isSuccess()
    {
        $info = $this->getInfo();
        return $info['http_code'] == self::SUCCESS_HTTP_CODE;
    }

    public function isForbidden()
    {
        $info = $this->getInfo();
        return $info['http_code'] == self::FORBIDDEN_HTTP_CODE || $info['http_code'] == self::UNAUTHORIZED_HTTP_CODE;
    }

    public function hasCreated()
    {
        $info = $this->getInfo();
        return $info['http_code'] == self::CREATED_HTTP_CODE;
    }

    public function hasFailed()
    {
        return $this->failed;
    }

    public function setFailed($failed)
    {
        return $this->failed = $failed;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->_url;
    }

    /**
     * @return string
     */
    public function getCurrentMethod()
    {
        return $this->_method;
    }
}
