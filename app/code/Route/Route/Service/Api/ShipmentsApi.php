<?php
/**
 * ShipmentsApi
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
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use Route\Route\Service\ApiException;
use Route\Route\Service\Configuration;
use Route\Route\Service\HeaderSelector;
use Route\Route\Service\ObjectSerializer;

/**
 * ShipmentsApi Class Doc Comment
 *
 * @category Class
 * @package  Route\Route\Service
 * @author   Route Protect team
 * @link     https://docs.route.com/docs/
 */
class ShipmentsApi
{
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

    /**
     * @param ClientInterface $client
     * @param Configuration   $config
     * @param HeaderSelector  $selector
     */
    public function __construct(
        ClientInterface $client = null,
        Configuration $config = null,
        HeaderSelector $selector = null
    ) {
        $this->client = $client ?: new Client();
        $this->config = $config ?: new Configuration();
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
     * Operation cancelShipment
     *
     * Cancel Shipment
     *
     * @param  string $tracking_number tracking number or route shipment ID (required)
     * @param  string $merchant_id merchant_id, if called using a user token (optional)
     * @param  string $source_order_id a source_order_id from the order in the shipment, helps with accuracy (optional)
     *
     * @throws \Route\Route\Service\ApiException on non-2xx response
     * @throws \InvalidArgumentException
     * @return void
     */
    public function cancelShipment($tracking_number, $merchant_id = null, $source_order_id = null)
    {
        $this->cancelShipmentWithHttpInfo($tracking_number, $merchant_id, $source_order_id);
    }

    /**
     * Operation cancelShipmentWithHttpInfo
     *
     * Cancel Shipment
     *
     * @param  string $tracking_number tracking number or route shipment ID (required)
     * @param  string $merchant_id merchant_id, if called using a user token (optional)
     * @param  string $source_order_id a source_order_id from the order in the shipment, helps with accuracy (optional)
     *
     * @throws \Route\Route\Service\ApiException on non-2xx response
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function cancelShipmentWithHttpInfo($tracking_number, $merchant_id = null, $source_order_id = null)
    {
        $request = $this->cancelShipmentRequest($tracking_number, $merchant_id, $source_order_id);

        try {
            $options = $this->createHttpClientOption();
            try {
                $response = $this->client->send($request, $options);
            } catch (RequestException $e) {
                throw new ApiException(
                    "[{$e->getCode()}] {$e->getMessage()}",
                    $e->getCode(),
                    $e->getResponse() ? $e->getResponse()->getHeaders() : null,
                    $e->getResponse() ? $e->getResponse()->getBody()->getContents() : null
                );
            }

            $statusCode = $response->getStatusCode();

            if ($statusCode < 200 || $statusCode > 299) {
                throw new ApiException(
                    sprintf(
                        '[%d] Error connecting to the API (%s)',
                        $statusCode,
                        $request->getUri()
                    ),
                    $statusCode,
                    $response->getHeaders(),
                    $response->getBody()
                );
            }

            return [null, $statusCode, $response->getHeaders()];

        } catch (ApiException $e) {
            switch ($e->getCode()) {
            }
            throw $e;
        }
    }

    /**
     * Operation cancelShipmentAsync
     *
     * Cancel Shipment
     *
     * @param  string $tracking_number tracking number or route shipment ID (required)
     * @param  string $merchant_id merchant_id, if called using a user token (optional)
     * @param  string $source_order_id a source_order_id from the order in the shipment, helps with accuracy (optional)
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function cancelShipmentAsync($tracking_number, $merchant_id = null, $source_order_id = null)
    {
        return $this->cancelShipmentAsyncWithHttpInfo($tracking_number, $merchant_id, $source_order_id)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation cancelShipmentAsyncWithHttpInfo
     *
     * Cancel Shipment
     *
     * @param  string $tracking_number tracking number or route shipment ID (required)
     * @param  string $merchant_id merchant_id, if called using a user token (optional)
     * @param  string $source_order_id a source_order_id from the order in the shipment, helps with accuracy (optional)
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function cancelShipmentAsyncWithHttpInfo($tracking_number, $merchant_id = null, $source_order_id = null)
    {
        $returnType = '';
        $request = $this->cancelShipmentRequest($tracking_number, $merchant_id, $source_order_id);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
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
     * Create request for operation 'cancelShipment'
     *
     * @param  string $tracking_number tracking number or route shipment ID (required)
     * @param  string $merchant_id merchant_id, if called using a user token (optional)
     * @param  string $source_order_id a source_order_id from the order in the shipment, helps with accuracy (optional)
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    protected function cancelShipmentRequest($tracking_number, $merchant_id = null, $source_order_id = null)
    {
        // verify the required parameter 'tracking_number' is set
        if ($tracking_number === null || (is_array($tracking_number) && count($tracking_number) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $tracking_number when calling cancelShipment'
            );
        }

        $resourcePath = '/v1/shipments/{tracking-number}/cancel';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        if ($merchant_id !== null) {
            $queryParams['merchant_id'] = ObjectSerializer::toQueryValue($merchant_id, null);
        }
        // query params
        if ($source_order_id !== null) {
            $queryParams['source_order_id'] = ObjectSerializer::toQueryValue($source_order_id, null);
        }

        // path params
        if ($tracking_number !== null) {
            $resourcePath = str_replace(
                '{' . 'tracking-number' . '}',
                ObjectSerializer::toPathValue($tracking_number),
                $resourcePath
            );
        }

        // body params
        $_tempBody = null;

        if ($multipart) {
            $headers = $this->headerSelector->selectHeadersForMultipart(
                []
            );
        } else {
            $headers = $this->headerSelector->selectHeaders(
                [],
                []
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

        // this endpoint requires API key authentication
        $apiKey = $this->config->getApiKeyWithPrefix('token');
        if ($apiKey !== null) {
            $headers['token'] = $apiKey;
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
     * Operation createShipment
     *
     * Create Shipment
     *
     * @param  \Route\Route\Service\Model\V1ShipmentsBody $body body (required)
     *
     * @throws \Route\Route\Service\ApiException on non-2xx response
     * @throws \InvalidArgumentException
     * @return \Route\Route\Service\Model\InlineResponse2002
     */
    public function createShipment($body)
    {
        list($response) = $this->createShipmentWithHttpInfo($body);
        return $response;
    }

    /**
     * Operation createShipmentWithHttpInfo
     *
     * Create Shipment
     *
     * @param  \Route\Route\Service\Model\V1ShipmentsBody $body (required)
     *
     * @throws \Route\Route\Service\ApiException on non-2xx response
     * @throws \InvalidArgumentException
     * @return array of \Route\Route\Service\Model\InlineResponse2002, HTTP status code, HTTP response headers (array of strings)
     */
    public function createShipmentWithHttpInfo($body)
    {
        $returnType = '\Route\Route\Service\Model\InlineResponse2002';
        $request = $this->createShipmentRequest($body);

        try {
            $options = $this->createHttpClientOption();
            try {
                $response = $this->client->send($request, $options);
            } catch (RequestException $e) {
                throw new ApiException(
                    "[{$e->getCode()}] {$e->getMessage()}",
                    $e->getCode(),
                    $e->getResponse() ? $e->getResponse()->getHeaders() : null,
                    $e->getResponse() ? $e->getResponse()->getBody()->getContents() : null
                );
            }

            $statusCode = $response->getStatusCode();

            if ($statusCode < 200 || $statusCode > 299) {
                throw new ApiException(
                    sprintf(
                        '[%d] Error connecting to the API (%s)',
                        $statusCode,
                        $request->getUri()
                    ),
                    $statusCode,
                    $response->getHeaders(),
                    $response->getBody()
                );
            }

            $responseBody = $response->getBody();
            if ($returnType === '\SplFileObject') {
                $content = $responseBody; //stream goes to serializer
            } else {
                $content = $responseBody->getContents();
                if (!in_array($returnType, ['string','integer','bool'])) {
                    $content = json_decode($content);
                }
            }

            return [
                ObjectSerializer::deserialize($content, $returnType, []),
                $response->getStatusCode(),
                $response->getHeaders()
            ];

        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\Route\Route\Service\Model\InlineResponse2002',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    break;
            }
            throw $e;
        }
    }

    /**
     * Operation createShipmentAsync
     *
     * Create Shipment
     *
     * @param  \Route\Route\Service\Model\V1ShipmentsBody $body (required)
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function createShipmentAsync($body)
    {
        return $this->createShipmentAsyncWithHttpInfo($body)
            ->then(
                function ($response) {
                    return $response[0];
                },
                function ($exception) {
                    throw $exception;
                }
            );
    }

    /**
     * Operation createShipmentAsyncWithHttpInfo
     *
     * Create Shipment
     *
     * @param  \Route\Route\Service\Model\V1ShipmentsBody $body (required)
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function createShipmentAsyncWithHttpInfo($body)
    {
        $returnType = '\Route\Route\Service\Model\InlineResponse2002';
        $request = $this->createShipmentRequest($body);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    $responseBody = $response->getBody();
                    if ($returnType === '\SplFileObject') {
                        $content = $responseBody; //stream goes to serializer
                    } else {
                        $content = $responseBody->getContents();
                        if ($returnType !== 'string') {
                            $content = json_decode($content);
                        }
                    }

                    return [
                        ObjectSerializer::deserialize($content, $returnType, []),
                        $response->getStatusCode(),
                        $response->getHeaders()
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
     * Create request for operation 'createShipment'
     *
     * @param  \Route\Route\Service\Model\V1ShipmentsBody $body (required)
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    protected function createShipmentRequest($body)
    {
        // verify the required parameter 'body' is set
        if ($body === null || (is_array($body) && count($body) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $body when calling createShipment'
            );
        }

        $resourcePath = '/v1/shipments';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



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

        // this endpoint requires API key authentication
        $apiKey = $this->config->getApiKeyWithPrefix('token');
        if ($apiKey !== null) {
            $headers['token'] = $apiKey;
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
     * Operation getShipment
     *
     * Read Shipment
     *
     * @param  string $tracking_number tracking number or route shipment ID (required)
     * @param  string $source_order_id a source_order_id from the order in the shipment, helps with accuracy (required)
     * @param  string $merchant_id merchant_id, if called using a user token (optional)
     *
     * @throws \Route\Route\Service\ApiException on non-2xx response
     * @throws \InvalidArgumentException
     * @return \Route\Route\Service\Model\InlineResponse2003
     */
    public function getShipment($tracking_number, $source_order_id, $merchant_id = null)
    {
        list($response) = $this->getShipmentWithHttpInfo($tracking_number, $source_order_id, $merchant_id);
        return $response;
    }

    /**
     * Operation getShipmentWithHttpInfo
     *
     * Read Shipment
     *
     * @param  string $tracking_number tracking number or route shipment ID (required)
     * @param  string $source_order_id a source_order_id from the order in the shipment, helps with accuracy (required)
     * @param  string $merchant_id merchant_id, if called using a user token (optional)
     *
     * @throws \Route\Route\Service\ApiException on non-2xx response
     * @throws \InvalidArgumentException
     * @return array of \Route\Route\Service\Model\InlineResponse2003, HTTP status code, HTTP response headers (array of strings)
     */
    public function getShipmentWithHttpInfo($tracking_number, $source_order_id, $merchant_id = null)
    {
        $returnType = '\Route\Route\Service\Model\InlineResponse2003';
        $request = $this->getShipmentRequest($tracking_number, $source_order_id, $merchant_id);

        try {
            $options = $this->createHttpClientOption();
            try {
                $response = $this->client->send($request, $options);
            } catch (RequestException $e) {
                throw new ApiException(
                    "[{$e->getCode()}] {$e->getMessage()}",
                    $e->getCode(),
                    $e->getResponse() ? $e->getResponse()->getHeaders() : null,
                    $e->getResponse() ? $e->getResponse()->getBody()->getContents() : null
                );
            }

            $statusCode = $response->getStatusCode();

            if ($statusCode < 200 || $statusCode > 299) {
                throw new ApiException(
                    sprintf(
                        '[%d] Error connecting to the API (%s)',
                        $statusCode,
                        $request->getUri()
                    ),
                    $statusCode,
                    $response->getHeaders(),
                    $response->getBody()
                );
            }

            $responseBody = $response->getBody();
            if ($returnType === '\SplFileObject') {
                $content = $responseBody; //stream goes to serializer
            } else {
                $content = $responseBody->getContents();
                if (!in_array($returnType, ['string','integer','bool'])) {
                    $content = json_decode($content);
                }
            }

            return [
                ObjectSerializer::deserialize($content, $returnType, []),
                $response->getStatusCode(),
                $response->getHeaders()
            ];

        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\Route\Route\Service\Model\InlineResponse2003',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    break;
            }
            throw $e;
        }
    }

    /**
     * Operation getShipmentAsync
     *
     * Read Shipment
     *
     * @param  string $tracking_number tracking number or route shipment ID (required)
     * @param  string $source_order_id a source_order_id from the order in the shipment, helps with accuracy (required)
     * @param  string $merchant_id merchant_id, if called using a user token (optional)
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function getShipmentAsync($tracking_number, $source_order_id, $merchant_id = null)
    {
        return $this->getShipmentAsyncWithHttpInfo($tracking_number, $source_order_id, $merchant_id)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation getShipmentAsyncWithHttpInfo
     *
     * Read Shipment
     *
     * @param  string $tracking_number tracking number or route shipment ID (required)
     * @param  string $source_order_id a source_order_id from the order in the shipment, helps with accuracy (required)
     * @param  string $merchant_id merchant_id, if called using a user token (optional)
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function getShipmentAsyncWithHttpInfo($tracking_number, $source_order_id, $merchant_id = null)
    {
        $returnType = '\Route\Route\Service\Model\InlineResponse2003';
        $request = $this->getShipmentRequest($tracking_number, $source_order_id, $merchant_id);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    $responseBody = $response->getBody();
                    if ($returnType === '\SplFileObject') {
                        $content = $responseBody; //stream goes to serializer
                    } else {
                        $content = $responseBody->getContents();
                        if ($returnType !== 'string') {
                            $content = json_decode($content);
                        }
                    }

                    return [
                        ObjectSerializer::deserialize($content, $returnType, []),
                        $response->getStatusCode(),
                        $response->getHeaders()
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
     * Create request for operation 'getShipment'
     *
     * @param  string $tracking_number tracking number or route shipment ID (required)
     * @param  string $source_order_id a source_order_id from the order in the shipment, helps with accuracy (required)
     * @param  string $merchant_id merchant_id, if called using a user token (optional)
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    protected function getShipmentRequest($tracking_number, $source_order_id, $merchant_id = null)
    {
        // verify the required parameter 'tracking_number' is set
        if ($tracking_number === null || (is_array($tracking_number) && count($tracking_number) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $tracking_number when calling getShipment'
            );
        }
        // verify the required parameter 'source_order_id' is set
        if ($source_order_id === null || (is_array($source_order_id) && count($source_order_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $source_order_id when calling getShipment'
            );
        }

        $resourcePath = '/v1/shipments/{tracking-number}';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        if ($merchant_id !== null) {
            $queryParams['merchant_id'] = ObjectSerializer::toQueryValue($merchant_id, null);
        }
        // query params
        if ($source_order_id !== null) {
            $queryParams['source_order_id'] = ObjectSerializer::toQueryValue($source_order_id, null);
        }

        // path params
        if ($tracking_number !== null) {
            $resourcePath = str_replace(
                '{' . 'tracking-number' . '}',
                ObjectSerializer::toPathValue($tracking_number),
                $resourcePath
            );
        }

        // body params
        $_tempBody = null;

        if ($multipart) {
            $headers = $this->headerSelector->selectHeadersForMultipart(
                ['application/json']
            );
        } else {
            $headers = $this->headerSelector->selectHeaders(
                ['application/json'],
                []
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

        // this endpoint requires API key authentication
        $apiKey = $this->config->getApiKeyWithPrefix('token');
        if ($apiKey !== null) {
            $headers['token'] = $apiKey;
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
            'GET',
            $this->config->getHost() . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation updateShipment
     *
     * Update Shipment
     *
     * @param  \Route\Route\Service\Model\ShipmentsTrackingnumberBody $body body (required)
     * @param  string $tracking_number tracking number or route shipment ID (required)
     * @param  string $merchant_id merchant_id, if called using a user token (optional)
     * @param  string $source_order_id a source_order_id from the order in the shipment, helps with accuracy (optional)
     *
     * @throws \Route\Route\Service\ApiException on non-2xx response
     * @throws \InvalidArgumentException
     * @return \Route\Route\Service\Model\InlineResponse2002
     */
    public function updateShipment($body, $tracking_number, $merchant_id = null, $source_order_id = null)
    {
        list($response) = $this->updateShipmentWithHttpInfo($body, $tracking_number, $merchant_id, $source_order_id);
        return $response;
    }

    /**
     * Operation updateShipmentWithHttpInfo
     *
     * Update Shipment
     *
     * @param  \Route\Route\Service\Model\ShipmentsTrackingnumberBody $body (required)
     * @param  string $tracking_number tracking number or route shipment ID (required)
     * @param  string $merchant_id merchant_id, if called using a user token (optional)
     * @param  string $source_order_id a source_order_id from the order in the shipment, helps with accuracy (optional)
     *
     * @throws \Route\Route\Service\ApiException on non-2xx response
     * @throws \InvalidArgumentException
     * @return array of \Route\Route\Service\Model\InlineResponse2002, HTTP status code, HTTP response headers (array of strings)
     */
    public function updateShipmentWithHttpInfo($body, $tracking_number, $merchant_id = null, $source_order_id = null)
    {
        $returnType = '\Route\Route\Service\Model\InlineResponse2002';
        $request = $this->updateShipmentRequest($body, $tracking_number, $merchant_id, $source_order_id);

        try {
            $options = $this->createHttpClientOption();
            try {
                $response = $this->client->send($request, $options);
            } catch (RequestException $e) {
                throw new ApiException(
                    "[{$e->getCode()}] {$e->getMessage()}",
                    $e->getCode(),
                    $e->getResponse() ? $e->getResponse()->getHeaders() : null,
                    $e->getResponse() ? $e->getResponse()->getBody()->getContents() : null
                );
            }

            $statusCode = $response->getStatusCode();

            if ($statusCode < 200 || $statusCode > 299) {
                throw new ApiException(
                    sprintf(
                        '[%d] Error connecting to the API (%s)',
                        $statusCode,
                        $request->getUri()
                    ),
                    $statusCode,
                    $response->getHeaders(),
                    $response->getBody()
                );
            }

            $responseBody = $response->getBody();
            if ($returnType === '\SplFileObject') {
                $content = $responseBody; //stream goes to serializer
            } else {
                $content = $responseBody->getContents();
                if (!in_array($returnType, ['string','integer','bool'])) {
                    $content = json_decode($content);
                }
            }

            return [
                ObjectSerializer::deserialize($content, $returnType, []),
                $response->getStatusCode(),
                $response->getHeaders()
            ];

        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\Route\Route\Service\Model\InlineResponse2002',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    break;
            }
            throw $e;
        }
    }

    /**
     * Operation updateShipmentAsync
     *
     * Update Shipment
     *
     * @param  \Route\Route\Service\Model\ShipmentsTrackingnumberBody $body (required)
     * @param  string $tracking_number tracking number or route shipment ID (required)
     * @param  string $merchant_id merchant_id, if called using a user token (optional)
     * @param  string $source_order_id a source_order_id from the order in the shipment, helps with accuracy (optional)
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function updateShipmentAsync($body, $tracking_number, $merchant_id = null, $source_order_id = null)
    {
        return $this->updateShipmentAsyncWithHttpInfo($body, $tracking_number, $merchant_id, $source_order_id)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation updateShipmentAsyncWithHttpInfo
     *
     * Update Shipment
     *
     * @param  \Route\Route\Service\Model\ShipmentsTrackingnumberBody $body (required)
     * @param  string $tracking_number tracking number or route shipment ID (required)
     * @param  string $merchant_id merchant_id, if called using a user token (optional)
     * @param  string $source_order_id a source_order_id from the order in the shipment, helps with accuracy (optional)
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function updateShipmentAsyncWithHttpInfo($body, $tracking_number, $merchant_id = null, $source_order_id = null)
    {
        $returnType = '\Route\Route\Service\Model\InlineResponse2002';
        $request = $this->updateShipmentRequest($body, $tracking_number, $merchant_id, $source_order_id);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    $responseBody = $response->getBody();
                    if ($returnType === '\SplFileObject') {
                        $content = $responseBody; //stream goes to serializer
                    } else {
                        $content = $responseBody->getContents();
                        if ($returnType !== 'string') {
                            $content = json_decode($content);
                        }
                    }

                    return [
                        ObjectSerializer::deserialize($content, $returnType, []),
                        $response->getStatusCode(),
                        $response->getHeaders()
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
     * Create request for operation 'updateShipment'
     *
     * @param  \Route\Route\Service\Model\ShipmentsTrackingnumberBody $body (required)
     * @param  string $tracking_number tracking number or route shipment ID (required)
     * @param  string $merchant_id merchant_id, if called using a user token (optional)
     * @param  string $source_order_id a source_order_id from the order in the shipment, helps with accuracy (optional)
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    protected function updateShipmentRequest($body, $tracking_number, $merchant_id = null, $source_order_id = null)
    {
        // verify the required parameter 'body' is set
        if ($body === null || (is_array($body) && count($body) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $body when calling updateShipment'
            );
        }
        // verify the required parameter 'tracking_number' is set
        if ($tracking_number === null || (is_array($tracking_number) && count($tracking_number) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $tracking_number when calling updateShipment'
            );
        }

        $resourcePath = '/v2/shipments/{tracking-number}';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        if ($merchant_id !== null) {
            $queryParams['merchant_id'] = ObjectSerializer::toQueryValue($merchant_id, null);
        }
        // query params
        if ($source_order_id !== null) {
            $queryParams['source_order_id'] = ObjectSerializer::toQueryValue($source_order_id, null);
        }

        // path params
        if ($tracking_number !== null) {
            $resourcePath = str_replace(
                '{' . 'tracking-number' . '}',
                ObjectSerializer::toPathValue($tracking_number),
                $resourcePath
            );
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

        // this endpoint requires API key authentication
        $apiKey = $this->config->getApiKeyWithPrefix('token');
        if ($apiKey !== null) {
            $headers['token'] = $apiKey;
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
     * @throws \RuntimeException on file opening failure
     * @return array of http client options
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
}
