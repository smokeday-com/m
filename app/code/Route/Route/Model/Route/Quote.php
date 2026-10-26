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
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\HTTP\ZendClientFactory;
use Route\Route\Logger\Sentry;
use Route\Route\Helper\Data;
use Route\Route\Helper\Setup;
use Route\Route\Model\Route\Operation\FallbackFactory;
use Route\Route\Service\Api\QuoteApi;
use Route\Route\Service\ApiClientBuilder;
use Route\Route\Service\ApiException;
use Route\Route\Service\Model\QuoteInlineResponse200;
use Route\Route\Service\Model\QuoteInlineResponse2001;
use Route\Route\Service\Model\QuoteInlineResponse2002;
use Route\Route\Service\Model\QuoteInput;
use Route\Route\Service\Model\QuoteOutput;

/**
 * Quote API Client
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
class Quote extends Client
{
    const API_ENDPOINT = 'quote';
    const MINIMUM_AMOUNT_INSURED = 98;
    const FALLBACK_PERCENTAGE = .01;
    const ROUTE_QUOTE = 'route_quote';
    const LIFETIME = 30;
    const INVALID = 'invalid';
    private QuoteApi $quoteApiInstance;

    protected $helper;

    public function __construct(ZendClientFactory $httpClientFactory, Data $helper, Sentry $logger, FallbackFactory $fallback, Setup $helperSetup, CacheInterface $cache, ProductMetadataInterface $productMetadata, ApiClientBuilder $apiClientBuilder)
    {

        $this->quoteApiInstance = $apiClientBuilder->buildQuoteApiClient();
        $this->helper = $helper;
        parent::__construct($httpClientFactory, $helper, $logger, $fallback, $helperSetup, $cache, $productMetadata);
    }

    /**
     * @param $quote
     * @return QuoteInlineResponse200 | QuoteInlineResponse2001 | QuoteInlineResponse2002
     * @throws ApiException
     */
    public function getQuoteV2($quote) : QuoteOutput
    {
        $payload = [
            'merchant_id' => $this->helper->getMerchantId(),
            'cart' => [
                'covered' => [
                    'currency' => $quote->getQuoteCurrencyCode() ?? 'USD',
                    'amount' => $this->helper->getShippableItemsSubtotal($quote)
                ],
                'cart_items' => []
            ]
        ];

        if ($quote->getId()) {
            $payload['cart']['cart_ref'] = $quote->getId();
        }

        return $this->quoteApiInstance->createQuoteAsync(new QuoteInput(
            $payload
        ), new \DateTime())->wait();
    }
    /**
     * Main method that get quote api response or execute fallback method
     *
     * @param float  $subtotal  quote subtotal
     * @param float  $amountCovered  amount covered
     * @param bool   $isInsured if is insured or not
     * @param string $currency  currency code
     *
     * @return mixed
     */
    public function getQuote($subtotal, $amountCovered, $isInsured, $currency)
    {
        $cachedQuote = $this->cache->load(self::ROUTE_QUOTE);

        if (!empty($cachedQuote)) {
            /** avoid repetitive invalid requests */
            if ($cachedQuote == self::INVALID) {
                return false;
            }
            $cachedQuote = $this->helper->unserialize($cachedQuote);
            if ($this->isValidCache($amountCovered, $currency, $cachedQuote)) {
                return $cachedQuote;
            }
        }

        try {

            $quoteResponse = $this
                ->usePublicToken()
                ->getMethod(
                    $this->getRouteApiUrl(self::API_ENDPOINT),
                    [
                    'subtotal' => $amountCovered,
                    'taxes' => 0,
                    'currency' => $currency,
                    'selected_insurance' => $isInsured
                    ]
                )->execute();

            if ($quoteResponse) {
                $this->cache->save($this->helper->serialize($quoteResponse), self::ROUTE_QUOTE, [], self::LIFETIME);
            }

            return $quoteResponse;

        } catch (\Exception $exception) {

            $this->cache->save(self::INVALID, self::ROUTE_QUOTE, [], self::LIFETIME);

            $this->logger->error(
                "Error trying to fee estimation:" . $exception->getMessage(),
                $exception->getTrace(),
                [
                    'params' => $this->getData(),
                    'method' => $this->getCurrentMethod(),
                    'endpoint' => $this->getUrl(),
                ]
            );
            return $this->_getFallbackProtectionAmount($amountCovered);
        }
    }

    /**
     * Fallback function to calculate fee in case of exceptions
     *
     * @param float $subtotal quote subtotal
     *
     * @return array
     */
    private function _getFallbackProtectionAmount($subtotal)
    {
        return [
            'insurance_price' => (
                $subtotal > self::MINIMUM_AMOUNT_INSURED ?
                    $subtotal :
                    self::MINIMUM_AMOUNT_INSURED
            ) * self::FALLBACK_PERCENTAGE
        ];
    }

    /**
     * @param $subtotal
     * @param $currency
     * @param $cachedQuote
     * @return bool
     */
    protected function isValidCache($subtotal, $currency, $cachedQuote)
    {
        return $cachedQuote['currency'] == $currency &&
            $cachedQuote['subtotal_usd'] == $subtotal;
    }
}
