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
namespace Route\Route\Plugin\Paypal;

use Magento\Checkout\Model\Session;
use Magento\Paypal\Model\Cart as PaypalCart;
use Route\Route\Helper\Data;
use Route\Route\Model\Route\Quote as QuoteClient;
use Route\Route\Service\ApiException;

/**
 * Rearrange totals to avoid Paypal Rejection
 *
 * Php version 7.0^
 *
 * @category  plugin
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Cart
{
    private $_helper;
    private $_quoteClient;
    private $_checkoutSession;

    public function __construct(
        Data $helper,
        QuoteClient $quoteClient,
        Session $checkoutSession,
    ) {


        $this->_helper = $helper;
        $this->_quoteClient = $quoteClient;
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     *
     */
    public function afterGetAmounts(PaypalCart $subject, array $result)
    {
        try {

            $quoteResponse = $this->_quoteClient->getQuoteV2($this->_checkoutSession->getQuote());

            if ($this->_helper->canAddRouteFee($quoteResponse, $this->_checkoutSession->getQuote())) {
                $result[PaypalCart::AMOUNT_SHIPPING] += $quoteResponse->getPremium()->getAmount();
            }

        }catch (ApiException $e){

        }

        return $result;

    }

}
