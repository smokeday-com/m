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
use Route\Route\Observer\ResendOrders;

/**
 * Action to get module status and version
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
class Config extends Action
{
    private $_helper;
    private $_jsonFactory;
    private $_currentSession;

    /**
     * Status constructor.
     *
     * @param Context           $context        Current context object
     * @param Data              $helper         Route helper
     * @param JsonFactory       $jsonFactory    Json parser
     * @param Session           $currentSession Current Session
     */
    public function __construct(
        Context $context,
        Data $helper,
        JsonFactory $jsonFactory,
        Session $currentSession
    ) {
        $this->_helper = $helper;
        $this->_jsonFactory = $jsonFactory;
        $this->_currentSession = $currentSession;
        parent::__construct($context);
    }

    /**
     * Main method to execute Action
     *
     * @return Json
     *
     * @throws \Exception
     */
    public function execute()
    {
        $message = '';
        $secretToken = $this->getRequest()->getParam('secret_token');
        $secretToken = isset($secretToken) ? $secretToken : false;
        $action = $this->getRequest()->getParam('action');
        $action  = isset($action) ? $this->getRequest()->getParam('action') : false;
        if ($secretToken != $this->_helper->getMerchantSecretToken() || !$action) {
            return;
        }

        switch ($action) {
            case 'modify_last_processed_order':
                    $orderProcessId = $this->getRequest()->getParam(ResendOrders::LAST_PROCESSED_ORDER);
                    $orderProcessId = isset($orderProcessId) ?
                        filter_var($orderProcessId, FILTER_SANITIZE_NUMBER_INT) :
                        false;
                    $this->_helper->saveConfig(ResendOrders::LAST_PROCESSED_ORDER, $orderProcessId);
                    $message = "Last Process Order: " . $orderProcessId;
                break;
        }

        $content = [
            'message' => $message
        ];

        $resultJson = $this->_jsonFactory->create();

        return $resultJson->setData($content);
    }
}
