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

namespace Route\Route\Block\Sales\Totals;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order;
use Route\Route\Helper\Data;

/**
 * Add Route Fee to order visualization at Frontend
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
class RouteFee extends Template
{

    protected $helper;
    protected $source;

    /**
     * RouteFee constructor
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
     * Check if we need display full tax total info
     *
     * @return bool
     */
    public function displayFullSummary()
    {
        return true;
    }

    /**
     * Retrieve source order
     *
     * @return mixed
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Main method to add Route fee at totals summary block
     *
     * @return $this
     */
    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $this->source = $parent->getSource();

        if ((float)$this->source->getRouteFee() > 0) {
            $routeFee = new DataObject(
                [
                    'code' => Data::ROUTE_FEE,
                    'strong' => false,
                    'value' => $this->source->getRouteFee(),
                    'label' => $this->helper->getRouteLabel(),
                ]
            );
            $parent->addTotal($routeFee, Data::ROUTE_FEE);
        }

        return $this;
    }
}
