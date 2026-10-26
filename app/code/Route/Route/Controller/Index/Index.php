<?php
/**
 * A Route Magento Extension that adds secure shipping
 * insurance to your orders
 *
 * Php version 7.0^
 *
 * @category  Controller
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
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\TotalsCollector;
use Magento\Quote\Model\QuoteRepository;

use Route\Route\Helper\Data;
use Route\Route\Helper\Setup;

/**
 * Main controller to receive and define the quote as insured or not
 *
 * Php version 7.0^
 *
 * @category  Controller
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Index extends Action
{
    protected $checkoutSession;
    protected $jsonFactory;
    protected $totalsCollector;
    protected $quoteRepository;
    protected $helper;
    protected $helperSetup;

    /**
     * Index constructor.
     *
     * @param Context         $context         Context variable
     * @param Session         $checkoutSession Current checkout session
     * @param JsonFactory     $jsonFactory     Json parser
     * @param TotalsCollector $totalsCollector Tax collector
     * @param QuoteRepository $quoteRepository Tax collector
     * @param Data             $helper          Route helper
     * @param Setup           $helperSetup     Route setup helper
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        JsonFactory $jsonFactory,
        TotalsCollector $totalsCollector,
        QuoteRepository $quoteRepository,
        Data $helper,
        Setup $helperSetup
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->totalsCollector = $totalsCollector;
        $this->jsonFactory = $jsonFactory;
        $this->quoteRepository = $quoteRepository;
        $this->helper = $helper;
        $this->helperSetup = $helperSetup;
        parent::__construct($context);
    }

    /**
     * Main method that receive the param to define if the quote is insured or not
     *
     * @return Json
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $routeInsurance = $this->getRequest()
            ->getParam('is_route_insured');

        $this->_getQuote()->setRouteIsInsured(
            filter_var($routeInsurance, FILTER_VALIDATE_BOOLEAN)
        );

        $resultJson = $this->jsonFactory->create();

        $this->_getQuote()->collectTotals();
        try {
            $this->quoteRepository->save($this->_getQuote());

            $this->helperSetup->setSetupCheckWidget($this->helperSetup::SETUP_CHECK_WIDGET_JS);

            return $resultJson->setData(
                [
                    'success' => true,
                    'subtotal' => $this->helper->getShippableItemsSubtotal($this->_getQuote()),
                    'currency' => $this->_getQuote()->getQuoteCurrencyCode()
                ]
            );

        } catch (\Exception $exception) {
            return $resultJson->setData(
                [
                    'success' => false
                ]
            );
        }
    }

    /**
     * Get current quote instance
     *
     * @return Quote
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function _getQuote()
    {
        return $this->checkoutSession->getQuote();
    }
}
