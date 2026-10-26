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
namespace Route\Route\Block\Adminhtml\Sales\Order\Invoice;

use Route\Route\Helper\Data;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use Route\Route\Model\Route\Order;

/**
 * Class responsible to add Route fee to total summary on
 * Invoice creation and visualization
 * Php version 7.0^
 *
 * @category  Block
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Totals extends Template
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
     * Retrieve Invoice object
     *
     * @return mixed
     */
    public function getInvoice()
    {
        return $this->getParentBlock()->getInvoice();
    }

    /**
     * Main method to add Route fee at totals summary block
     *
     * @return $this
     */
    public function initTotals()
    {
        $this->getParentBlock();
        $this->getInvoice();
        $this->getSource();

        if (!$this->getSource()->getRouteFee()) {
            return $this;
        }

        $total = new DataObject(
            [
                'code' => Data::ROUTE_FEE,
                'value' => $this->getSource()->getRouteFee(),
                'label' => $this->helper->getRouteLabel(),
            ]
        );

        $this->getParentBlock()->addTotalBefore($total, 'grand_total');

        return $this;
    }
}
