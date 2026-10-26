<?php
/**
 * InlineResponse2001
 *
 * PHP version 8
 *
 * @category Class
 * @package  Route\Route\Service
 * @author   Route Protect team
 * @link     https://docs.route.com/docs/
 */




namespace Route\Route\Service\Model;

use \ArrayAccess;
use \Route\Route\Service\ObjectSerializer;

/**
 * InlineResponse2001 Class Doc Comment
 *
 * @category Class
 * @package  Route\Route\Service
 * @author   Route Protect team
 * @link     https://docs.route.com/docs/
 */
class InlineResponse2001 implements ModelInterface, ArrayAccess
{
    const DISCRIMINATOR = null;

    /**
     * The original name of the model.
     *
     * @var string
     */
    protected static $swaggerModelName = 'inline_response_200_1';

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @var string[]
     */
    protected static $swaggerTypes = [
        'id' => 'string',
        'created_on' => '\DateTime',
        'updated_on' => '\DateTime',
        'cancelled_on' => '\DateTime',
        'order_total' => 'double',
        'order_total_usd' => 'double',
        'shipping_total' => 'double',
        'shipping_total_usd' => 'double',
        'discounts_total' => 'double',
        'discounts_total_usd' => 'double',
        'order_number' => 'string',
        'amount_covered' => 'double',
        'currency' => 'string',
        'line_items' => '\Route\Route\Service\Model\LineItem[]',
        'shipping_details' => '\Route\Route\Service\Model\InlineResponse2001ShippingDetails',
        'merchant_id' => 'string',
        'paid_to_insure' => 'double',
        'source' => 'string',
        'source_created_on' => '\DateTime',
        'source_updated_on' => '\DateTime',
        'source_order_id' => 'string',
        'source_order_number' => 'string',
        'status' => 'string',
        'subtotal' => 'double',
        'taxes' => 'double',
        'amount_covered_usd' => 'double',
        'paid_to_insure_usd' => 'double',
        'subtotal_usd' => 'double',
        'taxes_usd' => 'double',
        'first_name' => 'string',
        'last_name' => 'string',
        'email_account_id' => 'string',
        'platform_id' => 'string',
        'exchange_rate' => 'double',
        'insured_status' => 'string',
        'has_pii' => 'bool',
        'parent_id' => 'string'
    ];

    /**
     * Array of property to format mappings. Used for (de)serialization
     *
     * @var string[]
     */
    protected static $swaggerFormats = [
        'id' => null,
        'created_on' => 'date-time',
        'updated_on' => 'date-time',
        'cancelled_on' => 'date-time',
        'order_total' => 'double',
        'order_total_usd' => 'double',
        'shipping_total' => 'double',
        'shipping_total_usd' => 'double',
        'discounts_total' => 'double',
        'discounts_total_usd' => 'double',
        'order_number' => null,
        'amount_covered' => 'double',
        'currency' => null,
        'line_items' => null,
        'shipping_details' => null,
        'merchant_id' => null,
        'paid_to_insure' => 'double',
        'source' => null,
        'source_created_on' => 'date-time',
        'source_updated_on' => 'date-time',
        'source_order_id' => null,
        'source_order_number' => null,
        'status' => null,
        'subtotal' => 'double',
        'taxes' => 'double',
        'amount_covered_usd' => 'double',
        'paid_to_insure_usd' => 'double',
        'subtotal_usd' => 'double',
        'taxes_usd' => 'double',
        'first_name' => null,
        'last_name' => null,
        'email_account_id' => null,
        'platform_id' => null,
        'exchange_rate' => 'double',
        'insured_status' => null,
        'has_pii' => null,
        'parent_id' => null
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
        'id' => 'id',
        'created_on' => 'created_on',
        'updated_on' => 'updated_on',
        'cancelled_on' => 'cancelled_on',
        'order_total' => 'order_total',
        'order_total_usd' => 'order_total_usd',
        'shipping_total' => 'shipping_total',
        'shipping_total_usd' => 'shipping_total_usd',
        'discounts_total' => 'discounts_total',
        'discounts_total_usd' => 'discounts_total_usd',
        'order_number' => 'order_number',
        'amount_covered' => 'amount_covered',
        'currency' => 'currency',
        'line_items' => 'line_items',
        'shipping_details' => 'shipping_details',
        'merchant_id' => 'merchant_id',
        'paid_to_insure' => 'paid_to_insure',
        'source' => 'source',
        'source_created_on' => 'source_created_on',
        'source_updated_on' => 'source_updated_on',
        'source_order_id' => 'source_order_id',
        'source_order_number' => 'source_order_number',
        'status' => 'status',
        'subtotal' => 'subtotal',
        'taxes' => 'taxes',
        'amount_covered_usd' => 'amount_covered_usd',
        'paid_to_insure_usd' => 'paid_to_insure_usd',
        'subtotal_usd' => 'subtotal_usd',
        'taxes_usd' => 'taxes_usd',
        'first_name' => 'first_name',
        'last_name' => 'last_name',
        'email_account_id' => 'email_account_id',
        'platform_id' => 'platform_id',
        'exchange_rate' => 'exchange_rate',
        'insured_status' => 'insured_status',
        'has_pii' => 'has_pii',
        'parent_id' => 'parent_id'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'id' => 'setId',
        'created_on' => 'setCreatedOn',
        'updated_on' => 'setUpdatedOn',
        'cancelled_on' => 'setCancelledOn',
        'order_total' => 'setOrderTotal',
        'order_total_usd' => 'setOrderTotalUsd',
        'shipping_total' => 'setShippingTotal',
        'shipping_total_usd' => 'setShippingTotalUsd',
        'discounts_total' => 'setDiscountsTotal',
        'discounts_total_usd' => 'setDiscountsTotalUsd',
        'order_number' => 'setOrderNumber',
        'amount_covered' => 'setAmountCovered',
        'currency' => 'setCurrency',
        'line_items' => 'setLineItems',
        'shipping_details' => 'setShippingDetails',
        'merchant_id' => 'setMerchantId',
        'paid_to_insure' => 'setPaidToInsure',
        'source' => 'setSource',
        'source_created_on' => 'setSourceCreatedOn',
        'source_updated_on' => 'setSourceUpdatedOn',
        'source_order_id' => 'setSourceOrderId',
        'source_order_number' => 'setSourceOrderNumber',
        'status' => 'setStatus',
        'subtotal' => 'setSubtotal',
        'taxes' => 'setTaxes',
        'amount_covered_usd' => 'setAmountCoveredUsd',
        'paid_to_insure_usd' => 'setPaidToInsureUsd',
        'subtotal_usd' => 'setSubtotalUsd',
        'taxes_usd' => 'setTaxesUsd',
        'first_name' => 'setFirstName',
        'last_name' => 'setLastName',
        'email_account_id' => 'setEmailAccountId',
        'platform_id' => 'setPlatformId',
        'exchange_rate' => 'setExchangeRate',
        'insured_status' => 'setInsuredStatus',
        'has_pii' => 'setHasPii',
        'parent_id' => 'setParentId'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'id' => 'getId',
        'created_on' => 'getCreatedOn',
        'updated_on' => 'getUpdatedOn',
        'cancelled_on' => 'getCancelledOn',
        'order_total' => 'getOrderTotal',
        'order_total_usd' => 'getOrderTotalUsd',
        'shipping_total' => 'getShippingTotal',
        'shipping_total_usd' => 'getShippingTotalUsd',
        'discounts_total' => 'getDiscountsTotal',
        'discounts_total_usd' => 'getDiscountsTotalUsd',
        'order_number' => 'getOrderNumber',
        'amount_covered' => 'getAmountCovered',
        'currency' => 'getCurrency',
        'line_items' => 'getLineItems',
        'shipping_details' => 'getShippingDetails',
        'merchant_id' => 'getMerchantId',
        'paid_to_insure' => 'getPaidToInsure',
        'source' => 'getSource',
        'source_created_on' => 'getSourceCreatedOn',
        'source_updated_on' => 'getSourceUpdatedOn',
        'source_order_id' => 'getSourceOrderId',
        'source_order_number' => 'getSourceOrderNumber',
        'status' => 'getStatus',
        'subtotal' => 'getSubtotal',
        'taxes' => 'getTaxes',
        'amount_covered_usd' => 'getAmountCoveredUsd',
        'paid_to_insure_usd' => 'getPaidToInsureUsd',
        'subtotal_usd' => 'getSubtotalUsd',
        'taxes_usd' => 'getTaxesUsd',
        'first_name' => 'getFirstName',
        'last_name' => 'getLastName',
        'email_account_id' => 'getEmailAccountId',
        'platform_id' => 'getPlatformId',
        'exchange_rate' => 'getExchangeRate',
        'insured_status' => 'getInsuredStatus',
        'has_pii' => 'getHasPii',
        'parent_id' => 'getParentId'
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
        $this->container['id'] = isset($data['id']) ? $data['id'] : null;
        $this->container['created_on'] = isset($data['created_on']) ? $data['created_on'] : null;
        $this->container['updated_on'] = isset($data['updated_on']) ? $data['updated_on'] : null;
        $this->container['cancelled_on'] = isset($data['cancelled_on']) ? $data['cancelled_on'] : null;
        $this->container['order_total'] = isset($data['order_total']) ? $data['order_total'] : null;
        $this->container['order_total_usd'] = isset($data['order_total_usd']) ? $data['order_total_usd'] : null;
        $this->container['shipping_total'] = isset($data['shipping_total']) ? $data['shipping_total'] : null;
        $this->container['shipping_total_usd'] = isset($data['shipping_total_usd']) ? $data['shipping_total_usd'] : null;
        $this->container['discounts_total'] = isset($data['discounts_total']) ? $data['discounts_total'] : null;
        $this->container['discounts_total_usd'] = isset($data['discounts_total_usd']) ? $data['discounts_total_usd'] : null;
        $this->container['order_number'] = isset($data['order_number']) ? $data['order_number'] : null;
        $this->container['amount_covered'] = isset($data['amount_covered']) ? $data['amount_covered'] : null;
        $this->container['currency'] = isset($data['currency']) ? $data['currency'] : null;
        $this->container['line_items'] = isset($data['line_items']) ? $data['line_items'] : null;
        $this->container['shipping_details'] = isset($data['shipping_details']) ? $data['shipping_details'] : null;
        $this->container['merchant_id'] = isset($data['merchant_id']) ? $data['merchant_id'] : null;
        $this->container['paid_to_insure'] = isset($data['paid_to_insure']) ? $data['paid_to_insure'] : null;
        $this->container['source'] = isset($data['source']) ? $data['source'] : null;
        $this->container['source_created_on'] = isset($data['source_created_on']) ? $data['source_created_on'] : null;
        $this->container['source_updated_on'] = isset($data['source_updated_on']) ? $data['source_updated_on'] : null;
        $this->container['source_order_id'] = isset($data['source_order_id']) ? $data['source_order_id'] : null;
        $this->container['source_order_number'] = isset($data['source_order_number']) ? $data['source_order_number'] : null;
        $this->container['status'] = isset($data['status']) ? $data['status'] : null;
        $this->container['subtotal'] = isset($data['subtotal']) ? $data['subtotal'] : null;
        $this->container['taxes'] = isset($data['taxes']) ? $data['taxes'] : null;
        $this->container['amount_covered_usd'] = isset($data['amount_covered_usd']) ? $data['amount_covered_usd'] : null;
        $this->container['paid_to_insure_usd'] = isset($data['paid_to_insure_usd']) ? $data['paid_to_insure_usd'] : null;
        $this->container['subtotal_usd'] = isset($data['subtotal_usd']) ? $data['subtotal_usd'] : null;
        $this->container['taxes_usd'] = isset($data['taxes_usd']) ? $data['taxes_usd'] : null;
        $this->container['first_name'] = isset($data['first_name']) ? $data['first_name'] : null;
        $this->container['last_name'] = isset($data['last_name']) ? $data['last_name'] : null;
        $this->container['email_account_id'] = isset($data['email_account_id']) ? $data['email_account_id'] : null;
        $this->container['platform_id'] = isset($data['platform_id']) ? $data['platform_id'] : null;
        $this->container['exchange_rate'] = isset($data['exchange_rate']) ? $data['exchange_rate'] : null;
        $this->container['insured_status'] = isset($data['insured_status']) ? $data['insured_status'] : null;
        $this->container['has_pii'] = isset($data['has_pii']) ? $data['has_pii'] : null;
        $this->container['parent_id'] = isset($data['parent_id']) ? $data['parent_id'] : null;
    }

    /**
     * Show all the invalid properties with reasons.
     *
     * @return array invalid properties with reasons
     */
    public function listInvalidProperties()
    {
        $invalidProperties = [];

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
     * Gets id
     *
     * @return string
     */
    public function getId()
    {
        return $this->container['id'];
    }

    /**
     * Sets id
     *
     * @param string $id Unique order ID
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->container['id'] = $id;

        return $this;
    }

    /**
     * Gets created_on
     *
     * @return \DateTime
     */
    public function getCreatedOn()
    {
        return $this->container['created_on'];
    }

    /**
     * Sets created_on
     *
     * @param \DateTime $created_on Date and time (ISO 8601 format) the order was created
     *
     * @return $this
     */
    public function setCreatedOn($created_on)
    {
        $this->container['created_on'] = $created_on;

        return $this;
    }

    /**
     * Gets updated_on
     *
     * @return \DateTime
     */
    public function getUpdatedOn()
    {
        return $this->container['updated_on'];
    }

    /**
     * Sets updated_on
     *
     * @param \DateTime $updated_on Date and time (ISO 8601 format) of last order update
     *
     * @return $this
     */
    public function setUpdatedOn($updated_on)
    {
        $this->container['updated_on'] = $updated_on;

        return $this;
    }

    /**
     * Gets cancelled_on
     *
     * @return \DateTime
     */
    public function getCancelledOn()
    {
        return $this->container['cancelled_on'];
    }

    /**
     * Sets cancelled_on
     *
     * @param \DateTime $cancelled_on Date and time (ISO 8601 format) the order was cancelled
     *
     * @return $this
     */
    public function setCancelledOn($cancelled_on)
    {
        $this->container['cancelled_on'] = $cancelled_on;

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
     * Gets order_total_usd
     *
     * @return double
     */
    public function getOrderTotalUsd()
    {
        return $this->container['order_total_usd'];
    }

    /**
     * Sets order_total_usd
     *
     * @param double $order_total_usd The total price of all of the items in the cart, in USD, after discounts, shipping, and taxes have been applied.
     *
     * @return $this
     */
    public function setOrderTotalUsd($order_total_usd)
    {
        $this->container['order_total_usd'] = $order_total_usd;

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
     * Gets shipping_total_usd
     *
     * @return double
     */
    public function getShippingTotalUsd()
    {
        return $this->container['shipping_total_usd'];
    }

    /**
     * Sets shipping_total_usd
     *
     * @param double $shipping_total_usd The shipping price, in USD, of an order.
     *
     * @return $this
     */
    public function setShippingTotalUsd($shipping_total_usd)
    {
        $this->container['shipping_total_usd'] = $shipping_total_usd;

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
     * Gets discounts_total_usd
     *
     * @return double
     */
    public function getDiscountsTotalUsd()
    {
        return $this->container['discounts_total_usd'];
    }

    /**
     * Sets discounts_total_usd
     *
     * @param double $discounts_total_usd The total value of all discounts, in USD, applied to the order.
     *
     * @return $this
     */
    public function setDiscountsTotalUsd($discounts_total_usd)
    {
        $this->container['discounts_total_usd'] = $discounts_total_usd;

        return $this;
    }

    /**
     * Gets order_number
     *
     * @return string
     */
    public function getOrderNumber()
    {
        return $this->container['order_number'];
    }

    /**
     * Sets order_number
     *
     * @param string $order_number Unique string given to the order by Route
     *
     * @return $this
     */
    public function setOrderNumber($order_number)
    {
        $this->container['order_number'] = $order_number;

        return $this;
    }

    /**
     * Gets amount_covered
     *
     * @return double
     */
    public function getAmountCovered()
    {
        return $this->container['amount_covered'];
    }

    /**
     * Sets amount_covered
     *
     * @param double $amount_covered The monetary value covered by Route Protection
     *
     * @return $this
     */
    public function setAmountCovered($amount_covered)
    {
        $this->container['amount_covered'] = $amount_covered;

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
     * Gets line_items
     *
     * @return \Route\Route\Service\Model\LineItem[]
     */
    public function getLineItems()
    {
        return $this->container['line_items'];
    }

    /**
     * Sets line_items
     *
     * @param \Route\Route\Service\Model\LineItem[] $line_items Line item details stored as a JSON array.
     *
     * @return $this
     */
    public function setLineItems($line_items)
    {
        $this->container['line_items'] = $line_items;

        return $this;
    }

    /**
     * Gets shipping_details
     *
     * @return \Route\Route\Service\Model\InlineResponse2001ShippingDetails
     */
    public function getShippingDetails()
    {
        return $this->container['shipping_details'];
    }

    /**
     * Sets shipping_details
     *
     * @param \Route\Route\Service\Model\InlineResponse2001ShippingDetails $shipping_details shipping_details
     *
     * @return $this
     */
    public function setShippingDetails($shipping_details)
    {
        $this->container['shipping_details'] = $shipping_details;

        return $this;
    }

    /**
     * Gets merchant_id
     *
     * @return string
     */
    public function getMerchantId()
    {
        return $this->container['merchant_id'];
    }

    /**
     * Sets merchant_id
     *
     * @param string $merchant_id Unique ID for online merchant
     *
     * @return $this
     */
    public function setMerchantId($merchant_id)
    {
        $this->container['merchant_id'] = $merchant_id;

        return $this;
    }

    /**
     * Gets paid_to_insure
     *
     * @return double
     */
    public function getPaidToInsure()
    {
        return $this->container['paid_to_insure'];
    }

    /**
     * Sets paid_to_insure
     *
     * @param double $paid_to_insure The amount paid to insure order
     *
     * @return $this
     */
    public function setPaidToInsure($paid_to_insure)
    {
        $this->container['paid_to_insure'] = $paid_to_insure;

        return $this;
    }

    /**
     * Gets source
     *
     * @return string
     */
    public function getSource()
    {
        return $this->container['source'];
    }

    /**
     * Sets source
     *
     * @param string $source Source
     *
     * @return $this
     */
    public function setSource($source)
    {
        $this->container['source'] = $source;

        return $this;
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
     * Gets source_order_number
     *
     * @return string
     */
    public function getSourceOrderNumber()
    {
        return $this->container['source_order_number'];
    }

    /**
     * Sets source_order_number
     *
     * @param string $source_order_number A unique string provided by the store.
     *
     * @return $this
     */
    public function setSourceOrderNumber($source_order_number)
    {
        $this->container['source_order_number'] = $source_order_number;

        return $this;
    }

    /**
     * Gets status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->container['status'];
    }

    /**
     * Sets status
     *
     * @param string $status Status of order
     *
     * @return $this
     */
    public function setStatus($status)
    {
        $this->container['status'] = $status;

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
     * Gets amount_covered_usd
     *
     * @return double
     */
    public function getAmountCoveredUsd()
    {
        return $this->container['amount_covered_usd'];
    }

    /**
     * Sets amount_covered_usd
     *
     * @param double $amount_covered_usd The monetary value, in USD, covered by Route Protection
     *
     * @return $this
     */
    public function setAmountCoveredUsd($amount_covered_usd)
    {
        $this->container['amount_covered_usd'] = $amount_covered_usd;

        return $this;
    }

    /**
     * Gets paid_to_insure_usd
     *
     * @return double
     */
    public function getPaidToInsureUsd()
    {
        return $this->container['paid_to_insure_usd'];
    }

    /**
     * Sets paid_to_insure_usd
     *
     * @param double $paid_to_insure_usd The amount paid, in USD, to insure order
     *
     * @return $this
     */
    public function setPaidToInsureUsd($paid_to_insure_usd)
    {
        $this->container['paid_to_insure_usd'] = $paid_to_insure_usd;

        return $this;
    }

    /**
     * Gets subtotal_usd
     *
     * @return double
     */
    public function getSubtotalUsd()
    {
        return $this->container['subtotal_usd'];
    }

    /**
     * Sets subtotal_usd
     *
     * @param double $subtotal_usd The sum of the cart’s line-item prices in USD. The subtotal doesn’t include taxes (unless taxes are included in the prices), cart discounts, or shipping costs.
     *
     * @return $this
     */
    public function setSubtotalUsd($subtotal_usd)
    {
        $this->container['subtotal_usd'] = $subtotal_usd;

        return $this;
    }

    /**
     * Gets taxes_usd
     *
     * @return double
     */
    public function getTaxesUsd()
    {
        return $this->container['taxes_usd'];
    }

    /**
     * Sets taxes_usd
     *
     * @param double $taxes_usd The taxes applied to the subtotal in USD.
     *
     * @return $this
     */
    public function setTaxesUsd($taxes_usd)
    {
        $this->container['taxes_usd'] = $taxes_usd;

        return $this;
    }

    /**
     * Gets first_name
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->container['first_name'];
    }

    /**
     * Sets first_name
     *
     * @param string $first_name Customer's first name.
     *
     * @return $this
     */
    public function setFirstName($first_name)
    {
        $this->container['first_name'] = $first_name;

        return $this;
    }

    /**
     * Gets last_name
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->container['last_name'];
    }

    /**
     * Sets last_name
     *
     * @param string $last_name Customer's last name.
     *
     * @return $this
     */
    public function setLastName($last_name)
    {
        $this->container['last_name'] = $last_name;

        return $this;
    }

    /**
     * Gets email_account_id
     *
     * @return string
     */
    public function getEmailAccountId()
    {
        return $this->container['email_account_id'];
    }

    /**
     * Sets email_account_id
     *
     * @param string $email_account_id Unique string identifying customer's email address.
     *
     * @return $this
     */
    public function setEmailAccountId($email_account_id)
    {
        $this->container['email_account_id'] = $email_account_id;

        return $this;
    }

    /**
     * Gets platform_id
     *
     * @return string
     */
    public function getPlatformId()
    {
        return $this->container['platform_id'];
    }

    /**
     * Sets platform_id
     *
     * @param string $platform_id Unique string identifing ecommmerce platform
     *
     * @return $this
     */
    public function setPlatformId($platform_id)
    {
        $this->container['platform_id'] = $platform_id;

        return $this;
    }

    /**
     * Gets exchange_rate
     *
     * @return double
     */
    public function getExchangeRate()
    {
        return $this->container['exchange_rate'];
    }

    /**
     * Sets exchange_rate
     *
     * @param double $exchange_rate The exchange rate from USD to currency used for purchase
     *
     * @return $this
     */
    public function setExchangeRate($exchange_rate)
    {
        $this->container['exchange_rate'] = $exchange_rate;

        return $this;
    }

    /**
     * Gets insured_status
     *
     * @return string
     */
    public function getInsuredStatus()
    {
        return $this->container['insured_status'];
    }

    /**
     * Sets insured_status
     *
     * @param string $insured_status Status of insurance selected
     *
     * @return $this
     */
    public function setInsuredStatus($insured_status)
    {
        $this->container['insured_status'] = $insured_status;

        return $this;
    }

    /**
     * Gets has_pii
     *
     * @return bool
     */
    public function getHasPii()
    {
        return $this->container['has_pii'];
    }

    /**
     * Sets has_pii
     *
     * @param bool $has_pii Status of PII for order
     *
     * @return $this
     */
    public function setHasPii($has_pii)
    {
        $this->container['has_pii'] = $has_pii;

        return $this;
    }

    /**
     * Gets parent_id
     *
     * @return string
     */
    public function getParentId()
    {
        return $this->container['parent_id'];
    }

    /**
     * Sets parent_id
     *
     * @param string $parent_id Id for parent transaction
     *
     * @return $this
     */
    public function setParentId($parent_id)
    {
        $this->container['parent_id'] = $parent_id;

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
