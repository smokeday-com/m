<?php
/**
 * A Route Magento Extension that adds secure shipping
 * insurance to your orders
 *
 * Php version 7.0^
 *
 * @category
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */

namespace Route\Route\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Route\Route\Helper\Data;
use Route\Route\Model\ResourceModel\Fallback\CollectionFactory;
use Route\Route\Model\Route\Quote as QuoteClient;
use Route\Route\Observer\ResendOrders;

/**
 * Action to get module status and version
 *
 * Php version 7.0^
 *
 * @category
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Status extends Action
{
    private $_helper;
    private $_jsonFactory;
    private $_fallbackCollection;
    private QuoteClient $quoteClient;

    /**
     * Status constructor.
     *
     * @param Context           $context        Current context object
     * @param Data              $helper         Route helper
     * @param JsonFactory       $jsonFactory    Json parser
     * @param CollectionFactory $fallback       Current Session
     */
    public function __construct(
        Context $context,
        Data $helper,
        JsonFactory $jsonFactory,
        CollectionFactory $fallback,
        QuoteClient $quoteClient
    ) {
        $this->_helper = $helper;
        $this->_jsonFactory = $jsonFactory;
        $this->_fallbackCollection = $fallback;
        $this->quoteClient = $quoteClient;
        parent::__construct($context);
    }

    /**
     * Main method to execute Action
     *
     * @return Json
     *
     * @throws \Exception
     */
    public function execute()
    {
        $quoteSession =  $this->_helper->getCurrentQuoteSession();
        $quoteResponse = $this->quoteClient->getQuoteV2($quoteSession);

        $content = [
            'version' => $this->_helper->getVersion(),
            'magento_version' => $this->_helper->getMageVersion(),
            'php_version' => phpversion(),
            'merchant_id' => $this->_helper->getMerchantId(),
            'store_domain' => $this->_helper->getCurrentStoreDomain(),
            'public_token' => $this->encodeToken($this->_helper->getMerchantPublicToken()) ?? false,
            'secret_key' => $this->encodeToken($this->_helper->getMerchantSecretToken()) ?? false,
            'has_route_plus' => $this->_helper->isEnabledDefaultInsurance(),
            'current_cart_is_covered' =>  $this->_helper->isCovered($quoteResponse),
            'is_merchant_funded' =>  $this->_helper->isMerchantFunded($quoteResponse),
            'is_module_output_enabled' => $this->_helper->isModuleOutputEnabled(),
            'is_insured' => $this->_helper->isInsured($quoteResponse, $quoteSession),
            'failed_transactions' => $this->getPendingOperations(),
            'date' => (new \DateTime()),
            'is_taxable' => $this->_helper->isTaxable(),
            'is_carbon_neutral' => $this->_helper->isCarbonNeutral(),
            'include_order_thank_you_page_widget' => $this->_helper->isIncludesOrderThankYouPageWidget(),
            'payment_tax_class' => $this->_helper->getTaxClassId(),
            'order_status' => $this->_helper->getOrderStatus(),
            'order_status_canceled' => $this->_helper->getOrderStatusCanceled(),
            'excluded_shipping_methods' => $this->_helper->getExcludedShippingMethods(),
            'excluded_payment_methods' => $this->_helper->getExcludedPaymentMethods(),
            'is_invalid_merchant' => $this->_helper->isInvalidMerchant(),
            'is_module_enabled' => $this->_helper->isRouteModuleEnable(),
            'last_processed_order' => $this->_helper->getConfigValue(ResendOrders::LAST_PROCESSED_ORDER),
            'resend_shipments_operations_custom' => $this->_helper->getShipmentCronjobCustomPeriod()
        ];

        $resultJson = $this->_jsonFactory->create();

        return $resultJson->setData($content);
    }

    private function encodeToken($token = '')
    {
        $token = $token ?? '';
        return substr($token, 0, 5) . '...' . substr($token, -5);
    }

    private function getPendingOperations()
    {
        $fallback = $this->_fallbackCollection->create();
        return $fallback->getPendingOperations()->count();
    }
}
