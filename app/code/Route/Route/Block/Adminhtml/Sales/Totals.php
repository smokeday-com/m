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

namespace Route\Route\Block\Adminhtml\Sales;

use Magento\Directory\Model\Currency;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Route\Route\Helper\Data;

/**
 * Class responsible to add Route fee to total summary on
 * Order view page
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
    protected $currency;

    /**
     * Constructor
     *
     * @param Context  $context  Context variable
     * @param Data     $helper   Route helper
     * @param Currency $currency Currency
     * @param array    $data     Generic data
     */
    public function __construct(
        Context $context,
        Data $helper,
        Currency $currency,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper = $helper;
        $this->currency = $currency;
    }

    /**
     * Retrieve current order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->getParentBlock()->getOrder();
    }

    /**
     * Retrieve source order
     *
     * @return mixed
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }

    /**
     * Main method to add Route fee at totals summary block
     *
     * @return $this
     */
    public function initTotals()
    {
        $this->getParentBlock();
        $this->getOrder();
        $this->getSource();

        if (!$this->getSource()->getRouteFee()) {
            return $this;
        }

        $this->getParentBlock()->addTotalBefore(
            new DataObject(
                [
                    'code' => Data::ROUTE_FEE,
                    'value' => $this->getSource()->getRouteFee(),
                    'label' => $this->helper->getRouteLabel(),
                ]
            ),
            'grand_total'
        );

        return $this;
    }
}
