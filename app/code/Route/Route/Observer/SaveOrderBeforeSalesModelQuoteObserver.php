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

use Magento\Checkout\Model\Session;
use Magento\Framework\DataObject\Copy;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Route\Route\Helper\Data;
use Route\Route\Model\Route\Order as OrderClient;

/**
 * Observer to order place event
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
class SaveOrderBeforeSalesModelQuoteObserver implements ObserverInterface
{
    protected $objectCopyService;
    protected $orderClient;
    protected $checkoutSession;
    protected $helperData;

    /**
     * Observer constructor
     *
     * @param Copy        $objectCopyService Copy service object
     * @param OrderClient $orderClient       Order client object
     * @param Session     $checkoutSession   current checkout session
     * @param Data        $helperData        Route Helper
     */
    public function __construct(
        Copy $objectCopyService,
        OrderClient $orderClient,
        Session $checkoutSession,
        Data $helperData
    ) {
        $this->orderClient = $orderClient;
        $this->objectCopyService = $objectCopyService;
        $this->checkoutSession = $checkoutSession;
        $this->helperData = $helperData;
    }

    /**
     * Main method executed on order place event is triggered
     *
     * @param Observer $observer observer object
     *
     * @return ObserverInterface
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getData('order');
        $quote = $observer->getEvent()->getData('quote');

        $this->objectCopyService->copyFieldsetToTarget(
            'sales_convert_quote',
            'to_order',
            $quote,
            $order
        );

        $order->setRouteFee($quote->getRouteFee());
        $order->setRouteIsInsured($quote->getRouteIsInsured());
        $order->setRouteTaxFee($quote->getRouteTaxFee());

        return $this;
    }
}
