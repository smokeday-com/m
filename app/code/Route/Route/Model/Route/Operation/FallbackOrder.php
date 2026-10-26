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

use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Phrase;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Route\Route\Model\Route\Fallbackable;
use Route\Route\Model\Route\Order as OrderClient;

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
class FallbackOrder implements OperationFallback
{
    protected $orderClient;
    protected $fallback;
    protected $order;
    protected $orderRepository;

    public function __construct(
        OrderClient $orderClient,
        Order $order,
        OrderRepository $orderRepository
    ) {
        $this->orderClient = $orderClient;
        $this->orderRepository = $orderRepository;
        $this->order = $order;
    }

    public function getEntity()
    {
        $entity = $this->order->loadByIncrementId($this->fallback->getEntityId());
        if ($entity->getId() > 0) {
            return$entity;
        }
        throw new NotFoundException(new Phrase('Entity not Found'));
    }

    public function getOperationObject() : Fallbackable
    {
        return $this->orderClient;
    }

    public function getParams()
    {
        return [$this->getEntity(), !!$this->getEntity()->getRouteFee() > 0];
    }

    public function setFallback(Fallback $fallback) : OperationFallback
    {
        $this->fallback = $fallback;
        return $this;
    }
}
