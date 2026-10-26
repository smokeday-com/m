<?php
/**
 * InlineResponse200
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
 * InlineResponse200 Class Doc Comment
 *
 * @category Class
 * @package  Route\Route\Service
 * @author   Route Protect team
 * @link     https://docs.route.com/docs/
 */
class InlineResponse200 implements ModelInterface, ArrayAccess
{
    const DISCRIMINATOR = null;

    /**
     * The original name of the model.
     *
     * @var string
     */
    protected static $swaggerModelName = 'inline_response_200';

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @var string[]
     */
    protected static $swaggerTypes = [
        'amount_covered' => 'double',
        'amount_covered_usd' => 'double',
        'created_on' => '\DateTime',
        'currency' => 'string',
        'email_account_id' => 'string',
        'exchange_rate' => 'double',
        'first_name' => 'string',
        'has_pii' => 'bool',
        'id' => 'string',
        'insured_status' => 'string',
        'last_name' => 'string',
        'merchant_id' => 'string',
        'order_number' => 'string',
        'paid_to_insure' => 'double',
        'paid_to_insure_usd' => 'double',
        'platform_id' => 'string',
        'source_created_on' => '\DateTime',
        'source_order_id' => 'string',
        'status' => 'string',
        'subtotal' => 'double',
        'subtotal_usd' => 'double',
        'taxes' => 'double',
        'taxes_usd' => 'double',
        'updated_on' => '\DateTime'
    ];

    /**
     * Array of property to format mappings. Used for (de)serialization
     *
     * @var string[]
     */
    protected static $swaggerFormats = [
        'amount_covered' => 'double',
        'amount_covered_usd' => 'double',
        'created_on' => 'date-time',
        'currency' => null,
        'email_account_id' => null,
        'exchange_rate' => 'double',
        'first_name' => null,
        'has_pii' => null,
        'id' => null,
        'insured_status' => null,
        'last_name' => null,
        'merchant_id' => null,
        'order_number' => null,
        'paid_to_insure' => 'double',
        'paid_to_insure_usd' => 'double',
        'platform_id' => null,
        'source_created_on' => 'date-time',
        'source_order_id' => null,
        'status' => null,
        'subtotal' => 'double',
        'subtotal_usd' => 'double',
        'taxes' => 'double',
        'taxes_usd' => 'double',
        'updated_on' => null
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
        'amount_covered' => 'amount_covered',
        'amount_covered_usd' => 'amount_covered_usd',
        'created_on' => 'created_on',
        'currency' => 'currency',
        'email_account_id' => 'email_account_id',
        'exchange_rate' => 'exchange_rate',
        'first_name' => 'first_name',
        'has_pii' => 'has_pii',
        'id' => 'id',
        'insured_status' => 'insured_status',
        'last_name' => 'last_name',
        'merchant_id' => 'merchant_id',
        'order_number' => 'order_number',
        'paid_to_insure' => 'paid_to_insure',
        'paid_to_insure_usd' => 'paid_to_insure_usd',
        'platform_id' => 'platform_id',
        'source_created_on' => 'source_created_on',
        'source_order_id' => 'source_order_id',
        'status' => 'status',
        'subtotal' => 'subtotal',
        'subtotal_usd' => 'subtotal_usd',
        'taxes' => 'taxes',
        'taxes_usd' => 'taxes_usd',
        'updated_on' => 'updated_on'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'amount_covered' => 'setAmountCovered',
        'amount_covered_usd' => 'setAmountCoveredUsd',
        'created_on' => 'setCreatedOn',
        'currency' => 'setCurrency',
        'email_account_id' => 'setEmailAccountId',
        'exchange_rate' => 'setExchangeRate',
        'first_name' => 'setFirstName',
        'has_pii' => 'setHasPii',
        'id' => 'setId',
        'insured_status' => 'setInsuredStatus',
        'last_name' => 'setLastName',
        'merchant_id' => 'setMerchantId',
        'order_number' => 'setOrderNumber',
        'paid_to_insure' => 'setPaidToInsure',
        'paid_to_insure_usd' => 'setPaidToInsureUsd',
        'platform_id' => 'setPlatformId',
        'source_created_on' => 'setSourceCreatedOn',
        'source_order_id' => 'setSourceOrderId',
        'status' => 'setStatus',
        'subtotal' => 'setSubtotal',
        'subtotal_usd' => 'setSubtotalUsd',
        'taxes' => 'setTaxes',
        'taxes_usd' => 'setTaxesUsd',
        'updated_on' => 'setUpdatedOn'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'amount_covered' => 'getAmountCovered',
        'amount_covered_usd' => 'getAmountCoveredUsd',
        'created_on' => 'getCreatedOn',
        'currency' => 'getCurrency',
        'email_account_id' => 'getEmailAccountId',
        'exchange_rate' => 'getExchangeRate',
        'first_name' => 'getFirstName',
        'has_pii' => 'getHasPii',
        'id' => 'getId',
        'insured_status' => 'getInsuredStatus',
        'last_name' => 'getLastName',
        'merchant_id' => 'getMerchantId',
        'order_number' => 'getOrderNumber',
        'paid_to_insure' => 'getPaidToInsure',
        'paid_to_insure_usd' => 'getPaidToInsureUsd',
        'platform_id' => 'getPlatformId',
        'source_created_on' => 'getSourceCreatedOn',
        'source_order_id' => 'getSourceOrderId',
        'status' => 'getStatus',
        'subtotal' => 'getSubtotal',
        'subtotal_usd' => 'getSubtotalUsd',
        'taxes' => 'getTaxes',
        'taxes_usd' => 'getTaxesUsd',
        'updated_on' => 'getUpdatedOn'
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
        $this->container['amount_covered'] = isset($data['amount_covered']) ? $data['amount_covered'] : null;
        $this->container['amount_covered_usd'] = isset($data['amount_covered_usd']) ? $data['amount_covered_usd'] : null;
        $this->container['created_on'] = isset($data['created_on']) ? $data['created_on'] : null;
        $this->container['currency'] = isset($data['currency']) ? $data['currency'] : null;
        $this->container['email_account_id'] = isset($data['email_account_id']) ? $data['email_account_id'] : null;
        $this->container['exchange_rate'] = isset($data['exchange_rate']) ? $data['exchange_rate'] : null;
        $this->container['first_name'] = isset($data['first_name']) ? $data['first_name'] : null;
        $this->container['has_pii'] = isset($data['has_pii']) ? $data['has_pii'] : null;
        $this->container['id'] = isset($data['id']) ? $data['id'] : null;
        $this->container['insured_status'] = isset($data['insured_status']) ? $data['insured_status'] : null;
        $this->container['last_name'] = isset($data['last_name']) ? $data['last_name'] : null;
        $this->container['merchant_id'] = isset($data['merchant_id']) ? $data['merchant_id'] : null;
        $this->container['order_number'] = isset($data['order_number']) ? $data['order_number'] : null;
        $this->container['paid_to_insure'] = isset($data['paid_to_insure']) ? $data['paid_to_insure'] : null;
        $this->container['paid_to_insure_usd'] = isset($data['paid_to_insure_usd']) ? $data['paid_to_insure_usd'] : null;
        $this->container['platform_id'] = isset($data['platform_id']) ? $data['platform_id'] : null;
        $this->container['source_created_on'] = isset($data['source_created_on']) ? $data['source_created_on'] : null;
        $this->container['source_order_id'] = isset($data['source_order_id']) ? $data['source_order_id'] : null;
        $this->container['status'] = isset($data['status']) ? $data['status'] : null;
        $this->container['subtotal'] = isset($data['subtotal']) ? $data['subtotal'] : null;
        $this->container['subtotal_usd'] = isset($data['subtotal_usd']) ? $data['subtotal_usd'] : null;
        $this->container['taxes'] = isset($data['taxes']) ? $data['taxes'] : null;
        $this->container['taxes_usd'] = isset($data['taxes_usd']) ? $data['taxes_usd'] : null;
        $this->container['updated_on'] = isset($data['updated_on']) ? $data['updated_on'] : null;
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
     * @param double $amount_covered_usd The monetary value covered by Route Protection in USD
     *
     * @return $this
     */
    public function setAmountCoveredUsd($amount_covered_usd)
    {
        $this->container['amount_covered_usd'] = $amount_covered_usd;

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
     * @param \DateTime $created_on Date and time (ISO 8601 format) that order was created
     *
     * @return $this
     */
    public function setCreatedOn($created_on)
    {
        $this->container['created_on'] = $created_on;

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
     * @param string $currency The three-letter ISO 4217 code for the currency used in the order
     *
     * @return $this
     */
    public function setCurrency($currency)
    {
        $this->container['currency'] = $currency;

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
     * @param string $email_account_id Unique ID for customer's email address.
     *
     * @return $this
     */
    public function setEmailAccountId($email_account_id)
    {
        $this->container['email_account_id'] = $email_account_id;

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
     * @param bool $has_pii Status of PII
     *
     * @return $this
     */
    public function setHasPii($has_pii)
    {
        $this->container['has_pii'] = $has_pii;

        return $this;
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
     * @param double $paid_to_insure_usd The amount paid to insure order in USD
     *
     * @return $this
     */
    public function setPaidToInsureUsd($paid_to_insure_usd)
    {
        $this->container['paid_to_insure_usd'] = $paid_to_insure_usd;

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
     * Gets updated_on
     *
     * @return
     */
    public function getUpdatedOn()
    {
        return $this->container['updated_on'];
    }

    /**
     * Sets updated_on
     *
     * @param  $updated_on Date and time (ISO 8601 format) that order was updated
     *
     * @return $this
     */
    public function setUpdatedOn($updated_on)
    {
        $this->container['updated_on'] = $updated_on;

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
