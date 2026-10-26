<?php
/**
 * A Route Magento Extension that adds secure shipping
 * insurance to your orders
 *
 * Php version 7.0^
 *
 * @category  Helper
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */

namespace Route\Route\Helper;

use Magento\Backend\Model\Session\Quote as adminQuote;
use Magento\Checkout\Model\Session;
use Magento\Config\Model\Config\Source\Enabledisable;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\AbstractHelper as AbstractHelperAlias;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\Manager;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Pricing\Helper\Data as Price;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Route\Route\Logger\Sentry;
use Route\Route\Model\Route\Merchant;
use Route\Route\Model\Route\Quote;

/**
 * Helper to get system config, json serialization and
 * check current session is insured or not
 *
 * Php version 7.0^
 *
 * @category  Helper
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Data extends AbstractHelperAlias
{
    const ROUTE_FEE = 'route_fee';
    const ROUTE_TAX_FEE = 'route_tax_fee';
    const ROUTE_IS_INSURED = 'route_is_insured';
    const CONFIG_XML_PATH = 'route/route/';
    const CONFIG_ENABLED = 'enabled';
    const MERCHANT_PUBLIC_TOKEN = 'merchant_public_token';
    const MERCHANT_SECRET_TOKEN = 'merchant_secret_token';
    const INSURANCE_LABEL = 'insurance_label';
    const INSURANCE_ENABLED_BY_DEFAULT = 'default_setting';
    const IS_TAXABLE = 'is_taxable';

    const CARBON_NEUTRAL = 'carbon_neutral';
    const INCLUDE_ORDER_THANK_YOU_PAGE_WIDGET = 'include_order_thank_you_page_widget';
    const TAX_CLASS = 'payment_tax_class';
    const DEBUG = 'debug';
    const ORDER_STATUS = 'order_status';
    const ORDER_STATUS_CANCELED = 'order_status_canceled';

    const SHOW_ON_CART_PAGE = 'show_on_cart_page';

    const MODULE_NAME = 'Route_Route';

    const BOLT_MODULE_NAME = 'Bolt_Boltpay';

    const TEST_PREFIX = 'test-';
    const ROUTE_LABEL = 'Route Shipping Protection';
    const DEFAULT_MAX_USD_SUBTOTAL_ALLOWED = 5000;
    const DEFAULT_MIN_USD_SUBTOTAL_ALLOWED = 0;
    const ADMIN_AREA_CODE = \Magento\Framework\App\Area::AREA_ADMINHTML;
    const BILLING_INFO_FILLED = 'billing_info';
    const EXCLUDED_SHIPPING_METHODS = 'excluded_shipping_methods';
    const CUSTOM_EXCLUDED_SHIPPING_METHODS = 'custom_excluded_shipping_methods';
    const EXCLUDED_PAYMENT_METHODS = 'excluded_payment_methods';
    const SHIPMENTS_CRONJOB_CUSTOM_PERIOD = 'resend_shipments_operations_custom';
    const LATEST_VERSION_CHECK_DATE = 'latest_version_check_date';
    const LATEST_VERSION_CHECK_VERSION = 'latest_version_check_version';
    const INVALID_MERCHANT = 'invalid_merchant';
    const NO_COVERAGE = 'no_coverage';
    const PAID_BY_MERCHANT = 'paid_by_merchant';
    const PAID_BY_CUSTOMER = 'paid_by_customer';
    const TOGGLE_STATE_CHECKED = 'checked';
    const TOGGLE_STATE_UNCHECKED = 'unchecked';

    protected $checkoutSession;
    protected $productMetadata;
    protected $moduleList;
    protected $jsonFactory;
    protected $quoteClient;
    protected $storeManager;
    protected $price;
    protected $scopeCode;
    protected $configWriter;
    protected $quoteRepository;
    protected $state;
    protected $adminQuoteSession;
    protected $moduleManager;

    private $merchantClient;
    private $collectionFactory;
    private $redirect;
    /**
     * Data constructor.
     *
     * @param Context $context Context variable
     * @param Session $checkoutSession Current checkout session
     * @param ProductMetadataInterface $productMetadata Magento application
     *                                                  product metadata
     * @param ModuleListInterface $moduleList Module list
     * @param Quote $quoteClient Route Quote Client
     * @param StoreManagerInterface $storeManager Store Manager
     * @param Price $price Price
     * @param Merchant $merchantClient Route Merchant client
     * @param CollectionFactory $collectionFactory Route Merchant client
     * @param WriterInterface $configWriter
     * @param Sentry $logger Sentry logger
     * @param CartRepositoryInterface $quoteRepository
     * @param State $state
     * @param adminQuote $adminQuoteSession
     * @param Manager $moduleManager
     */
    public function __construct(
        Context                  $context,
        Session                  $checkoutSession,
        ProductMetadataInterface $productMetadata,
        ModuleListInterface      $moduleList,
        Quote                    $quoteClient,
        StoreManagerInterface    $storeManager,
        Price                    $price,
        Merchant                 $merchantClient,
        CollectionFactory        $collectionFactory,
        WriterInterface          $configWriter,
        Sentry          $logger,
        CartRepositoryInterface  $quoteRepository,
        State                    $state,
        adminQuote               $adminQuoteSession,
        Manager                  $moduleManager,
        RedirectInterface $redirect
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->productMetadata = $productMetadata;
        $this->moduleList = $moduleList;
        $this->quoteClient = $quoteClient;
        $this->storeManager = $storeManager;
        $this->price = $price;
        $this->merchantClient = $merchantClient;
        $this->collectionFactory = $collectionFactory;
        $this->configWriter = $configWriter;
        parent::__construct($context);
        $this->_logger = $logger;
        $this->quoteRepository = $quoteRepository;
        $this->state = $state;
        $this->adminQuoteSession = $adminQuoteSession;
        $this->moduleManager = $moduleManager;
        $this->redirect = $redirect;
    }

    /**
     * Generic method to retrieve Route Settings passing
     * the config name as param
     *
     * @param string $config config name
     * @param bool $escapeCache scape cache
     *
     * @return mixed
     */
    public function getConfigValue($config, $escapeCache = false)
    {
        if ($escapeCache) {
            return $this->getConfigValueByPassCache(
                self::CONFIG_XML_PATH . $config,
                ScopeInterface::SCOPE_STORES,
                $this->scopeCode
            );
        }

        return $this->scopeConfig->getValue(
            self::CONFIG_XML_PATH . $config,
            ScopeInterface::SCOPE_STORES,
            $this->scopeCode
        );
    }

    /**
     * Save config
     *
     * @param $config
     * @param $value
     * @param $scope
     * @param int $scopeId
     */
    public function saveConfig($config, $value, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0)
    {
        $this->configWriter->save(self::CONFIG_XML_PATH . $config, $value, $scope, $scopeId);
    }

    /**
     * Generic method to retrieve Route Settings passing
     * the config name as param by passing cache
     * @param $config
     * @param $scope
     * @param $scopeId
     * @return mixed
     */
    public function getConfigValueByPassCache($config, $scope, $scopeId)
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('path', $config);
        foreach ($collection->getItems() as $config) {
            return $config->getValue();
        }
    }

    /**
     * Change default scope
     * @param $scopeCode
     */
    public function setScopeCode($scopeCode)
    {
        if (isset($scopeCode) && $scopeCode >= 0) {
            $this->scopeCode = $scopeCode;
        }
    }

    /**
     * Check if it has test token
     *
     * @return bool
     */
    public function hasTestSecretToken()
    {
        return substr($this->getMerchantSecretToken(), 0, strlen(self::TEST_PREFIX)) === self::TEST_PREFIX;
    }

    /**
     * Get current module version
     *
     * @return mixed
     */
    public function getVersion()
    {
        return $this->moduleList
            ->getOne(self::MODULE_NAME)['setup_version'];
    }

    /**
     * It returns merchant public token as string
     *
     * @return string
     */
    public function getMerchantPublicToken(): string|null
    {
        return $this->getConfigValue(self::MERCHANT_PUBLIC_TOKEN);
    }

    /**
     * It returns merchant secret token as string
     *
     * @return string
     */
    public function getMerchantSecretToken(): string|null
    {
        return $this->getConfigValue(self::MERCHANT_SECRET_TOKEN);
    }

    /**
     * It returns Route Fee label
     *
     * @return string
     */
    public function getRouteLabel(): string
    {
        return self::ROUTE_LABEL;
    }
    public function isCartPage(): bool
    {
        return $this->_request->getFullActionName() === 'checkout_cart_index';
    }

    /**
     * Check if widget and fee can be added in the cart page
     *
     * @return bool
     */
    public function canShowOnCartPage(): bool
    {
        return intval($this->getConfigValue(self::SHOW_ON_CART_PAGE)) == 1;
    }

    /**
     * Get order status to submit
     *
     * @return string|array
     */
    public function getOrderStatus(): mixed
    {
        return empty($this->getConfigValue(self::ORDER_STATUS)) ?
            $this->getConfigValue(self::ORDER_STATUS) :
            explode(',', $this->getConfigValue(self::ORDER_STATUS));
    }

    /**
     * Get order canceled status to submit
     *
     * @return string[]
     */
    public function getOrderStatusCanceled(): string|array
    {
        return empty($this->getConfigValue(self::ORDER_STATUS_CANCELED)) ?
            $this->getConfigValue(self::ORDER_STATUS_CANCELED) :
            explode(',', $this->getConfigValue(self::ORDER_STATUS_CANCELED));
    }

    /**
     * Check if it can be submitted
     *
     * @param OrderInterface $order
     *
     * @return string
     */
    public function canSubmitOrder($order): bool|string
    {
        if (!$this->isRouteModuleEnable()) {
            return false;
        }

        return empty($this->getOrderStatus()) ||
            is_null($this->getConfigValue(self::ORDER_STATUS)) ||
            in_array($order->getStatus(), $this->getOrderStatus());
    }

    /**
     * Check if it should add Route Fee to summaries
     *
     * @param null $quoteResponse
     * @return bool
     */
    public function canAddRouteFee($quoteResponse = null, $quote = null): bool
    {
        if (!$this->isRouteProtectionAllowed(false, false, $quote))
            return false;

        if($this->isCartPage() && !$this->canShowOnCartPage()){
            return false;
        }

        return $this->isInsured($quoteResponse, $quote) && !in_array($quoteResponse->getPaymentResponsible()->getType(), [self::NO_COVERAGE, self::PAID_BY_MERCHANT]);
    }

    /**
     * It returns if the current session is insured
     */
    public function isInsured($quoteResponse, $checkout): bool
    {
        if($this->isAdmin()){
            $checkout = $this->adminQuoteSession;
        }

        if (!$this->isRouteModuleEnable() || !$this->isAllowSubtotal($quoteResponse)) {
            return false;
        }

        if ($this->isMerchantFunded($quoteResponse)) {
            return true;
        }

        if(!is_null($checkout->getRouteIsInsured())){
            return $checkout->getRouteIsInsured();
        }

        return $quoteResponse->getPaymentResponsible()->getType() == self::PAID_BY_CUSTOMER &&
            $quoteResponse->getPaymentResponsible()->getToggleState() == self::TOGGLE_STATE_CHECKED;
    }

    /**
     * Check if merchant is Route Plus
     *
     * @return bool Enabled or not
     */
    public function isMerchantFunded($quoteResponse): bool
    {
        return $this->isRouteModuleEnable() && $quoteResponse->getPaymentResponsible()->getType() == self::PAID_BY_MERCHANT;
    }

    /**
     * Check if merchant is Route Plus
     */
    public function isCovered($quoteResponse): bool
    {
        return $this->isRouteModuleEnable() && $quoteResponse->getPaymentResponsible()->getType() != self::NO_COVERAGE;
    }

    /**
     * It serializes param $data as json
     *
     * @param array $data array an array param is expected
     *
     * @return string
     */
    public function serialize($data): string
    {
        return json_encode($data);
    }

    /**
     * It decodes param $json to array assoc
     *
     * @param string $json an string json is expected
     *
     * @return mixed
     */
    public function unserialize($json): mixed
    {
        return json_decode($json, true);
    }

    /**
     * It returns the current Magento Version
     *
     * @return string
     */
    public function getMageVersion(): string
    {
        return $this->productMetadata->getVersion();
    }

    /**
     * It adds a comment to the history of the order passed by param
     * before it verifies the Magento version
     *
     * @param Order $order Order to add the comment
     * @param string $comment the comment
     * @param bool $save save the comment
     *
     * @return void
     */
    public function addCommentToOrderHistory($order, $comment, $save = false)
    {
        $this->_logger->debug($comment);
    }

    /**
     * It checks if the configuration Default setting is set to Opt-in or Opt-out
     *
     * @return bool
     */
    public function isEnabledDefaultInsurance()
    {
        if (!$this->isRouteModuleEnable()) {
            return false;
        }

        return (bool)!$this->merchantClient->isOptIn();
    }

    /**
     * Get Route Line
     *
     * @param string $format Desired format default php array
     *
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getRouteLine($format = "")
    {
        $line = [
            'label' => $this->getRouteLabel(),
            'amount' => $this->_getCurrentQuoteAmount(),
            'currency' => $this->storeManager->getStore()->getCurrentCurrencyCode(),
            'currency_symbol' => $this->storeManager->getStore()->getBaseCurrency()->getCurrencySymbol()
        ];

        if ($format == 'json') {
            return $this->serialize(['route' => $line]);
        }

        if ($format == 'html') {
            return $this->_htmlFormatLine($line);
        }

        return $line;
    }

    /**
     * Get Route line as html
     *
     * @param $line
     *
     * @return string
     */
    private function _htmlFormatLine($line)
    {
        $amountFormatted = $this->price->currency($line['amount'], true, false);
        return "<div id=\"route\">" .
            "<span class=\"label\">{$line['label']}</span>" .
            "<span class=\"amount\">{$amountFormatted}</span>" .
            "</div>";
    }

    /**
     * Get Current Quote
     *
     * @return float
     */
    private function _getCurrentQuoteAmount()
    {
        $currentSession = $this->isAdmin() ? $this->adminQuoteSession : $this->checkoutSession;
        try {
            $quoteResponse = $this->quoteClient->getQuoteV2($currentSession->getQuote());
            if ($quoteResponse->getPaymentResponsible()->getType() != self::NO_COVERAGE) {
                return $quoteResponse->getPremium()->getAmount();
            }
        } catch (\Exception) {

        }
        return 0;
    }

    /**
     * Get Current Quote
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function _getQuoteCurrency()
    {
        $quote = $this->isAdmin() ? $this->adminQuoteSession->getQuote() : $this->checkoutSession->getQuote();
        return $quote->getQuoteCurrencyCode();
    }

    /**
     * Check if the Route Fee is Taxable
     *
     * @return bool
     */
    public function isTaxable()
    {
        return (bool)$this->getConfigValue(self::IS_TAXABLE);
    }

    /**
     * Check if shipping is carbon neutral
     *
     * @return bool
     */
    public function isCarbonNeutral(): bool
    {
        return (bool)$this->getConfigValue(self::CARBON_NEUTRAL);
    }


    /**
     * Check if the Route order thank you page is enabled
     *
     * @return bool
     */
    public function isIncludesOrderThankYouPageWidget()
    {
        return (bool)$this->getConfigValue(self::INCLUDE_ORDER_THANK_YOU_PAGE_WIDGET);
    }

    /**
     * Returns the tax class id from Route Fee
     *
     * @return int
     */
    public function getTaxClassId()
    {
        return $this->getConfigValue(self::TAX_CLASS);
    }

    /**
     * Returns if billing info was checked
     *
     * @return bool
     */
    public function isBillingChecked()
    {
        return !is_null($this->isBillingFilled());
    }

    /**
     * Returns shipment cronjob custom period
     *
     * @return int
     */
    public function getShipmentCronjobCustomPeriod()
    {
        return $this->getConfigValue(self::SHIPMENTS_CRONJOB_CUSTOM_PERIOD);
    }

    /**
     * Returns last time we perform a version check on admin page
     *
     * @return int
     */
    public function getLatestVersionCheckDate()
    {
        return $this->getConfigValue(self::LATEST_VERSION_CHECK_DATE);
    }

    /**
     * Returns version check on admin page
     *
     * @return int
     */
    public function getLatestVersionCheckVersion()
    {
        return $this->getConfigValue(self::LATEST_VERSION_CHECK_VERSION);
    }

    /**
     * Set billing info as checked or not
     *
     * @param $isFilled
     *
     * @return void
     */
    public function setBillingFilled($isFilled)
    {
        $this->saveConfig(self::BILLING_INFO_FILLED, intval($isFilled));
    }

    /**
     * Check flag if billing is filled
     *
     * @return mixed
     */
    public function isBillingFilled()
    {
        # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
        # "Eliminate 5.18s admin notification overhead in Mirasvit_Core, Route_Route, and Klevu_Search MessageList checks and remove render-blocking Google Fonts on Dashboard":
        # https://github.com/smokeday-com/m/issues/16
        return $this->getConfigValue(self::BILLING_INFO_FILLED, false);
    }

    /**
     * Get current store Domain
     *
     * @return string
     */
    public function getCurrentStoreDomain()
    {
        try {
            return parse_url($this->getStoreUrl(), PHP_URL_HOST);
        } catch (\Exception $exception) {
            $this->_logger->error('Couldn\'t get store domain');
            return "";
        }
    }

    /**
     * Returns store URL
     *
     * @return string
     *
     * @throws NoSuchEntityException
     */
    public function getStoreUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl();
    }

    /**
     * Check if Route debug is enable
     *
     * @return bool
     */
    public function isDebugEnable()
    {
        return false;
        //return (bool)$this->getConfigValue(self::DEBUG);
    }

    /**
     * Check cart subtotal, hide if subtotal above allowed
     * @return bool
     */
    public function isAllowSubtotal($quoteResponse)
    {
        return $this->isRouteModuleEnable() && $quoteResponse->getPaymentResponsible()->getType() != self::NO_COVERAGE;
    }

    public function getCurrentQuoteSession(): bool|CartInterface|\Magento\Quote\Model\Quote
    {
        $quoteSession = $this->isAdmin() ? $this->adminQuoteSession : $this->checkoutSession;
        $quoteId = $quoteSession->getQuoteId();

        if ($quoteId) {
            return $this->quoteRepository->get($quoteId);
        }

        try {
            return $quoteSession->getQuote();
        } catch (\Exception $exception) {
            return false;
        }
    }

    public function isAdmin(): bool
    {
        return $this->state->getAreaCode() === self::ADMIN_AREA_CODE;
    }

    public function isWebapi(): bool
    {
        return $this->state->getAreaCode() === Area::AREA_WEBAPI_REST;
    }

    /**
     *  Get Quote/Order subtotal for shippable items only
     * getShippableItemsSubtotal
     * @param $obj Quote | OrderInterface
     * @param $total
     * @return string
     */
    public function getShippableItemsSubtotal($obj, $total = null): string
    {
        $subtotal = ($total > 0 ? $total : null)
            ?? ($obj->getSubtotal() > 0 ? $obj->getSubtotal() : null)
            ?? ($obj->getShippingAddress()->getSubtotal() > 0 ? $obj->getShippingAddress()->getSubtotal() : null)
            ?? 0;

        $items = $obj->getAllItems();

        foreach ($items as $item) {
            $product = $item->getProduct();
            if (is_null($product) || !method_exists($product, 'getIsVirtual') || !method_exists($product, 'getTypeId')) {
                continue;
            }
            if ($product->getIsVirtual() || $product->getTypeId() == \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE) {
                $qty = $item->getQty() ? intval($item->getQty()) : intval($item->getQtyOrdered());
                $productPrice = $item->getPrice() * $qty;
                $subtotal = $subtotal - $productPrice;
            }
        }

        return number_format($subtotal < 0 ? 0 : $subtotal, 2, '.','');
    }

    /**
     * Check if the shipping method allows Route to appear
     *
     * @param mixed $shippingMethod
     * @param mixed $paymentMethod
     * @return bool
     */
    public function isRouteProtectionAllowed($shippingMethod = false, $paymentMethod = false, $quote = null): bool
    {
        if (!$this->isRouteModuleEnable()) {
            return false;
        }

        $quote = $quote ?? $this->getCurrentQuoteSession();

        if (!$quote) {
            return false;
        }

        if (!$shippingMethod) {
            $shippingMethod = $quote->getShippingAddress()->getShippingMethod() ?? false;
        }

        if (!$paymentMethod) {
            $paymentMethod = $quote->getPayment()->getMethod() ?? false;
        }

        $excludedShippingMethods = $this->getExcludedShippingMethods();
        $excludedPaymentMethods = $this->getExcludedPaymentMethods();

        return
            !in_array($shippingMethod, $excludedShippingMethods, true) &&
            !in_array($paymentMethod, $excludedPaymentMethods, true);
    }

    /**
     * Get excluded shipping methods
     *
     * @return array
     */
    public function getExcludedShippingMethods()
    {

        $excluded = $this->getConfigValue(self::EXCLUDED_SHIPPING_METHODS);
        $excluded = empty($excluded) ? [] : explode(',', $excluded);
        return array_merge($excluded, [$this->getCustomExcludedShippingMethods()]);
    }

    public function getCustomExcludedShippingMethods()
    {
        return $this->getConfigValue(self::CUSTOM_EXCLUDED_SHIPPING_METHODS) ?? '';
    }
    /**
     * Get excluded payment methods
     *
     * @return array
     */
    public function getExcludedPaymentMethods()
    {
        return empty($this->getConfigValue(self::EXCLUDED_PAYMENT_METHODS)) ?
            [] :
            explode(',', $this->getConfigValue(self::EXCLUDED_PAYMENT_METHODS));
    }

    /**
     * Check if it's has valid Merchants
     *
     * @return bool
     */
    public function hasValidMerchantTokens()
    {
        return $this->hasMerchantTokens() && !empty($this->merchantClient->getMerchant());
    }

    /**
     * Check if it's has Merchant Tokens
     *
     * @return bool
     */
    private function hasMerchantTokens()
    {
        return !empty($this->getMerchantPublicToken()) && !empty($this->getMerchantSecretToken());
    }

    /**
     * Check if it's has Merchant Tokens
     *
     * @return bool
     */
    public function getMerchantId()
    {
        return $this->merchantClient->getMerchantId();
    }

    /**
     * Set current configuration as invalid merchant
     */
    public function setMerchantAsInvalid()
    {
        $this->saveConfig(self::INVALID_MERCHANT, true);
    }

    /**
     * Remove merchant invalid flag
     */
    public function removeMerchantInvalidFlag()
    {
        $this->saveConfig(self::INVALID_MERCHANT, false);
    }

    /**
     * Check if it's a invalid merchant
     * @return bool
     */
    public function isInvalidMerchant()
    {
        # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
        # "Eliminate 5.18s admin notification overhead in Mirasvit_Core, Route_Route, and Klevu_Search MessageList checks and remove render-blocking Google Fonts on Dashboard":
        # https://github.com/smokeday-com/m/issues/16
        $invalidMerchantConfig = $this->getConfigValue(self::INVALID_MERCHANT, false);
        return !empty($invalidMerchantConfig) || $invalidMerchantConfig;
    }

    /**
     * Check if Route Extension is Enabled
     * @return bool
     */
    public function isRouteModuleEnable()
    {
        $isRouteModuleEnable = $this->getConfigValue(self::CONFIG_ENABLED);
        return $isRouteModuleEnable == Enabledisable::ENABLE_VALUE;
    }

    private function isBoltModuleEnabled()
    {
        return $this->moduleManager->isEnabled(self::BOLT_MODULE_NAME);
    }

    /**
     * Check if module is enabled
     * @param $moduleName
     * @return bool
     */
    public function isModuleEnabled($moduleName)
    {
        return $this->moduleManager->isEnabled($moduleName);
    }
}
