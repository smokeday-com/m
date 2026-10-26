<?php

namespace Route\Route\Plugin\CyberSource;

use Magento\Checkout\Model\Session;
use Route\Route\Helper\Data;
use Route\Route\Model\Route\Quote as QuoteClient;

class ItemsDataBuilderPlugin
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

    private function addRouteFeeToResult($result): array
    {
        $quote = $this->_checkoutSession->getQuote();

        $quoteResponse = $this->_quoteClient->getQuoteV2($quote);

        if ($this->_helper->canAddRouteFee($quoteResponse, $quote)) {

            $id = count($result['item']);
            $result['item'][] = [
                'id' => $id,
                'productName' => preg_replace("/[^a-zA-Z0-9\s]/", "", Data::ROUTE_LABEL),
                'productSKU' => Data::ROUTE_FEE,
                'quantity' => 1,
                'unitPrice' => $this->formatAmount($this->getCurrentQuote()),
                'taxAmount' => $this->formatAmount(0),
                'productCode'=> 'simple'
            ];
        }

        return $result;
    }

    public function afterBuild(\CyberSource\SecureAcceptance\Gateway\Request\Soap\ItemsDataBuilder $subject, $result): array
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
