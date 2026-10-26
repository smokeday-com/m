<?php
/**
 * InlineResponse2002
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
 * InlineResponse2002 Class Doc Comment
 *
 * @category Class
 * @package  Route\Route\Service
 * @author   Route Protect team
 * @link     https://docs.route.com/docs/
 */
class InlineResponse2002 implements ModelInterface, ArrayAccess
{
    const DISCRIMINATOR = null;

    /**
     * The original name of the model.
     *
     * @var string
     */
    protected static $swaggerModelName = 'inline_response_200_2';

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @var string[]
     */
    protected static $swaggerTypes = [
        'id' => 'string',
        'created_on' => '\DateTime',
        'updated_on' => '\DateTime',
        'canceled_on' => '\DateTime',
        'deliver_status' => 'string',
        'tracking_registration_status' => 'string',
        'tracking_number' => 'string',
        'tracking_number_override' => 'string',
        'tracking_url' => 'string',
        'courier' => 'string',
        'expected_delivery_date' => '\DateTime',
        'merchant_id' => 'string'
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
        'canceled_on' => 'date-time',
        'deliver_status' => null,
        'tracking_registration_status' => null,
        'tracking_number' => null,
        'tracking_number_override' => null,
        'tracking_url' => null,
        'courier' => null,
        'expected_delivery_date' => 'date-time',
        'merchant_id' => null
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
        'canceled_on' => 'canceled_on',
        'deliver_status' => 'deliver_status',
        'tracking_registration_status' => 'tracking_registration_status',
        'tracking_number' => 'tracking_number',
        'tracking_number_override' => 'tracking_number_override',
        'tracking_url' => 'tracking_url',
        'courier' => 'courier',
        'expected_delivery_date' => 'expected_delivery_date',
        'merchant_id' => 'merchant_id'
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
        'canceled_on' => 'setCanceledOn',
        'deliver_status' => 'setDeliverStatus',
        'tracking_registration_status' => 'setTrackingRegistrationStatus',
        'tracking_number' => 'setTrackingNumber',
        'tracking_number_override' => 'setTrackingNumberOverride',
        'tracking_url' => 'setTrackingUrl',
        'courier' => 'setCourier',
        'expected_delivery_date' => 'setExpectedDeliveryDate',
        'merchant_id' => 'setMerchantId'
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
        'canceled_on' => 'getCanceledOn',
        'deliver_status' => 'getDeliverStatus',
        'tracking_registration_status' => 'getTrackingRegistrationStatus',
        'tracking_number' => 'getTrackingNumber',
        'tracking_number_override' => 'getTrackingNumberOverride',
        'tracking_url' => 'getTrackingUrl',
        'courier' => 'getCourier',
        'expected_delivery_date' => 'getExpectedDeliveryDate',
        'merchant_id' => 'getMerchantId',
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
        $this->container['canceled_on'] = isset($data['canceled_on']) ? $data['canceled_on'] : null;
        $this->container['deliver_status'] = isset($data['deliver_status']) ? $data['deliver_status'] : null;
        $this->container['tracking_registration_status'] = isset($data['tracking_registration_status']) ? $data['tracking_registration_status'] : null;
        $this->container['tracking_number'] = isset($data['tracking_number']) ? $data['tracking_number'] : null;
        $this->container['tracking_number_override'] = isset($data['tracking_number_override']) ? $data['tracking_number_override'] : null;
        $this->container['tracking_url'] = isset($data['tracking_url']) ? $data['tracking_url'] : null;
        $this->container['courier'] = isset($data['courier']) ? $data['courier'] : null;
        $this->container['expected_delivery_date'] = isset($data['expected_delivery_date']) ? $data['expected_delivery_date'] : null;
        $this->container['merchant_id'] = isset($data['merchant_id']) ? $data['merchant_id'] : null;
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
     * @param string $id A unique order id provided by the store.
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
     * @param \DateTime $created_on Date and time (ISO 8601 format) that shipment was created
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
     * @param \DateTime $updated_on Date and time (ISO 8601 format) of the last shipment update
     *
     * @return $this
     */
    public function setUpdatedOn($updated_on)
    {
        $this->container['updated_on'] = $updated_on;

        return $this;
    }

    /**
     * Gets canceled_on
     *
     * @return \DateTime
     */
    public function getCanceledOn()
    {
        return $this->container['canceled_on'];
    }

    /**
     * Sets canceled_on
     *
     * @param \DateTime $canceled_on Date and time (ISO 8601 format) that shipment was canceled
     *
     * @return $this
     */
    public function setCanceledOn($canceled_on)
    {
        $this->container['canceled_on'] = $canceled_on;

        return $this;
    }

    /**
     * Gets deliver_status
     *
     * @return string
     */
    public function getDeliverStatus()
    {
        return $this->container['deliver_status'];
    }

    /**
     * Sets deliver_status
     *
     * @param string $deliver_status The current status of the delivery process for the shipment
     *
     * @return $this
     */
    public function setDeliverStatus($deliver_status)
    {
        $this->container['deliver_status'] = $deliver_status;

        return $this;
    }

    /**
     * Gets tracking_registration_status
     *
     * @return string
     */
    public function getTrackingRegistrationStatus()
    {
        return $this->container['tracking_registration_status'];
    }

    /**
     * Sets tracking_registration_status
     *
     * @param string $tracking_registration_status The status of the tracking registration for the shipment
     *
     * @return $this
     */
    public function setTrackingRegistrationStatus($tracking_registration_status)
    {
        $this->container['tracking_registration_status'] = $tracking_registration_status;

        return $this;
    }

    /**
     * Gets tracking_number
     *
     * @return string
     */
    public function getTrackingNumber()
    {
        return $this->container['tracking_number'];
    }

    /**
     * Sets tracking_number
     *
     * @param string $tracking_number The tracking number for the shipment
     *
     * @return $this
     */
    public function setTrackingNumber($tracking_number)
    {
        $this->container['tracking_number'] = $tracking_number;

        return $this;
    }

    /**
     * Gets tracking_number_override
     *
     * @return string
     */
    public function getTrackingNumberOverride()
    {
        return $this->container['tracking_number_override'];
    }

    /**
     * Sets tracking_number_override
     *
     * @param string $tracking_number_override The tracking number override for the shipment
     *
     * @return $this
     */
    public function setTrackingNumberOverride($tracking_number_override)
    {
        $this->container['tracking_number_override'] = $tracking_number_override;

        return $this;
    }

    /**
     * Gets tracking_url
     *
     * @return string
     */
    public function getTrackingUrl()
    {
        return $this->container['tracking_url'];
    }

    /**
     * Sets tracking_url
     *
     * @param string $tracking_url The tracking url for the shipment
     *
     * @return $this
     */
    public function setTrackingUrl($tracking_url)
    {
        $this->container['tracking_url'] = $tracking_url;

        return $this;
    }

    /**
     * Gets courier
     *
     * @return string
     */
    public function getCourier()
    {
        return $this->container['courier'];
    }

    /**
     * Sets courier
     *
     * @param string $courier The courier ID for the shipment's courier
     *
     * @return $this
     */
    public function setCourier($courier)
    {
        $this->container['courier'] = $courier;

        return $this;
    }

    /**
     * Gets expected_delivery_date
     *
     * @return \DateTime
     */
    public function getExpectedDeliveryDate()
    {
        return $this->container['expected_delivery_date'];
    }

    /**
     * Sets expected_delivery_date
     *
     * @param \DateTime $expected_delivery_date Date and time (ISO 8601 format) of estimated arrival of shipment
     *
     * @return $this
     */
    public function setExpectedDeliveryDate($expected_delivery_date)
    {
        $this->container['expected_delivery_date'] = $expected_delivery_date;

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
     * Gets checkpoints
     *
     * @return array
     */
    public function getCheckpoints()
    {
        return $this->container['checkpoints'];
    }

    /**
     * Sets checkpoints
     *
     * @param array $checkpoints List of all shipment milestones
     *
     * @return $this
     */
    public function setCheckpoints($checkpoints)
    {
        $this->container['checkpoints'] = $checkpoints;

        return $this;
    }

    /**
     * Gets merchant_messages
     *
     * @return array
     */
    public function getMerchantMessages()
    {
        return $this->container['merchant_messages'];
    }

    /**
     * Sets merchant_messages
     *
     * @param array $merchant_messages List of messages from merchant pertaining shipment
     *
     * @return $this
     */
    public function setMerchantMessages($merchant_messages)
    {
        $this->container['merchant_messages'] = $merchant_messages;

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
