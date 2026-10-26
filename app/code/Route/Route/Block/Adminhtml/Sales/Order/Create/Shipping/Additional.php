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

namespace Route\Route\Block\Adminhtml\Sales\Order\Create\Shipping;

use Magento\Framework\View\Element\Template;
use Magento\Backend\Block\Template\Context;
use Route\Route\Helper\Data;
use Magento\Backend\Model\Session\Quote;
use Route\Route\Model\ConfigProvider;
use Route\Route\Model\Route\Client;
use Route\Route\Model\Route\Quote as QuoteClient;
use Route\Route\Service\ApiException;

/**
 * Route widget to be used on admin order creations
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
class Additional extends Template
{
    protected $helper;
    protected $checkoutSession;
    protected $quoteClient;
    protected $url;
    protected $formKey;
    /**
     * Constructor
     *
     * @param Context  $context  Context variable
     * @param Data     $helper   Route helper
     * @param Quote    $quote    Admin quote session
     * @param array    $data     Generic data
     */
    public function __construct(
        Context $context,
        Data $helper,
        Quote $quote,
        QuoteClient $quoteClient,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->formKey = $context->getFormKey();
        $this->helper = $helper;
        $this->quoteClient = $quoteClient;
        $this->checkoutSession = $quote;
        $this->url = $context->getUrlBuilder();
        $quoteSession =  $this->helper->getCurrentQuoteSession();
        $this->quoteResponse = $this->quoteClient->getQuoteV2($quoteSession);
    }

    /**
     * Method to check if the current session is insured
     *
     * @return bool
     */
    public function isInsured()
    {
        return $this->checkoutSession->getRouteIsInsured();
    }

    /**
     * Get current quote subtotal
     *
     * @return float
     */
    public function getSubtotalAmount()
    {
        return $this->helper->getShippableItemsSubtotal($this->checkoutSession->getQuote());
    }

    /**
     * Get controller insurance url
     *
     * @return string
     */
    public function getControllerUrl()
    {
        return $this->url->getUrl('route/index/insured');
    }

    /**
     * Is route insurance route module enable
     *
     * @return bool
     */
    public function isRoutePlus()
    {
        return $this->helper->isCovered($this->quoteResponse) && $this->helper->isAllowSubtotal($this->quoteResponse);
    }

    /**
     * Check if merchant is Route Plus
     *
     * @return bool
     */
    public function isFullCoverage()
    {
        return $this->helper->isMerchantFunded($this->quoteResponse) && $this->helper->isAllowSubtotal($this->quoteResponse);
    }

    /**
     * Check if the insurance is checked by default
     *
     * @return bool
     */
    private function _isEnabledDefaultInsurance()
    {
        if (is_null($this->checkoutSession->getRouteIsInsured())) {
            return $this->helper->isEnabledDefaultInsurance();
        }
        return $this->checkoutSession->getRouteIsInsured();
    }

    /**
     * Returns json with default setting info
     *
     * @return false|string
     */
    public function isEnabledDefaultInsuranceEncoded()
    {
        return json_encode((bool) $this->_isEnabledDefaultInsurance());
    }

    public function getQuote()
    {
        return $this->checkoutSession->getQuote();
    }

    /**
     * Get all module configuration necessary at frontend
     *
     * @return false|string
     */
    public function getConfig()
    {

        $config['store_domain'] = $this->helper->getCurrentStoreDomain();
        $config['merchant_id'] = $this->helper->getMerchantId();
        $config['subtotal'] = $this->getSubtotalAmount();
        $config['controller_url'] = $this->getControllerUrl();
        $config['quote_function_url'] = $this->getQuoteUrl();
        $config['controller_widget'] = $this->url->getUrl('route/index/checkWidget');
        $config['is_checked'] = $this->helper->isInsured($this->quoteResponse, $this->getQuote());
        $config['is_carbon_neutral'] = $this->helper->isCarbonNeutral();
        $config['route_live_widget'] = $this->helper->isCovered($this->quoteResponse) && !$this->helper->isMerchantFunded($this->quoteResponse) ? ConfigProvider::LIVE_JS_WIDGET : ($this->helper->isMerchantFunded($this->quoteResponse) ? ConfigProvider::LIVE_JS_WIDGET_FULL_COVERAGE : '');
        $config['enable'] = ($this->helper->isCovered($this->quoteResponse) || $this->helper->isMerchantFunded($this->quoteResponse)) && $this->helper->isAllowSubtotal($this->quoteResponse);
        $config['form_key'] = $this->formKey->getFormKey();
        $config['currency'] = $this->getQuote()->getQuoteCurrencyCode();
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
