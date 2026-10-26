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
namespace Route\Route\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Route\Route\Helper\Setup;

class Login extends Action
{
    private $setupHelper;

    /**
     * User constructor.
     * @param Action\Context $context
     * @param Setup $setupHelper
     */
    public function __construct(
        Action\Context $context,
        Setup $setupHelper
    ) {
        $this->setupHelper = $setupHelper;
        parent::__construct($context);
    }

    public function execute()
    {
        $username = $this->getRequest()->getParam('username');
        $password = $this->getRequest()->getParam('password');
        $this->setupHelper->registerAccountLogin($username, $password);
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        return $resultRedirect;
    }
}
