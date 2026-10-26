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
namespace Route\Route\Model\Quote\Total;

use Magento\Customer\Api\Data\AddressInterfaceFactory as CustomerAddressFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory as CustomerAddressRegionFactory;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\QuoteValidator;
use Magento\Tax\Api\Data\QuoteDetailsInterfaceFactory;
use Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory;
use Magento\Tax\Api\Data\TaxClassKeyInterface;
use Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory;
use Magento\Tax\Api\TaxCalculationInterface;
use Magento\Tax\Model\Config;
use Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector;
use Route\Route\Helper\Data;
use Route\Route\Model\Route\Quote as QuoteClient;
use Route\Route\Service\ApiException;
use Route\Route\Service\Model\QuoteOutput;

/**
 * Quote collector
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
class RouteFee extends CommonTaxCollector
{
    const ITEM_TYPE_FEE = 'fee';

    protected $helperData;
    protected $priceCurrency;
    protected $quoteValidator;
    protected $quoteClient;

    /**
     * RouteFee constructor.
     *
     * @param Config                           $taxConfig,
     * @param TaxCalculationInterface          $taxCalculationService,
     * @param QuoteDetailsInterfaceFactory     $quoteDetailsDataObjectFactory,
     * @param QuoteDetailsItemInterfaceFactory $quoteDetailsItemDataObjectFactory,
     * @param TaxClassKeyInterfaceFactory      $taxClassKeyDataObjectFactory,
     * @param CustomerAddressFactory           $customerAddressFactory,
     * @param CustomerAddressRegionFactory     $customerAddressRegionFactory,
     * @param QuoteValidator                   $quoteValidator quote validator
     * @param PriceCurrencyInterface           $priceCurrency  currency handler
     * @param Data                             $helperData     Route helper
     * @param QuoteClient                      $quoteClient    Client to Route Quote API
     */
    public function __construct(
        Config $taxConfig,
        TaxCalculationInterface $taxCalculationService,
        QuoteDetailsInterfaceFactory $quoteDetailsDataObjectFactory,
        QuoteDetailsItemInterfaceFactory $quoteDetailsItemDataObjectFactory,
        TaxClassKeyInterfaceFactory $taxClassKeyDataObjectFactory,
        CustomerAddressFactory $customerAddressFactory,
        CustomerAddressRegionFactory $customerAddressRegionFactory,
        QuoteValidator $quoteValidator,
        PriceCurrencyInterface $priceCurrency,
        Data $helperData,
        QuoteClient $quoteClient
    ) {
        $this->quoteValidator = $quoteValidator;
        $this->priceCurrency = $priceCurrency;
        $this->helperData = $helperData;
        $this->quoteClient = $quoteClient;
        parent::__construct(
            $taxConfig,
            $taxCalculationService,
            $quoteDetailsDataObjectFactory,
            $quoteDetailsItemDataObjectFactory,
            $taxClassKeyDataObjectFactory,
            $customerAddressFactory,
            $customerAddressRegionFactory
        );
    }

    /**
     * Collect grand total address amount
     *
     * @param Quote                       $quote              Quote to add
     *                                                        Route Fee
     * @param ShippingAssignmentInterface $shippingAssignment Shipping Address
     * @param Total                       $total              Total object to
     *                                                        add Route Fee
     *
     * @return $this
     */
    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ) {

        parent::collect($quote, $shippingAssignment, $total);

        if ($quote->getIsMultiShipping()) {
            return $this;
        }

        $total->setRouteFee(0);

        if (!count($shippingAssignment->getItems())) {
            return $this;
        }

        $routeFee = 0;

        $quoteResponse = $this->quoteClient->getQuoteV2($quote);

        if ($this->helperData->canAddRouteFee($quoteResponse, $quote)) {
            try {
                $routeFee = $quoteResponse->getPremium()->getAmount();
                $this->processTax($quote, $shippingAssignment, $total, $routeFee);
            } catch (ApiException $e) {
                return $this;
            }
        }

        $quote->setRouteIsInsured($this->helperData->isInsured($quoteResponse, $quote) );
        $total->setTotalAmount(Data::ROUTE_FEE, $routeFee);
        $total->setBaseTotalAmount(Data::ROUTE_FEE, $routeFee);
        $quote->setRouteFee($routeFee);
        $total->setRouteFee($routeFee);

        return $this;
    }

    /**
     * @param QuoteDetailsItemInterfaceFactory $itemDataObjectFactory
     * @param $routeFee
     * @return array
     */
    public function mapFee(
        QuoteDetailsItemInterfaceFactory $itemDataObjectFactory,
        $routeFee
    ) {
        $itemDataObject = $itemDataObjectFactory->create();
        $itemDataObject->setCode($this->helperData->getTaxClassId())
            ->setQuantity(1)
            ->setTaxClassKey(
                $this->taxClassKeyDataObjectFactory->create()
                    ->setType(TaxClassKeyInterface::TYPE_ID)
                    ->setValue($this->helperData->getTaxClassId())
            )
            ->setIsTaxIncluded(false)
            ->setType(self::ITEM_TYPE_FEE);

        $itemDataObject->setUnitPrice($routeFee)
                ->setDiscountAmount(0);

        return [$itemDataObject];
    }

    /**
     * Fetch (Retrieve data as array)
     *
     * @param Quote $quote current quote object
     * @param Total $total totals
     *
     * @return array
     */
    public function fetch(Quote $quote, Total $total)
    {
        $quoteResponse = $this->quoteClient->getQuoteV2($quote);

        $result = [];
        if ($this->helperData->canAddRouteFee($quoteResponse, $quote)) {
            $result = [
                'code' => Data::ROUTE_FEE,
                'title' => $this->helperData->getRouteLabel(),
                'value' => $quoteResponse->getPremium()->getAmount()
            ];
        }

        return $result;
    }

    /**
     * Get Subtotal label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->helperData->getRouteLabel();
    }

    /**
     * Process tax if it's enabled
     *
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @param $routeFee
     */
    protected function processTax(Quote $quote, ShippingAssignmentInterface $shippingAssignment, Total $total, $routeFee)
    {
        if ($this->helperData->isTaxable()) {

            $itemDataObjectFactory = $this->quoteDetailsItemDataObjectFactory;
            $itemDataObjects = $this->mapFee($itemDataObjectFactory, $routeFee);

            $quoteDetails = $this->prepareQuoteDetails($shippingAssignment, $itemDataObjects);

            $taxDetails = $this->taxCalculationService
                ->calculateTax($quoteDetails, $quote->getStore()->getStoreId());

            $itemsByType = $this->organizeItemTaxDetailsByType($taxDetails, $taxDetails);

            $this->processFeeTaxable($total, $itemsByType, $quote);
            $this->processAppliedTaxes($total, $shippingAssignment, $itemsByType);

        }
    }

    /**
     * Process tax fee by Row
     *
     * @param Total $total
     * @param array $itemsByType
     * @param Quote $quote
     * @return $this
     */
    protected function processFeeTaxable(Total $total, array $itemsByType, $quote)
    {
        $extraTaxableDetails = [];
        foreach ($itemsByType as $itemType => $itemTaxDetails) {
            if ($itemType == self::ITEM_TYPE_FEE) {
                foreach ($itemTaxDetails as $itemCode => $itemTaxDetail) {

                    $taxDetails = $itemTaxDetail[self::KEY_ITEM];
                    $baseTaxDetails = $itemTaxDetail[self::KEY_BASE_ITEM];

                    $appliedTaxes = $taxDetails->getAppliedTaxes();
                    $baseAppliedTaxes = $baseTaxDetails->getAppliedTaxes();

                    $associatedItemCode = $taxDetails->getAssociatedItemCode();

                    $appliedTaxesArray = $this->convertAppliedTaxes($appliedTaxes, $baseAppliedTaxes);
                    $extraTaxableDetails[$itemType][$associatedItemCode][] = [
                        self::KEY_TAX_DETAILS_TYPE => $taxDetails->getType(),
                        self::KEY_TAX_DETAILS_CODE => $taxDetails->getCode(),
                        self::KEY_TAX_DETAILS_PRICE_EXCL_TAX => $taxDetails->getPrice(),
                        self::KEY_TAX_DETAILS_PRICE_INCL_TAX => $taxDetails->getPriceInclTax(),
                        self::KEY_TAX_DETAILS_BASE_PRICE_EXCL_TAX => $baseTaxDetails->getPrice(),
                        self::KEY_TAX_DETAILS_BASE_PRICE_INCL_TAX => $baseTaxDetails->getPriceInclTax(),
                        self::KEY_TAX_DETAILS_ROW_TOTAL => $taxDetails->getRowTotal(),
                        self::KEY_TAX_DETAILS_ROW_TOTAL_INCL_TAX => $taxDetails->getRowTotalInclTax(),
                        self::KEY_TAX_DETAILS_BASE_ROW_TOTAL => $baseTaxDetails->getRowTotal(),
                        self::KEY_TAX_DETAILS_BASE_ROW_TOTAL_INCL_TAX => $baseTaxDetails->getRowTotalInclTax(),
                        self::KEY_TAX_DETAILS_TAX_PERCENT => $taxDetails->getTaxPercent(),
                        self::KEY_TAX_DETAILS_ROW_TAX => $taxDetails->getRowTax(),
                        self::KEY_TAX_DETAILS_BASE_ROW_TAX => $baseTaxDetails->getRowTax(),
                        self::KEY_TAX_DETAILS_APPLIED_TAXES => $appliedTaxesArray,
                    ];

                    $total->addTotalAmount('tax', $taxDetails->getRowTax());
                    $total->addBaseTotalAmount('tax', $baseTaxDetails->getRowTax());
                    $quote->setRouteTaxFee($taxDetails->getRowTax());
                    $total->setRouteTaxFee($taxDetails->getRowTax());

                }
            }
        }

        $total->setExtraTaxableDetails($extraTaxableDetails);
        return $this;
    }
}
