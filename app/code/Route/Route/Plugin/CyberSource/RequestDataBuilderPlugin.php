<?php

namespace Route\Route\Plugin\CyberSource;

use Magento\Checkout\Model\Session;
use Route\Route\Helper\Data;
use Route\Route\Model\Route\Quote as QuoteClient;

class RequestDataBuilderPlugin
{

    private $_helper;
    private $_quoteClient;
    private $_checkoutSession;

    public function __construct(
        Data $helper,
        QuoteClient $quoteClient,
        Session $checkoutSession

    ) {
        $this->_helper = $helper;
        $this->_quoteClient = $quoteClient;
        $this->_checkoutSession = $checkoutSession;
    }

    private function addRouteFeeToResult($result)
    {
        $quote = $this->_checkoutSession->getQuote();

        $quoteResponse = $this->_quoteClient->getQuoteV2($quote);

        if ($this->_helper->canAddRouteFee($quoteResponse, $quote)) {
            $id = $result['line_item_count'];

            $result['item_' . $id . '_name'] = preg_replace("/[^a-zA-Z0-9\s]/", "", Data::ROUTE_LABEL);
            $result['item_' . $id . '_sku'] = Data::ROUTE_FEE;
            $result['item_' . $id . '_quantity'] = 1;
            $result['item_' . $id . '_unit_price'] = $this->formatAmount($this->getCurrentQuote());
            $result['item_' . $id . '_tax_amount'] = $this->formatAmount(0);
            $result['item_' . $id . '_code'] = 'simple';

            $result['line_item_count'] += 1;
        }

        return $result;
    }

    public function afterFilterParams(\CyberSource\SecureAcceptance\Helper\RequestDataBuilder $subject, $result): array
    {
        return $this->addRouteFeeToResult($result);
    }

    private function getCurrentQuote()
    {
        $routeLine = $this->_helper->getRouteLine();
        return $routeLine['amount'];
    }

    private function formatAmount($amount): string
    {
        if (!is_float($amount)) {
            $amount = (float) $amount;
        }

        return number_format($amount, 2, '.', '');
    }
}
