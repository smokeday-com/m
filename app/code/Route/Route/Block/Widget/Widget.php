<?php
/**
 * A Route Magento Extension that adds secure shipping
 * insurance to your orders
 *
 * Php version 7.0^
 *
 * @category  Block
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */

namespace Route\Route\Block\Widget;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;
use Route\Route\Helper\Data;
use Route\Route\Helper\Setup;
use Route\Route\Model\ConfigProvider;
use Route\Route\Model\Route\Client;
use Route\Route\Model\Route\Quote as QuoteClient;

/**
 * Route fee widget to be used on CMS Page and as Short Code
 *
 * Php version 7.0^
 *
 * @category  Block
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Widget extends Template implements BlockInterface
{
    protected $_template = "widget/widget.phtml";
    protected $helper;
    protected $url;
    protected $checkoutSession;
    protected $helperSetup;
    protected $quoteClient;

    /**
     * Widget constructor.
     *
     * @param Template\Context $context Context variable
     * @param Data $helper Route helper
     * @param Session $checkoutSession Current checkout session
     * @param Setup $helperSetup Route setup helper
     * @param array $data Generic data
     */
    public function __construct(
        Template\Context $context,
        Data             $helper,
        Session          $checkoutSession,
        Setup            $helperSetup,
        QuoteClient      $quoteClient,
        array            $data
    ) {
        $this->helper = $helper;
        $this->checkoutSession = $checkoutSession;
        $this->url = $context->getUrlBuilder();
        $this->helperSetup = $helperSetup;
        $this->quoteClient = $quoteClient;
        parent::__construct($context, $data);
    }

    public function getQuote()
    {
        return $this->checkoutSession->getQuote();
    }

    /**
     * Get all module configuration necessary at frontend
     *
     * @return false|string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getConfig()
    {
        $quote = $this->checkoutSession->getQuote();
        $subtotal = $this->helper->getShippableItemsSubtotal($quote);
        $quoteResponse = $this->quoteClient->getQuoteV2($quote);

        $this->helperSetup->setSetupCheckWidget($this->helperSetup::SETUP_CHECK_WIDGET_PHP);
        $config['isEnabled'] = $this->helper->isRouteProtectionAllowed();
        $config['is_route_plus'] = $this->helper->isCovered($quoteResponse);
        $config['is_full_coverage'] = $this->helper->isMerchantFunded($quoteResponse);
        $config['is_carbon_neutral'] = $this->helper->isCarbonNeutral();
        $config['merchant_id'] = $this->helper->getMerchantId();
        $config['store_domain'] = $this->helper->getCurrentStoreDomain();
        $config['public_token'] = $this->helper->getMerchantPublicToken();
        $config['subtotal'] = $subtotal;
        $config['currency'] = $quote->getQuoteCurrencyCode();
        $config['controller_url'] = $this->url->getUrl('route/index/index');
        $config['quote_function_url'] = $this->getQuoteUrl();
        $config['controller_widget'] = $this->url->getUrl('route/index/checkWidget');
        $config['is_checked'] = $this->helper->isInsured($quoteResponse, $quote);
        $config['enable'] = ($this->helper->isCovered($quoteResponse) || $this->helper->isMerchantFunded($quoteResponse)) && $this->helper->isAllowSubtotal($quoteResponse) && $this->helper->hasValidMerchantTokens();
        $config['route_environment'] = getenv(Client::ROUTEAPP_ENVIRONMENT_ENDPOINT) ?? $_SERVER[Client::ROUTEAPP_ENVIRONMENT_ENDPOINT] ?? '';
        return $this->helper->serialize($config);
    }

    /**
     * @return string
     */
    public function getQuoteUrl(): string
    {
        return sprintf(
            ConfigProvider::FUNCTION_QUOTE_URL,
            $this->helper->getMerchantId(),
            $this->getQuote()->getQuoteCurrencyCode()
        );
    }
}
