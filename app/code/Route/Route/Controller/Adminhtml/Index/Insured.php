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
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Backend\Model\Session\Quote;
use Magento\Quote\Model\QuoteRepository;
use Route\Route\Helper\Data;

class Insured extends Action
{
    protected $checkoutSession;
    protected $jsonFactory;
    protected $quoteRepository;
    protected $helper;

    /**
     * Index constructor.
     *
     * @param Context         $context         Context variable
     * @param Quote           $checkoutSession Admin quote session
     * @param JsonFactory     $jsonFactory     Json parser
     * @param QuoteRepository $quoteRepository Tax collector
     * @param Data             $helper          Route helper
     */
    public function __construct(
        Context $context,
        Quote $checkoutSession,
        JsonFactory $jsonFactory,
        QuoteRepository $quoteRepository,
        Data $helper
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->jsonFactory = $jsonFactory;
        $this->quoteRepository = $quoteRepository;
        $this->helper = $helper;
        parent::__construct($context);
    }

    /**
     * Main method that receive the param to define if the quote is insured or not
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $routeInsurance = $this->getRequest()->getParam('is_route_insured');

        $this->checkoutSession->setRouteIsInsured(
            filter_var($routeInsurance, FILTER_VALIDATE_BOOLEAN)
        );

        $resultJson = $this->jsonFactory->create();

        $this->_getQuote()->collectTotals();
        $this->quoteRepository->save($this->_getQuote());

        return $resultJson->setData(
            [
                'success' => true,
                'subtotal' => $this->helper->getShippableItemsSubtotal($this->_getQuote()),
                'currency' => $this->_getQuote()->getQuoteCurrencyCode()
            ]
        );
    }

    /**
     * Get current quote instance
     *
     * @return \Magento\Quote\Model\Quote
     */
    private function _getQuote()
    {
        return $this->checkoutSession->getQuote();
    }
}
