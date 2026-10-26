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

namespace Route\Route\Model\Invoice\Total;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;

/**
 * Invoice collector
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
class RouteFee extends AbstractTotal
{
    /**
     * Collect Invoice subtotal taking in consideration Route Fee
     *
     * @param \Magento\Sales\Model\Order\Invoice $invoice Invoice to add Route Fee
     *
     * @return $this
     */
    public function collect(Invoice $invoice)
    {
        $invoice->setRouteFee(0);

        $amount = $invoice->getOrder()->getRouteFee();
        $invoice->setRouteFee($amount);

        $invoice->setGrandTotal(
            $invoice->getGrandTotal() + $invoice->getRouteFee() + $invoice->getOrder()->getRouteTaxFee()
        );
        $invoice->setBaseGrandTotal(
            $invoice->getBaseGrandTotal() + $invoice->getRouteFee() + $invoice->getOrder()->getRouteTaxFee()
        );

        $invoice->setTaxAmount(
            $invoice->getTaxAmount() + $invoice->getOrder()->getRouteTaxFee()
        );
        $invoice->setBaseTaxAmount(
            $invoice->getBaseTaxAmount() + $invoice->getOrder()->getRouteTaxFee()
        );

        return $this;
    }
}
