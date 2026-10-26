<?php

namespace Route\Route\Service\Model;

use \ArrayAccess;
use \Route\Route\Service\ObjectSerializer;

/**
 * V2OrdersBody Class Doc Comment
 *
 * @category Class
 * @package  Route\Route\Service
 * @author   Route Protect team
 * @link     https://docs.route.com/docs/
 */
class V2OrdersBody implements ModelInterface, ArrayAccess
{
    const DISCRIMINATOR = null;

    /**
     * The original name of the model.
     *
     * @var string
     */
    protected static $swaggerModelName = 'v2_orders_body';

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @var string[]
     */
    protected static $swaggerTypes = [
        'quote_id' => 'string',
        'source_order_id' => 'string',
        'subtotal' => 'double',
        'order_total' => 'double',
        'shipping_total' => 'double',
        'discounts_total' => 'double',
        'taxes' => 'double',
        'currency' => 'string',
        'insurance_selected' => 'bool',
        'customer_details' => '\Route\Route\Service\Model\V2OrdersCustomerDetails',
        'shipping_details' => '\Route\Route\Service\Model\V2OrdersShippingDetails',
        'billing_details' => '\Route\Route\Service\Model\V2OrdersBillingDetails',
        'line_items' => '\Route\Route\Service\Model\LineItem[]',
        'source_created_on' => '\DateTime',
        'source_updated_on' => '\DateTime'
    ];

    /**
     * Array of property to format mappings. Used for (de)serialization
     *
     * @var string[]
     */
    protected static $swaggerFormats = [
        'quote_id' => null,
        'source_order_id' => null,
        'subtotal' => 'double',
        'order_total' => 'double',
        'shipping_total' => 'double',
        'discounts_total' => 'double',
        'taxes' => 'double',
        'currency' => null,
        'insurance_selected' => null,
        'customer_details' => null,
        'shipping_details' => null,
        'billing_details' => null,
        'line_items' => null,
        'source_created_on' => 'date-time',
        'source_updated_on' => 'date-time'
    ];

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @return array
     */
    public static function swaggerTypes()
    {
        return self::$swaggerTypes;
    }

    /**
     * Array of property to format mappings. Used for (de)serialization
     *
     * @return array
     */
    public static function swaggerFormats()
    {
        return self::$swaggerFormats;
    }

    /**
     * Array of attributes where the key is the local name,
     * and the value is the original name
     *
     * @var string[]
     */
    protected static $attributeMap = [
        'quote_id' => 'quote_id',
        'source_order_id' => 'source_order_id',
        'subtotal' => 'subtotal',
        'order_total' => 'order_total',
        'shipping_total' => 'shipping_total',
        'discounts_total' => 'discounts_total',
        'taxes' => 'taxes',
        'currency' => 'currency',
        'insurance_selected' => 'insurance_selected',
        'customer_details' => 'customer_details',
        'shipping_details' => 'shipping_details',
        'billing_details' => 'billing_details',
        'line_items' => 'line_items',
        'source_created_on' => 'source_created_on',
        'source_updated_on' => 'source_updated_on'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'quote_id' => 'setQuoteId',
        'source_order_id' => 'setSourceOrderId',
        'subtotal' => 'setSubtotal',
        'order_total' => 'setOrderTotal',
        'shipping_total' => 'setShippingTotal',
        'discounts_total' => 'setDiscountsTotal',
        'taxes' => 'setTaxes',
        'currency' => 'setCurrency',
        'insurance_selected' => 'setInsuranceSelected',
        'customer_details' => 'setCustomerDetails',
        'shipping_details' => 'setShippingDetails',
        'billing_details' => 'setBillingDetails',
        'line_items' => 'setLineItems',
        'source_created_on' => 'setSourceCreatedOn',
        'source_updated_on' => 'setSourceUpdatedOn'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'quote_id' => 'getQuoteId',
        'source_order_id' => 'getSourceOrderId',
        'subtotal' => 'getSubtotal',
        'order_total' => 'getOrderTotal',
        'shipping_total' => 'getShippingTotal',
        'discounts_total' => 'getDiscountsTotal',
        'taxes' => 'getTaxes',
        'currency' => 'getCurrency',
        'insurance_selected' => 'getInsuranceSelected',
        'customer_details' => 'getCustomerDetails',
        'shipping_details' => 'getShippingDetails',
        'billing_details' => 'getBillingDetails',
        'line_items' => 'getLineItems',
        'source_created_on' => 'getSourceCreatedOn',
        'source_updated_on' => 'getSourceUpdatedOn'
    ];

    /**
     * Array of attributes where the key is the local name,
     * and the value is the original name
     *
     * @return array
     */
    public static function attributeMap()
    {
        return self::$attributeMap;
    }

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @return array
     */
    public static function setters()
    {
        return self::$setters;
    }

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @return array
     */
    public static function getters()
    {
        return self::$getters;
    }

    /**
     * The original name of the model.
     *
     * @return string
     */
    public function getModelName()
    {
        return self::$swaggerModelName;
    }



    /**
     * Associative array for storing property values
     *
     * @var mixed[]
     */
    protected $container = [];

    /**
     * Constructor
     *
     * @param mixed[] $data Associated array of property values
     *                      initializing the model
     */
    public function __construct(array $data = null)
    {
        $this->container['quote_id'] = $data['quote_id'] ?? null;
        $this->container['source_order_id'] = $data['source_order_id'] ?? null;
        $this->container['subtotal'] = $data['subtotal'] ?? null;
        $this->container['order_total'] = $data['order_total'] ?? null;
        $this->container['shipping_total'] = $data['shipping_total'] ?? null;
        $this->container['discounts_total'] = $data['discounts_total'] ?? null;
        $this->container['taxes'] = $data['taxes'] ?? null;
        $this->container['currency'] = $data['currency'] ?? null;
        $this->container['insurance_selected'] = $data['insurance_selected'] ?? null;
        $this->container['customer_details'] = $data['customer_details'] ?? null;
        $this->container['shipping_details'] = $data['shipping_details'] ?? null;
        $this->container['billing_details'] = $data['billing_details'] ?? null;
        $this->container['line_items'] = $data['line_items'] ?? null;
        $this->container['source_created_on'] = $data['source_created_on'] ?? null;
        $this->container['source_updated_on'] = $data['source_updated_on'] ?? null;
    }

    /**
     * Show all the invalid properties with reasons.
     *
     * @return array invalid properties with reasons
     */
    public function listInvalidProperties()
    {
        $invalidProperties = [];

        if ($this->container['quote_id'] === null) {
            $invalidProperties[] = "'quote_id' can't be null";
        }
        if ($this->container['source_order_id'] === null) {
            $invalidProperties[] = "'source_order_id' can't be null";
        }
        if ($this->container['subtotal'] === null) {
            $invalidProperties[] = "'subtotal' can't be null";
        }
        if ($this->container['taxes'] === null) {
            $invalidProperties[] = "'taxes' can't be null";
        }
        if ($this->container['insurance_selected'] === null) {
            $invalidProperties[] = "'insurance_selected' can't be null";
        }
        if ($this->container['customer_details'] === null) {
            $invalidProperties[] = "'customer_details' can't be null";
        }
        if ($this->container['shipping_details'] === null) {
            $invalidProperties[] = "'shipping_details' can't be null";
        }
        if ($this->container['billing_details'] === null) {
            $invalidProperties[] = "'billing_details' can't be null";
        }

        if ($this->container['line_items'] === null) {
            $invalidProperties[] = "'line_items' can't be null";
        }
        if ($this->container['source_created_on'] === null) {
            $invalidProperties[] = "'source_created_on' can't be null";
        }
        if ($this->container['source_updated_on'] === null) {
            $invalidProperties[] = "'source_updated_on' can't be null";
        }
        return $invalidProperties;
    }

    /**
     * Validate all the properties in the model
     * return true if all passed
     *
     * @return bool True if all properties are valid
     */
    public function valid()
    {
        return count($this->listInvalidProperties()) === 0;
    }


    /**
     * Gets quote_id
     *
     * @return string
     */
    public function getQuoteId()
    {
        return $this->container['quote_id'];
    }

    /**
     * Sets quote_id
     *
     * @param string $quote_id id of the quote generated to the order
     *
     * @return $this
     */
    public function setQuoteId($quote_id)
    {
        $this->container['quote_id'] = $quote_id;

        return $this;
    }

    /**
     * Gets source_order_id
     *
     * @return string
     */
    public function getSourceOrderId()
    {
        return $this->container['source_order_id'];
    }

    /**
     * Sets source_order_id
     *
     * @param string $source_order_id A unique order id provided by the store.
     *
     * @return $this
     */
    public function setSourceOrderId($source_order_id)
    {
        $this->container['source_order_id'] = $source_order_id;

        return $this;
    }

    /**
     * Gets subtotal
     *
     * @return double
     */
    public function getSubtotal()
    {
        return $this->container['subtotal'];
    }

    /**
     * Sets subtotal
     *
     * @param double $subtotal The sum of the cart’s line-item prices. The subtotal doesn’t include taxes (unless taxes are included in the prices), cart discounts, or shipping costs.
     *
     * @return $this
     */
    public function setSubtotal($subtotal)
    {
        $this->container['subtotal'] = $subtotal;

        return $this;
    }

    /**
     * Gets order_total
     *
     * @return double
     */
    public function getOrderTotal()
    {
        return $this->container['order_total'];
    }

    /**
     * Sets order_total
     *
     * @param double $order_total The total price of all of the items in the cart after discounts, shipping, and taxes have been applied.
     *
     * @return $this
     */
    public function setOrderTotal($order_total)
    {
        $this->container['order_total'] = $order_total;

        return $this;
    }

    /**
     * Gets shipping_total
     *
     * @return double
     */
    public function getShippingTotal()
    {
        return $this->container['shipping_total'];
    }

    /**
     * Sets shipping_total
     *
     * @param double $shipping_total The shipping price of an order.
     *
     * @return $this
     */
    public function setShippingTotal($shipping_total)
    {
        $this->container['shipping_total'] = $shipping_total;

        return $this;
    }

    /**
     * Gets discounts_total
     *
     * @return double
     */
    public function getDiscountsTotal()
    {
        return $this->container['discounts_total'];
    }

    /**
     * Sets discounts_total
     *
     * @param double $discounts_total The total value of all discounts applied to the order.
     *
     * @return $this
     */
    public function setDiscountsTotal($discounts_total)
    {
        $this->container['discounts_total'] = $discounts_total;

        return $this;
    }

    /**
     * Gets taxes
     *
     * @return double
     */
    public function getTaxes()
    {
        return $this->container['taxes'];
    }

    /**
     * Sets taxes
     *
     * @param double $taxes The taxes applied to the subtotal.
     *
     * @return $this
     */
    public function setTaxes($taxes)
    {
        $this->container['taxes'] = $taxes;

        return $this;
    }

    /**
     * Gets currency
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->container['currency'];
    }

    /**
     * Sets currency
     *
     * @param string $currency The order's three-letter ISO 4217 code
     *
     * @return $this
     */
    public function setCurrency($currency)
    {
        $this->container['currency'] = $currency;

        return $this;
    }

    /**
     * Gets insurance_selected
     *
     * @return bool
     */
    public function getInsuranceSelected()
    {
        return $this->container['insurance_selected'];
    }

    /**
     * Sets insurance_selected
     *
     * @param bool $insurance_selected When it is true it informs that customer is insuring order.
     *
     * @return $this
     */
    public function setInsuranceSelected($insurance_selected)
    {
        $this->container['insurance_selected'] = $insurance_selected;

        return $this;
    }

    /**
     * Gets customer_details
     *
     * @return \Route\Route\Service\Model\V2OrdersCustomerDetails
     */
    public function getCustomerDetails()
    {
        return $this->container['customer_details'];
    }

    /**
     * Sets customer_details
     *
     * @param \Route\Route\Service\Model\V2OrdersCustomerDetails $customer_details customer_details
     *
     * @return $this
     */
    public function setCustomerDetails($customer_details)
    {
        $this->container['customer_details'] = $customer_details;

        return $this;
    }

    /**
     * Gets shipping_details
     *
     * @return \Route\Route\Service\Model\V2OrdersShippingDetails
     */
    public function getShippingDetails()
    {
        return $this->container['shipping_details'];
    }

    /**
     * Sets shipping_details
     *
     * @param \Route\Route\Service\Model\V2OrdersShippingDetails $shipping_details shipping_details
     *
     * @return $this
     */
    public function setShippingDetails($shipping_details)
    {
        $this->container['shipping_details'] = $shipping_details;

        return $this;
    }

    /**
     * Gets billing_details
     *
     * @return \Route\Route\Service\Model\V2OrdersBillingDetails
     */
    public function getBillingDetails()
    {
        return $this->container['billing_details'];
    }

    /**
     * Sets billing_details
     *
     * @param \Route\Route\Service\Model\V2OrdersBillingDetails $billing_details billing_details
     *
     * @return $this
     */
    public function setBillingDetails($billing_details)
    {
        $this->container['billing_details'] = $billing_details;

        return $this;
    }

    /**
     * Gets line_items
     *
     * @return \Route\Route\Service\Model\LineItem[]
     */
    public function getLineItems()
    {
        return $this->container['line_items'];
    }

    /**
     * Gets source_created_on
     *
     * @return \DateTime
     */
    public function getSourceCreatedOn()
    {
        return $this->container['source_created_on'];
    }

    /**
     * Sets source_created_on
     *
     * @param \DateTime $source_created_on Date and time (ISO 8601 format) that order was created
     *
     * @return $this
     */
    public function setSourceCreatedOn($source_created_on)
    {
        $this->container['source_created_on'] = $source_created_on;

        return $this;
    }

    /**
     * Gets source_updated_on
     *
     * @return \DateTime
     */
    public function getSourceUpdatedOn()
    {
        return $this->container['source_updated_on'];
    }

    /**
     * Sets source_updated_on
     *
     * @param \DateTime $source_updated_on Date and time (ISO 8601 format) that order was updated
     *
     * @return $this
     */
    public function setSourceUpdatedOn($source_updated_on)
    {
        $this->container['source_updated_on'] = $source_updated_on;

        return $this;
    }
    /**
     * Sets line_items
     *
     * @param \Route\Route\Service\Model\LineItem[] $line_items List of line item ids and quanitity.
     *
     * @return $this
     */
    public function setLineItems($line_items)
    {
        $this->container['line_items'] = $line_items;

        return $this;
    }

    /**
     * Returns true if offset exists. False otherwise.
     *
     * @param integer $offset Offset
     *
     * @return boolean
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    /**
     * Gets offset.
     *
     * @param integer $offset Offset
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }

    /**
     * Sets value based on offset.
     *
     * @param integer $offset Offset
     * @param mixed   $value  Value to be set
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    /**
     * Unsets offset.
     *
     * @param integer $offset Offset
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }

    /**
     * Gets the string presentation of the object
     *
     * @return string
     */
    public function __toString()
    {
        if (defined('JSON_PRETTY_PRINT')) { // use JSON pretty print
            return json_encode(
                ObjectSerializer::sanitizeForSerialization($this),
                JSON_PRETTY_PRINT
            );
        }

        return json_encode(ObjectSerializer::sanitizeForSerialization($this));
    }
}
