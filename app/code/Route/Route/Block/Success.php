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

namespace Route\Route\Block;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Checkout\Model\Session;
use Magento\Widget\Block\BlockInterface;
use Route\Route\Helper\Data;
use Route\Route\Model\Route\Widget;

/**
 * Route thank you page asset
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
class Success extends Template implements BlockInterface
{
    protected $_template = "success.phtml";
    protected $helper;
    protected $widgetModel;
    protected $checkoutSession;

    /**
     * Widget constructor.
     *
     * @param Template\Context $context         Context variable
     * @param Data             $helper          Route helper
     * @param Widget           $widgetModel     Route widget Model
     * @param Session          $checkoutSession Checkout Session
     * @param array            $data            Generic data
     */
    public function __construct(
        Template\Context $context,
        Data $helper,
        Widget $widgetModel,
        Session $checkoutSession,
        array $data
    ) {
        $this->helper = $helper;
        $this->widgetModel = $widgetModel;
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context, $data);
    }

    public function isIncludesOrderThankYouPageWidget()
    {
        return $this->helper->isIncludesOrderThankYouPageWidget();
    }

    public function getThankYouPageWidget()
    {
        $storeDomain = $this->helper->getCurrentStoreDomain();
        $order = $this->checkoutSession->getLastRealOrder();
        $response = $this->widgetModel->getThankYouPageWidget($storeDomain);

        if (is_array($response)) {
            $response['orderId'] = $order->getId();
        } else {
            $response = ['orderId' => $order->getId()];
        }

        return $response;
    }
}
