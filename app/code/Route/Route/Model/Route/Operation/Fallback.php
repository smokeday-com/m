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

namespace Route\Route\Model\Route\Operation;

use \Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Model\AbstractModel;
use Route\Route\Model\Route\Order;
use Route\Route\Model\Route\Shipment;

/**
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
class Fallback extends AbstractModel
{
    const STATUS_PENDING = 0;
    const STATUS_DONE = 1;
    const STATUS_EXCEED_LIMIT = 2;
    const RETRY_LIMIT = 3;

    private $order;
    private $shipment;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        FallbackOrder $order,
        FallbackShipment $shipment,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->order = $order;
        $this->shipment = $shipment;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    public function _construct()
    {
        $this->_init('Route\Route\Model\ResourceModel\Fallback');
    }

    public function exceedRetryLimit()
    {
        return $this->getRetry() >= self::RETRY_LIMIT;
    }

    public function isPending()
    {
        return $this->getStatus() == self::STATUS_PENDING;
    }

    public function beforeSave()
    {
        if (empty($this->getId())) {
            $this->setCreatedAt(new \DateTime());
            $this->setRetry(0);
        }
        $this->setUpdatedAt(new \DateTime());

        return parent::beforeSave();
    }

    /**
     * Try to rerun an operation that failed
     *
     * @return void
     */
    public function retry()
    {
        if ($this->exceedRetryLimit()) {
            $this->setStatus(self::STATUS_EXCEED_LIMIT);
        } else {
            $operation = $this->getOperationInstance();

            $operationObj = $operation->getOperationObject();
            $operationObj->setRetrying();

            try {
                if (method_exists($operationObj, $this->getOperation())) {
                    $response = call_user_func_array(
                        [
                            $operationObj, $this->getOperation()
                        ],
                        $operation->getParams()
                    );

                    if ($response) {
                        $this->setStatus(self::STATUS_DONE);
                    }
                }
            } catch (NotFoundException $e) {
                $this->_logger->error(
                    "Error trying to rerun operation: " .
                    $this->getOperation() . " Entity ID " . $this->getEntityId() . ' not found',
                    $e->getTrace()
                );
            } catch (\Exception $exception) {
                $this->_logger->error(
                    "Error trying to rerun operation: " .
                    $this->getOperation() . " to Entity ID " . $this->getEntityId() .
                    'Error: ' . $exception->getMessage(),
                    $exception->getTrace()
                );
            }

            $this->setRetry($this->getRetry() + 1);
        }
    }

    /**
     * Return Fallback Operation instance
     *
     * @return OperationFallback
     */
    public function getOperationInstance()
    {
        switch ($this->getType()) {
            case Order::API_ENDPOINT:
                return $this->order->setFallback($this);
            case Shipment::API_ENDPOINT:
                return $this->shipment->setFallback($this);
            default:
                return;
        }
    }

    public function save()
    {
        if ($this->_getResource()->isUnique($this)) {
            return parent::save();
        }
        return $this;
    }
}
