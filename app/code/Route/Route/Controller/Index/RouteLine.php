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

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Route\Route\Helper\Data;

/**
 * Route Line to layout Incompatibility
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
class RouteLine extends Action
{
    private $_helper;
    private $_resultRawFactory;

    /**
     * Route Line constructor.
     *
     * @param Context           $context        Current context object
     * @param Data              $helper         Route helper
     */
    public function __construct(
        Context $context,
        Data $helper,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
    ) {
        $this->_helper = $helper;
        $this->_resultRawFactory = $resultRawFactory;
        parent::__construct($context);
    }

    /**
     * Main method to execute Action
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function execute()
    {

        $format = $this->getRequest()->getParam('format');

        if (!in_array($format, ["", "json", "html"])) {
            throw new \Exception("Invalid format");
        }
        $result = $this->_resultRawFactory->create();
        $result->setContents($this->_helper->getRouteLine($format));
        return $result;
    }
}
