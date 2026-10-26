<?php

namespace Route\Route\Model;

use Route\Route\Api\Data\RouteProtectionOutputInterface;

class RouteProtectionFactory
{
    /**
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager = null;

    /**
     * Instance name to create
     *
     * @var string
     */
    protected $_instanceName = null;

    /**
     * Factory constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param string $instanceName
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager, $instanceName = '\\Route\\Route\\Api\\Data\\RouteProtectionOutputData')
    {
        $this->_objectManager = $objectManager;
        $this->_instanceName = $instanceName;
    }

    /**
     * Create class instance with specified parameters
     *
     * @param array $data
     * @return \Magento\Quote\Model\Shipping
     */
    public function create(array $data = []): RouteProtectionOutputInterface
    {
        return $this->_objectManager->create($this->_instanceName, $data);
    }
}
