<?php
/**
 * A Route Magento Extension that adds secure shipping
 * insurance to your orders
 *
 * Php version 7.0^
 *
 * @category  Block
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
namespace Route\Route\Block\Adminhtml\Sales\Order\Creditmemo;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\DataObject;
use Magento\Sales\Model\Order;
use Route\Route\Helper\Data;

/**
 * Class responsible to add Route fee to total summary on
 * Creditmemo creation and visualization
 *
 * @category  Block
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Totals extends \Magento\Framework\View\Element\Template
{
    protected $helper;

    /**
     * Constructor
     *
     * @param Context $context    Context variable
     * @param Data    $dataHelper Route helper
     * @param array   $data       Generic data
     */
    public function __construct(
        Context $context,
        Data $dataHelper,
        array $data = []
    ) {
        $this->helper = $dataHelper;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve source order
     *
     * @return Order
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }

    /**
     * Retrieve Creditmemo object
     *
     * @return mixed
     */
    public function getCreditmemo()
    {
        return $this->getParentBlock()->getCreditmemo();
    }

    /**
     * Main method to add Route fee at totals summary block
     *
     * @return $this
     */
    public function initTotals()
    {
        $this->getParentBlock();
        $this->getCreditmemo();
        $this->getSource();

        if (!$this->getSource()->getRouteFee()) {
            return $this;
        }
        $routeFee = new DataObject(
            [
                'code' => Data::ROUTE_FEE,
                'strong' => false,
                'value' => $this->getSource()->getRouteFee(),
                'label' => $this->helper->getRouteLabel(),
            ]
        );

        $this->getParentBlock()->addTotalBefore($routeFee, 'grand_total');

        return $this;
    }
}
