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

namespace Route\Route\Controller\Index;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Route\Route\Helper\Data;

/**
 * Action to show/hide Route widget
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
class CheckWidget extends Action
{
    private $_helper;
    private $_jsonFactory;
    private $_checkoutSession;

    /**
     * Status constructor.
     *
     * @param Context           $context        Current context object
     * @param Data              $helper         Route helper
     * @param JsonFactory       $jsonFactory    Json parser
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        Data $helper,
        JsonFactory $jsonFactory
    ) {
        $this->_helper = $helper;
        $this->_jsonFactory = $jsonFactory;
        $this->_checkoutSession = $checkoutSession;
        parent::__construct($context);
    }

    /**
     * Main method that receive the param to define if widget should be displayed
     *
     * @return Json
     */
    public function execute()
    {
        $showWidget = true;
        $shippingMethod = $this->getRequest()->getParam('shipping_method');
        $paymentMethod = $this->getRequest()->getParam('payment_method');

        if (strpos($shippingMethod, '_') === false) {
            $shippingMethod = $shippingMethod . '_' . $shippingMethod;
        }


        if (!$this->_helper->isRouteProtectionAllowed($shippingMethod, $paymentMethod)) {
            $showWidget = false;
        }

        $resultJson = $this->_jsonFactory->create();

        return $resultJson->setData(
            [
                'success' => true,
                'show_widget' => $showWidget,
                'is_insured' => $this->_checkoutSession->getRouteIsInsured()
            ]
        );
    }
}
