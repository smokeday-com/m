<?php
/**
 * ShipmentsTrackingnumberBody
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
 * ShipmentsTrackingnumberBody Class Doc Comment
 *
 * @category Class
 * @package  Route\Route\Service
 * @author   Route Protect team
 * @link     https://docs.route.com/docs/
 */
class ShipmentsTrackingnumberBody implements ModelInterface, ArrayAccess
{
    const DISCRIMINATOR = null;

    /**
     * The original name of the model.
     *
     * @var string
     */
    protected static $swaggerModelName = 'shipments_trackingnumber_body';

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @var string[]
     */
    protected static $swaggerTypes = [
        'source_order_id' => 'string',
        'source_product_ids' => 'array',
        'courier_id' => 'string',
        'line_items' => '\Route\Route\Service\Model\LineItem[]'
    ];

    /**
     * Array of property to format mappings. Used for (de)serialization
     *
     * @var string[]
     */
    protected static $swaggerFormats = [
        'source_order_id' => null,
        'source_product_ids' => 'string',
        'courier_id' => null,
        'line_items' => null
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
        'source_order_id' => 'source_order_id',
        'source_product_ids' => 'source_product_ids',
        'courier_id' => 'courier_id',
        'line_items' => 'line_items'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'source_order_id' => 'setSourceOrderId',
        'source_product_ids' => 'setSourceProductIds',
        'courier_id' => 'setCourierId',
        'line_items' => 'setLineItems'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'source_order_id' => 'getSourceOrderId',
        'source_product_ids' => 'getSourceProductIds',
        'courier_id' => 'getCourierId',
        'line_items' => 'getLineItems'
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
        $this->container['source_order_id'] = isset($data['source_order_id']) ? $data['source_order_id'] : null;
        $this->container['source_product_ids'] = isset($data['source_product_ids']) ? $data['source_product_ids'] : null;
        $this->container['courier_id'] = isset($data['courier_id']) ? $data['courier_id'] : null;
        $this->container['line_items'] = isset($data['line_items']) ? $data['line_items'] : null;
    }

    /**
     * Show all the invalid properties with reasons.
     *
     * @return array invalid properties with reasons
     */
    public function listInvalidProperties()
    {
        $invalidProperties = [];

        if ($this->container['source_order_id'] === null) {
            $invalidProperties[] = "'source_order_id' can't be null";
        }
        if ($this->container['source_product_ids'] === null) {
            $invalidProperties[] = "'source_product_ids' can't be null";
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
     * Gets source_product_ids
     *
     * @return array
     */
    public function getSourceProductIds()
    {
        return $this->container['source_product_ids'];
    }

    /**
     * Sets source_product_ids
     *
     * @param array $source_product_ids List of all associated source_product_id's in shipment
     *
     * @return $this
     */
    public function setSourceProductIds($source_product_ids)
    {
        $this->container['source_product_ids'] = $source_product_ids;

        return $this;
    }

    /**
     * Gets courier_id
     *
     * @return string
     */
    public function getCourierId()
    {
        return $this->container['courier_id'];
    }

    /**
     * Sets courier_id
     *
     * @param string $courier_id The courier ID for the shipment's courier
     *
     * @return $this
     */
    public function setCourierId($courier_id)
    {
        $this->container['courier_id'] = $courier_id;

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
