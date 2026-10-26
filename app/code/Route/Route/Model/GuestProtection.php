<?php

namespace Route\Route\Model;

use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\QuoteRepository;
use Route\Route\Api\Data\RouteProtectionDataInterface;
use Route\Route\Api\ProtectionInterface;
use Route\Route\Helper\Data;
use Route\Route\Helper\Setup;

class GuestProtection implements ProtectionInterface
{

    private QuoteRepository $quoteRepository;
    private Data $helper;
    private Setup $helperSetup;
    private QuoteIdMaskFactory $quoteIdMaskFactory;
    private JsonFactory $jsonFactory;

    public function __construct(
        JsonFactory $jsonFactory,
        QuoteRepository $quoteRepository,
        Data $helper,
        Setup $helperSetup,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        RouteProtectionFactory $routeProtectionFactory
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->helper = $helper;
        $this->helperSetup = $helperSetup;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->jsonFactory = $jsonFactory;
        $this->routeProtectionFactory = $routeProtectionFactory;
    }

    public function protect(RouteProtectionDataInterface $protectionData)
    {
        try {
            $quoteIdMask = $this->quoteIdMaskFactory->create()->load($protectionData->getQuoteId(), 'masked_id');
            $quote = $this->quoteRepository->get($quoteIdMask->getQuoteId());
        } catch (NoSuchEntityException $e) {
            return $this->routeProtectionFactory->create()
                ->setSuccess(false)
                ->setRouteFee(0)
                ->setCurrency('');
        }

        $quote->setRouteIsInsured(
            filter_var($protectionData->getIsProtected(), FILTER_VALIDATE_BOOLEAN)
        );

        $quote->collectTotals();

        try {
            $this->quoteRepository->save($quote);

            $this->helperSetup->setSetupCheckWidget($this->helperSetup::SETUP_CHECK_WIDGET_JS);

            return $this->routeProtectionFactory->create()
                ->setSuccess(true)
                ->setRouteFee($quote->getRouteFee())
                ->setCurrency($quote->getQuoteCurrencyCode());

        } catch (\Exception $exception) {
            $this->routeProtectionFactory->create()
                ->setSuccess(false)
                ->setRouteFee(0)
                ->setCurrency($quote->getQuoteCurrencyCode());
        }
    }
}
