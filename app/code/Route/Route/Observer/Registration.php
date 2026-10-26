<?php
/**
 * A Route Magento Extension that adds secure shipping
 * insurance to your orders
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

namespace Route\Route\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Route\Route\Logger\Sentry;
use Route\Route\Helper\Setup;

/**
 * Registration
 *
 * Php version 7.0^
 *
 * @category  Observer
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Registration implements ObserverInterface
{
    private $helper;
    private $logger;

    /**
     * Registration constructor.
     *
     * @param Setup           $helper
     * @param Sentry $logger
     */
    public function __construct(
        Setup $helper,
        Sentry $logger
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        $this->logger->debug("Check if registration is necessary");
        if ($this->helper->isFreshNewInstallation()) {
            $this->logger->debug("Fresh new installation - trying to auto setup");
            $this->helper->registerAccountToFreshNewInstallation();
            return;
        }
        $this->logger->debug("Registration not necessary");
        return $this;
    }
}
