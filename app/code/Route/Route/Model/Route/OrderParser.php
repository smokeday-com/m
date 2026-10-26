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

use Exception;
use Magento\Catalog\Helper\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Downloadable\Model\Product\Type;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Item;
use Magento\Store\Model\ScopeInterface;
use Route\Route\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Directory\Model\ResourceModel\Region\Collection as RegionCollection;
use Route\Route\Service\Model\V2OrdersBody;

/**
 * Order Parser to object from Route API client
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
class OrderParser
{
    private Product $_productHelper;
    private Data $helper;
    private ScopeConfigInterface $_scopeConfig;
    private RegionCollection $_regionCollection;

    /**
     * OrderParser constructor.
     *
     * @param Product $productHelper product helper
     * @param Data $helper Route helper
     * @param ScopeConfigInterface $scopeConfig Scope Config Interface
     * @poram RegionCollection $regionCollection
     */
    public function __construct(
        Product $productHelper,
        Data $helper,
        ScopeConfigInterface $scopeConfig,
        RegionCollection $regionCollection
    ) {
        $this->_productHelper = $productHelper;
        $this->helper = $helper;
        $this->_scopeConfig = $scopeConfig;
        $this->_regionCollection = $regionCollection;
    }

    /**
     * Parse Magento order object to Route format
     *
     * @param OrderInterface $order
     * @param bool $insuranceSelected if insurance is selected
     *
     * @return V2OrdersBody
     */
    public function getRouteObject(OrderInterface $order, bool $insuranceSelected): V2OrdersBody
    {
        $address = $order->getShippingAddress() ?
            $order->getShippingAddress() :
            $order->getBillingAddress();
        $billingAddress = $order->getBillingAddress();

        $data = [
            "source_order_id" => $order->getIncrementId(),
            "source_order_number"  => $order->getIncrementId(),
            "source_created_on" => $this->getOrderCreatedAt($order),
            "source_updated_on" => $this->getOrderUpdatedAt($order),
            "subtotal" => $order->getSubtotal(),
            "amount_covered" => $this->helper->getShippableItemsSubtotal($order),
            "currency" => $order->getOrderCurrencyCode(),
            "taxes" => $order->getTaxAmount(),
            "insurance_selected" => !!$insuranceSelected,
            "shipping_total" => $order->getShippingAmount(),
            "discounts_total" => $order->getDiscountAmount(),
            "order_total" => $order->getGrandTotal(),
            "cart_ref" => $order->getQuoteId() ?? '-',
            "quote_id" => $order->getQuoteId() ?? '-',
            "customer_details" => [
                "first_name" => $this->getFirstName($order),
                "last_name" => $this->getLastName($order),
                "email" => $order->getCustomerEmail()
            ],
            "shipping_details" => [
                "first_name" => $address->getFirstname(),
                "last_name" => $address->getLastname(),
                "street_address1" => $address->getStreetLine(1),
                "street_address2" => $address->getStreetLine(2),
                "city" => $address->getCity(),
                "province" => $address->getRegion(),
                "zip" => $address->getPostcode(),
                "country_code" => $address->getCountryId()
            ],
            "billing_details" => [
                "first_name" => $billingAddress->getFirstname(),
                "last_name" => $billingAddress->getLastname(),
                "company"   => $billingAddress->getCompany(),
                "street_address1" => $billingAddress->getStreetLine(1),
                "street_address2" => $billingAddress->getStreetLine(2),
                "city" => $billingAddress->getCity(),
                "province" => $billingAddress->getRegion(),
                "zip" => $billingAddress->getPostcode(),
                "country_code" => $billingAddress->getCountryId()
            ],
            "line_items" => $this->_getLineItems($order, $insuranceSelected)
        ];

        return new V2OrdersBody($data);
    }

    /**
     * Get price of given line of item
     *
     * @param Item $item Magento Product Object
     *
     * @return float
     */

    private function _getLineItemsPrice(Item $item) : float
    {
        try {
            if ($item->getProduct()->getTypeId() == \Magento\Bundle\Model\Product\Type::TYPE_CODE) {
                return $item->getPrice();
            }

            if ($this->isBundleItemProduct($item)) {
                return 0;
            }

            return ($item->getParentItem() ? $item->getParentItem()->getPrice() : $item->getPrice());
        } catch (Exception) {
            return $item->getPrice();
        }
    }

    /**
     * Get line of items
     *
     * @param $order
     * @param $insuranceSelected
     *
     * @return array
     */
    private function _getLineItems($order, $insuranceSelected): array
    {
        $lineItems = [];
        $weightUnit = $this->getWeightUnit();
        $originLocation = $this->_getOriginLocation();

        foreach ($order->getAllItems() as $item) {
            if ($this->isNotASimpleProduct($item)) {
                continue;
            }

            $lineItems[] = [
                "source_id" => strval($item->getItemId()),
                "delivery_method" => $this->getDeliveryMethod($order, $this->isVirtualProduct($item)),
                "is_insured" => $this->canProductBeProtected($order, $insuranceSelected, $this->isVirtualProduct($item), $this->isBundleItemProduct($item)),
                "source_product_id" => $item->getProduct()->getId(),
                "sku" => $item->getProduct()->getSku(),
                "name" => $item->getName(),
                "price" => $this->_getLineItemsPrice($item),
                "quantity" => intval($item->getQtyOrdered()),
                "upc" => "",
                "image_url" => $this->_getProductImage($item),
                'weight' => [
                    'value' => floatval($item->getWeight()),
                    'unit' => $weightUnit,
                ],
                'origin_location' => $originLocation,
                'shipping_method' => $this->getShippingMethod($order),
                'vendor' => 'magento',
                'fulfillment_status' => $this->getFulfillmentStatus($item)
            ];

        }

        return $lineItems;
    }

    /**
     * Get config value from Store Scope
     *
     * @param string $key
     *
     * @return mixed
     */
    private function _getScopeValue(string $key): mixed
    {
        return $this->_scopeConfig->getValue(
            $key,
            ScopeInterface::SCOPE_STORE
        );
    }


    /**
     * Get Origin Location
     *
     * @return array
     */
    private function _getOriginLocation(): array
    {

        $address1 = $this->_getScopeValue("shipping/origin/street_line1");
        $address2 = $this->_getScopeValue("shipping/origin/street_line2");
        $city = $this->_getScopeValue("shipping/origin/city");
        $region = $this->_getScopeValue("shipping/origin/region_id");
        $zip = $this->_getScopeValue("shipping/origin/postcode");
        $country = $this->_getScopeValue("shipping/origin/country_id");

        if ($region) {
            $regionItem = $this->_regionCollection->getItemById($region);
            $region = $regionItem->getCode();
        }
        return [
            'address1' => $address1,
            'address2' => $address2,
            'city' => $city,
            'province_code' => $region,
            'zip' => $zip,
            'country_code' => $country
        ];
    }

    /**
     * Get Product Image url
     *
     * @param Item $item item from order
     *
     * @return string
     */
    private function _getProductImage(Item $item): string
    {
        try {
            return $this->_productHelper->getImageUrl($item->getProduct());
        } catch (Exception) {
            return "";
        }
    }

    /**
     * @param $order
     * @return string
     */
    private function getFirstName($order): string
    {
        if ($order->getCustomerFirstname())
            return $order->getCustomerFirstname() . '';
        if ( $order->getBillingAddress()->getFirstName())
            return  $order->getBillingAddress()->getFirstName() . '';
        if( $order->getShippingAddress()->getFirstName())
            return  $order->getShippingAddress()->getFirstName() . '';
        return '';
    }

    /**
     * @param $order
     * @return string
     */
    private function getLastName($order): string
    {
        if ($order->getCustomerLastname())
            return $order->getCustomerLastname() . '';
        if ( $order->getBillingAddress()->getLastname())
            return  $order->getBillingAddress()->getLastname() . '';
        if( $order->getShippingAddress()->getLastname())
            return  $order->getShippingAddress()->getLastname() . '';
        return '';
    }

    /**
     * Get Order created at formatted as Route expect it
     *
     * @param $order
     * @return bool|string
     */
    private function getOrderCreatedAt($order): bool|string
    {
        return $this->formatDateToRoute($order->getCreatedAt());
    }

    /**
     * Get Order updated at formatted as Route expect it
     *
     * @param $order
     * @return bool|string
     */
    private function getOrderUpdatedAt($order): bool|string
    {
        return $this->formatDateToRoute($order->getUpdatedAt());
    }

    /**
     * Format string date as Route expect it
     *
     * @param string $date
     *
     * @return false|string
     */
    private function formatDateToRoute(string $date): bool|string
    {
        return date(DATE_ATOM, empty($date) ? time() : strtotime($date));
    }

    /**
     * @param $order
     * @return bool
     */
    private function isShippingMethodAllowed($order): bool
    {
        return $this->helper->isRouteProtectionAllowed($order->getShippingMethod(), $order->getPayment()->getMethod());
    }

    /**
     * @param $order
     * @param bool $isProductVirtual
     * @return string
     */
    private function getDeliveryMethod($order, bool $isProductVirtual): string
    {
        return $isProductVirtual ? 'digital' : ($this->isShippingMethodAllowed($order) ? 'ship_to_home' : 'ship_to_store');
    }

    /**
     * @param $order
     * @param $insuranceSelected
     * @param bool $isProductVirtual
     * @param bool $isBundleItemProduct
     * @return bool
     */
    private function canProductBeProtected($order, $insuranceSelected, bool $isProductVirtual, bool $isBundleItemProduct): bool
    {
        return !($isProductVirtual || !$insuranceSelected || $isBundleItemProduct) && $this->isShippingMethodAllowed($order);
    }

    /**
     * @param Item $item
     * @return bool
     */
    private function isVirtualProduct(Item $item) : bool
    {
        return ($item->getProduct()->getIsVirtual() || $item->getProduct()->getTypeId() == Type::TYPE_DOWNLOADABLE);
    }

    /**
     * Return if the item is a bundle item product
     *
     * @param Item $item
     * @return bool
     */
    private function isBundleItemProduct(Item $item) : bool
    {
        return ($item->getProduct()->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE &&
            $item->getParentItem() && $item->getParentItem()->getProduct()->getTypeId() == \Magento\Bundle\Model\Product\Type::TYPE_CODE);
    }

    /**
     * @param Item $item
     * @return string
     */
    public function getFulfillmentStatus(Item $item): string
    {
        if (intval($item->getQtyShipped()) == 0) {
            return 'unfulfilled';
        }
        if (intval($item->getQtyShipped()) == intval($item->getQtyOrdered())) {
            return 'fulfilled';
        }
        return 'partially-fulfilled';
    }

    /**
     * @param $order
     * @return string
     */
    public function getShippingMethod($order): string
    {
        return substr(trim($order->getShippingMethod() ?? ''), 0, 200);
    }

    /**
     * @param mixed $item
     * @return bool
     */
    public function isNotASimpleProduct(mixed $item): bool
    {
        return !($item->getProduct() instanceof \Magento\Catalog\Model\Product) || $item->getProduct()->getTypeId() == Configurable::TYPE_CODE;
    }

    /**
     * @return mixed|string
     */
    public function getWeightUnit(): mixed
    {
        $weightUnit = $this->_getScopeValue('general/locale/weight_unit');
        return match ($weightUnit) {
            'lbs' => 'pound',
            'kgs' => 'kilogram',
            default => $weightUnit,
        };
    }
}
