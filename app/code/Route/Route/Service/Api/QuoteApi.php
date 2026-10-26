<?php
/**
 * QuoteApi
 * PHP version 8
 *
 * @category Class
 * @package  Route\Route\Service
 * @author   Route Protect team
 * @link     https://docs.route.com/docs/
 * /
 *
 * namespace Route\Route\Service\Api;
 * PHP version 8
 *
 * @category Class
 * @package  Route\Route\Service
 * @author   Route Protect team
 * @link     https://docs.route.com/docs/
 */

namespace Route\Route\Service\Api;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
# 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
# "Fix broken Route_Route QuoteApi caching and FulfilledPromise bug, increase Merchant cache TTL to 86400s, use Magento config cache in Setup::getSetupCheckWidget, and eliminate duplicate checkWidget calls and full-screen loader blocking":
# https://github.com/smokeday-com/m/issues/23
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use Magento\Framework\App\CacheInterface;
use Psr\Http\Message\ResponseInterface;
use Route\Route\Helper\Data;
use Route\Route\Service\ApiException;
use Route\Route\Service\Configuration;
use Route\Route\Service\HeaderSelector;
use Route\Route\Service\Model\QuoteInlineResponse200;
use Route\Route\Service\Model\QuoteInlineResponse2001;
use Route\Route\Service\Model\QuoteInlineResponse2002;
use Route\Route\Service\Model\QuoteInput;
use Route\Route\Service\Model\QuoteOutput;
use Route\Route\Service\ObjectSerializer;

/**
 * QuoteApi Class Doc Comment
 *
 * @category Class
 * @package  Route\Route\Service
 * @author   Protect Team
 * @link     https://github.com/swagger-api/swagger-codegen
 */
class QuoteApi
{
    const EMPTY_QUOTE = '{
        "id": "error",
        "premium": {
            "currency": "USD",
            "amount": "0"
        },
        "payment_responsible": {
            "type": "no_coverage"
        }
    }';

    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var Configuration
     */
    protected $config;

    /**
     * @var HeaderSelector
     */
    protected $headerSelector;

    protected $cache;
    protected $helper;

    const ROUTE_QUOTE = 'route_quote';
    # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
    # "Fix broken Route_Route QuoteApi caching and FulfilledPromise bug, increase Merchant cache TTL to 86400s, use Magento config cache in Setup::getSetupCheckWidget, and eliminate duplicate checkWidget calls and full-screen loader blocking":
    # https://github.com/smokeday-com/m/issues/23
    const LIFETIME = 3600;
    const INVALID_LIFETIME = 60;
    const INVALID = 'invalid';
    private static array $memoryCache = [];


    /**
     * @param ClientInterface|null $client
     * @param Configuration|null $config
     * @param HeaderSelector|null $selector
     * @param CacheInterface $cache
     */
    public function __construct(
        ClientInterface $client = null,
        Configuration   $config = null,
        HeaderSelector  $selector = null,
        CacheInterface  $cache = null,
        Data            $helper = null
    ) {
        $this->client = $client ?: new Client();
        $this->config = $config ?: new Configuration();
        $this->cache = $cache;
        $this->helper = $helper;
        $this->headerSelector = $selector ?: new HeaderSelector();
    }

    /**
     * @return Configuration
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Operation createQuote
     *
     * Generate a new quote
     *
     * @param QuoteInput $body body (required)
     * @param \DateTime|null $at_date date to be used to get configuration and agreement (optional)
     *
     * @return QuoteOutput
     * @throws \InvalidArgumentException
     * @throws \Route\Route\Service\ApiException on non-2xx response
     */
    public function createQuote(QuoteInput $body, \DateTime $at_date = null): QuoteOutput
    {
        list($response) = $this->createQuoteWithHttpInfo($body, $at_date);
        return $response;
    }

    private function isCacheValid($body, $unserializedCachedResponse): bool
    {
        if (!$unserializedCachedResponse || $unserializedCachedResponse == self::INVALID) {
            return false;
        }
        $decodedContent = json_decode($unserializedCachedResponse['content']);
        # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
        # "Fix broken Route_Route QuoteApi caching and FulfilledPromise bug, increase Merchant cache TTL to 86400s, use Magento config cache in Setup::getSetupCheckWidget, and eliminate duplicate checkWidget calls and full-screen loader blocking":
        # https://github.com/smokeday-com/m/issues/23
        if (!is_object($decodedContent) || !isset($decodedContent->payment_responsible->type)) {
            return false;
        }
        if (isset($decodedContent->premium->currency)) {
            return $body->getCart()['covered']['currency'] == $decodedContent->premium->currency;
        }
        return true;
    }

    private function isValidCache($body, $cachedResponse): bool
    {
        if (empty($cachedResponse)) {
            return false;
        }

        $unserializedCachedResponse = $this->helper->unserialize($cachedResponse);

        return $this->isCacheValid($body, $unserializedCachedResponse);
    }


    # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
    # "Fix broken Route_Route QuoteApi caching and FulfilledPromise bug, increase Merchant cache TTL to 86400s, use Magento config cache in Setup::getSetupCheckWidget, and eliminate duplicate checkWidget calls and full-screen loader blocking":
    # https://github.com/smokeday-com/m/issues/23
    private function buildCacheKey($body): string
    {
        if ($body instanceof QuoteInput && is_array($body->getCart())) {
            $cart = $body->getCart();
            $currency = (string)($cart['covered']['currency'] ?? 'USD');
            $amount = number_format((float)($cart['covered']['amount'] ?? 0), 2, '.', '');
            return self::ROUTE_QUOTE . '_' . md5($currency . '|' . $amount);
        }
        return self::ROUTE_QUOTE . '_' . hash('md5', $body . '');
    }

    private function updateCache($body, $response): array
    {
        $statusCode = $response->getStatusCode();
        $responseHeaders = $response->getHeaders();
        $content = $response->getBody()->getContents();
        $cacheKey = $this->buildCacheKey($body);

        # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
        # "Fix broken Route_Route QuoteApi caching and FulfilledPromise bug, increase Merchant cache TTL to 86400s, use Magento config cache in Setup::getSetupCheckWidget, and eliminate duplicate checkWidget calls and full-screen loader blocking":
        # https://github.com/smokeday-com/m/issues/23
        if ($statusCode >= 200 && $statusCode <= 299) {
            $serialized = $this->helper->serialize([
                'status' => $statusCode,
                'responseHeader' => $responseHeaders,
                'content' => $content,
            ]);
            self::$memoryCache[$cacheKey] = $serialized;
            $this->cache->save(
                $serialized,
                $cacheKey,
                [],
                self::LIFETIME
            );
        }

        return [$statusCode, $responseHeaders, json_decode($content)];
    }

    private function loadFromCache($body): mixed
    {
        $cacheKey = $this->buildCacheKey($body);
        # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
        # "Fix broken Route_Route QuoteApi caching and FulfilledPromise bug, increase Merchant cache TTL to 86400s, use Magento config cache in Setup::getSetupCheckWidget, and eliminate duplicate checkWidget calls and full-screen loader blocking":
        # https://github.com/smokeday-com/m/issues/23
        if (isset(self::$memoryCache[$cacheKey])) {
            return self::$memoryCache[$cacheKey];
        }
        $cached = $this->cache->load($cacheKey);
        if ($cached) {
            self::$memoryCache[$cacheKey] = $cached;
        }
        return $cached;
    }

    private function invalidateCache($body):void
    {
        $cacheKey = $this->buildCacheKey($body);
        self::$memoryCache[$cacheKey] = self::INVALID;
        $this->cache->save(self::INVALID, $cacheKey, [], self::INVALID_LIFETIME);
    }

    /**
     * Operation createQuoteWithHttpInfo
     *
     * Generate a new quote
     *
     * @param QuoteInput $body (required)
     * @param \DateTime $at_date date to be used to get configuration and agreement (optional)
     *
     * @return array of \Route\Route\Service\Model\InlineResponse200, HTTP status code, HTTP response headers (array of strings)
     * @throws \InvalidArgumentException
     * @throws \Route\Route\Service\ApiException on non-2xx response
     */
    public function createQuoteWithHttpInfo($body, $at_date = null)
    {
        $returnType = [
            'paid_by_customer' => '\Route\Route\Service\Model\QuoteInlineResponse2001',
            'paid_by_merchant' => '\Route\Route\Service\Model\QuoteInlineResponse2002',
            'no_coverage' => '\Route\Route\Service\Model\QuoteInlineResponse200'
        ];

        try {

            $cachedResponse = $this->loadFromCache($body);

            if ($this->isValidCache($body, $cachedResponse)) {//check if cache is still valid

                list($statusCode, $responseHeaders, $decodedContent) = $this->extractCachedContent($cachedResponse);

                return [
                    ObjectSerializer::deserialize($decodedContent, $returnType[$decodedContent->payment_responsible->type], []),
                    $statusCode,
                    $responseHeaders
                ];

            }

            $request = $this->createQuoteRequest($body, $at_date);
            $options = $this->createHttpClientOption();

            try {
                $response = $this->client->sendAsync($request, $options)->wait();
                list($statusCode, $responseHeaders, $decodedContent) = $this->updateCache($body, $response);
            }catch (RequestException $e) {

                $this->invalidateCache($body);
                return [
                    ObjectSerializer::deserialize(
                        json_decode(self::EMPTY_QUOTE),
                        '\Route\Route\Service\Model\QuoteInlineResponse400',
                        []
                    ), 400,
                    []
                ];
            }

            if ($statusCode < 200 || $statusCode > 299) {
                return [
                    ObjectSerializer::deserialize(
                        json_decode(self::EMPTY_QUOTE),
                        '\Route\Route\Service\Model\QuoteInlineResponse400',
                        []
                    ),
                    $statusCode,
                    $responseHeaders
                ];
            }

            return [
                ObjectSerializer::deserialize($decodedContent, $returnType[$decodedContent->payment_responsible->type], []),
                $statusCode,
                $responseHeaders
            ];

        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\Route\Route\Service\Model\QuoteInlineResponse200',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    break;
                case 404:
                case 400:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\Route\Route\Service\Model\ErrorMsg',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    break;
            }
            throw $e;
        }
    }

    /**
     * Operation createQuoteAsync
     *
     * Generate a new quote
     *
     * @param QuoteInput $body (required)
     * @param \DateTime $at_date date to be used to get configuration and agreement (optional)
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     * @throws \InvalidArgumentException
     */
    public function createQuoteAsync($body, $at_date = null)
    {
        return $this->createQuoteAsyncWithHttpInfo($body, $at_date)
            ->then(
                function ($response) {
                    return $response[0];
                },
                function ($e) use ($body) {
                    $this->invalidateCache($body);
                    return
                        ObjectSerializer::deserialize(
                            json_decode(self::EMPTY_QUOTE),
                            '\Route\Route\Service\Model\QuoteInlineResponse400',
                            []
                        );
                }
            );
    }

    /**
     * Operation createQuoteAsyncWithHttpInfo
     *
     * Generate a new quote
     *
     * @param QuoteInput $body (required)
     * @param \DateTime $at_date date to be used to get configuration and agreement (optional)
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     * @throws \InvalidArgumentException
     */
    public function createQuoteAsyncWithHttpInfo($body, $at_date = null)
    {
        $returnType = [
            'paid_by_customer' => '\Route\Route\Service\Model\QuoteInlineResponse2001',
            'paid_by_merchant' => '\Route\Route\Service\Model\QuoteInlineResponse2002',
            'no_coverage' => '\Route\Route\Service\Model\QuoteInlineResponse200'
        ];

        $request = $this->createQuoteRequest($body, $at_date);

        $cachedResponse = $this->loadFromCache($body);

        # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
        # "Fix broken Route_Route QuoteApi caching and FulfilledPromise bug, increase Merchant cache TTL to 86400s, use Magento config cache in Setup::getSetupCheckWidget, and eliminate duplicate checkWidget calls and full-screen loader blocking":
        # https://github.com/smokeday-com/m/issues/23
        if ($cachedResponse === self::INVALID) {
            return new FulfilledPromise([
                ObjectSerializer::deserialize(
                    json_decode(self::EMPTY_QUOTE),
                    '\Route\Route\Service\Model\QuoteInlineResponse400',
                    []
                ),
                400,
                []
            ]);
        }

        if ($this->isValidCache($body, $cachedResponse)) {//check if cache is still valid

            list($statusCode, $responseHeaders, $decodedContent) = $this->extractCachedContent($cachedResponse);

            # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
            # "Fix broken Route_Route QuoteApi caching and FulfilledPromise bug, increase Merchant cache TTL to 86400s, use Magento config cache in Setup::getSetupCheckWidget, and eliminate duplicate checkWidget calls and full-screen loader blocking":
            # https://github.com/smokeday-com/m/issues/23
            return new FulfilledPromise([
                ObjectSerializer::deserialize($decodedContent, $returnType[$decodedContent->payment_responsible->type], []),
                $statusCode,
                $responseHeaders
            ]);

        }

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
                # "Fix broken Route_Route QuoteApi caching and FulfilledPromise bug, increase Merchant cache TTL to 86400s, use Magento config cache in Setup::getSetupCheckWidget, and eliminate duplicate checkWidget calls and full-screen loader blocking":
                # https://github.com/smokeday-com/m/issues/23
                function (ResponseInterface $response) use ($returnType, $body) {
                    list($statusCode, $responseHeaders, $content) = $this->updateCache($body, $response);

                    if ($statusCode < 200 || $statusCode > 299) {
                        return [
                            ObjectSerializer::deserialize(
                                json_decode(self::EMPTY_QUOTE),
                                '\Route\Route\Service\Model\QuoteInlineResponse400',
                                []
                            ),
                            $statusCode,
                            $responseHeaders
                        ];
                    }

                    return [
                        ObjectSerializer::deserialize($content, $returnType[$content->payment_responsible->type], []),
                        $statusCode,
                        $responseHeaders
                    ];

                },
                function ($exception) {
                    $response = $exception->getResponse();
                    $statusCode = $response->getStatusCode();
                    throw new ApiException(
                        sprintf(
                            '[%d] Error connecting to the API (%s)',
                            $statusCode,
                            $exception->getRequest()->getUri()
                        ),
                        $statusCode,
                        $response->getHeaders(),
                        $response->getBody()
                    );
                }
            );
    }

    /**
     * Create request for operation 'createQuote'
     *
     * @param QuoteInput $body (required)
     * @param \DateTime $at_date date to be used to get configuration and agreement (optional)
     *
     * @return \GuzzleHttp\Psr7\Request
     * @throws \InvalidArgumentException
     */
    protected function createQuoteRequest($body, $at_date = null)
    {
        // verify the required parameter 'body' is set
        if ($body === null || (is_array($body) && count($body) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $body when calling createQuote'
            );
        }

        $resourcePath = '/v2/quotes';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        if ($at_date !== null) {
            $queryParams['at_date'] = ObjectSerializer::toQueryValue($at_date, 'date-time');
        }


        // body params
        $_tempBody = null;
        if (isset($body)) {
            $_tempBody = $body;
        }

        if ($multipart) {
            $headers = $this->headerSelector->selectHeadersForMultipart(
                ['application/json']
            );
        } else {
            $headers = $this->headerSelector->selectHeaders(
                ['application/json'],
                ['application/json']
            );
        }

        // for model (json/xml)
        if (isset($_tempBody)) {
            // $_tempBody is the method argument, if present
            $httpBody = $_tempBody;
            // \stdClass has no __toString(), so we should encode it manually
            if ($httpBody instanceof \stdClass && $headers['Content-Type'] === 'application/json') {
                $httpBody = \GuzzleHttp\json_encode($httpBody);
            }
        } elseif (count($formParams) > 0) {
            if ($multipart) {
                $multipartContents = [];
                foreach ($formParams as $formParamName => $formParamValue) {
                    $multipartContents[] = [
                        'name' => $formParamName,
                        'contents' => $formParamValue
                    ];
                }
                // for HTTP post (form)
                $httpBody = new MultipartStream($multipartContents);

            } elseif ($headers['Content-Type'] === 'application/json') {
                $httpBody = \GuzzleHttp\json_encode($formParams);

            } else {
                // for HTTP post (form)
                $httpBody = \GuzzleHttp\Psr7\Query::build($formParams);
            }
        }


        $defaultHeaders = [];
        if ($this->config->getUserAgent()) {
            $defaultHeaders['User-Agent'] = $this->config->getUserAgent();
        }

        $headers = array_merge(
            $defaultHeaders,
            $headerParams,
            $headers
        );

        $query = \GuzzleHttp\Psr7\Query::build($queryParams);
        return new Request(
            'POST',
            $this->config->getHost() . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Create http client option
     *
     * @return array of http client options
     * @throws \RuntimeException on file opening failure
     */
    protected function createHttpClientOption()
    {
        $options = [];
        if ($this->config->getDebug()) {
            $options[RequestOptions::DEBUG] = fopen($this->config->getDebugFile(), 'a');
            if (!$options[RequestOptions::DEBUG]) {
                throw new \RuntimeException('Failed to open the debug file: ' . $this->config->getDebugFile());
            }
        }

        return $options;
    }

    /**
     * @param string $cachedResponse
     * @return array
     */
    public function extractCachedContent(string $cachedResponse): array
    {
        $unserializedCachedResponse = $this->helper->unserialize($cachedResponse);
        $statusCode = $unserializedCachedResponse['status'];
        $responseHeaders = $unserializedCachedResponse['responseHeader'];
        $content = json_decode($unserializedCachedResponse['content']);
        return [$statusCode, $responseHeaders, $content];
    }
}
