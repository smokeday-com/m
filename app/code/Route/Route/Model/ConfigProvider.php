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

namespace Route\Route\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Route\Route\Helper\Data;
use Route\Route\Helper\Setup;
use Route\Route\Model\Route\Client;
use Route\Route\Model\Route\Quote as QuoteClient;
use Route\Route\Service\ApiException;

/**
 * Config provider
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
class ConfigProvider implements ConfigProviderInterface
{
    const LIVE_JS_WIDGET = "https://protection-widget.route.com/route-protection-widget.js";
    const STAGE_JS_WIDGET = "https://stage-protection-widget.route.com/route-protection-widget.js";
    const FUNCTION_QUOTE_URL = "https://protect-quote-q.route.com/v2/quotes/function?merchant_id=%s&currency=%s&namespace=__Route";
    const WIDGET_TEMPLATE = "Route_Route/checkout/cart/route/widget";

    protected $helper;
    protected $checkoutSession;
    protected $url;
    protected $quoteClient;
    protected $helperSetup;

    /**
     * ConfigProvider constructor.
     *
     * @param Data         $helper          Route Helper
     * @param Session      $checkoutSession Current checkout session
     * @param UrlInterface $url             Url constructor
     * @param QuoteClient  $quoteClient     Current Quote
     * @param Setup            $helperSetup     Route setup helper
     */
    public function __construct(
        Data $helper,
        Session $checkoutSession,
        UrlInterface $url,
        QuoteClient $quoteClient,
        Setup $helperSetup
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->helper = $helper;
        $this->url = $url;
        $this->quoteClient = $quoteClient;
        $this->helperSetup = $helperSetup;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws ApiException
     */
    public function getConfig()
    {
        if ($this->helper->isInvalidMerchant()) {
            return [];
        }

        $quote = $this->checkoutSession->getQuote();
        $amountCovered = $this->helper->getShippableItemsSubtotal($quote);
        $quoteResponse = $this->quoteClient->getQuoteV2($quote);
        $custom_env = getenv(Client::ROUTEAPP_ENVIRONMENT_ENDPOINT) ?? $_SERVER[Client::ROUTEAPP_ENVIRONMENT_ENDPOINT] ?? '';

        $config =[
            'route_label' => $this->helper->getRouteLabel(),
            'route_fee_amount' => $quoteResponse->getPremium()->getAmount() ?? 0,
            'subtotal' => $amountCovered,
            'controller_url' => $this->url->getUrl('route/index/index'),
            'controller_widget' => $this->url->getUrl('route/index/checkWidget'),
            'quote_function_url' => $this->getQuoteUrl($quote),
            'is_carbon_neutral' => $this->helper->isCarbonNeutral(),
            'currency' => $quote->getQuoteCurrencyCode(),
            'enable' => ($this->helper->isCovered($quoteResponse) && $this->helper->isAllowSubtotal($quoteResponse) && $this->helper->hasValidMerchantTokens()),
            'is_checked' => $this->helper->isInsured($quoteResponse, $quote),
            'merchant_id' => $this->helper->getMerchantId(),
            'store_domain' => $this->helper->getCurrentStoreDomain(),
            'template' => self::WIDGET_TEMPLATE,
            'route_environment' => $custom_env,
        ];

        $this->helperSetup->setSetupCheckWidget(\Route\Route\Helper\Setup::SETUP_CHECK_WIDGET_PHP);
        return $config;
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface|\Magento\Quote\Model\Quote $quote
     * @return string
     */
    public function getQuoteUrl($quote): string
    {
        return sprintf(
            ConfigProvider::FUNCTION_QUOTE_URL,
            $this->helper->getMerchantId(),
            $quote->getQuoteCurrencyCode()
        );
    }
}
