<?php
namespace Route\Route\Plugin\Bolt;


use Bolt\Boltpay\Model\Api\RouteInsuranceManagement;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\QuoteRepository;
use Route\Route\Helper\Data;

class RouteInsuranceManagementPlugin
{

    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    private $helper;

    public function __construct(
        QuoteRepository $quoteRepository,
        CheckoutSession $checkoutSession,
        Data $helper
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->checkoutSession = $checkoutSession;
        $this->helper = $helper;
    }

    public function aroundExecute(
        RouteInsuranceManagement $subject,
        callable $proceed,
        int $cartId,
        bool $isInsured
    ) {
        $quote = $this->quoteRepository->get($cartId);
        $quote->setRouteIsInsured($isInsured);
        $this->quoteRepository->save($quote);
        return $proceed($cartId, $isInsured);
    }
}
