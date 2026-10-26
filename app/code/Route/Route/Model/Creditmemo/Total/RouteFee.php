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

namespace Route\Route\Model\Creditmemo\Total;

use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;

/**
 * Creditmemo collector
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
     * Collect credit memo subtotal taking in consideration Route Fee
     *
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo Creditmemo
     *                                                          to add Route Fee
     *
     * @return $this
     */
    public function collect(Creditmemo $creditmemo)
    {
        if ($this->isCompleteCreditmemo($creditmemo)) {
            $creditmemo->setRouteFee(0);

            $amount = $creditmemo->getOrder()->getRouteFee();
            $creditmemo->setRouteFee($amount);

            $creditmemo->setGrandTotal(
                $creditmemo->getGrandTotal() + $creditmemo->getRouteFee() + $creditmemo->getOrder()->getRouteTaxFee()
            );
            $creditmemo->setBaseGrandTotal(
                $creditmemo->getBaseGrandTotal() + $creditmemo->getRouteFee() + $creditmemo->getOrder()->getRouteTaxFee()
            );

            $creditmemo->setTaxAmount(
                $creditmemo->getTaxAmount() + $creditmemo->getOrder()->getRouteTaxFee()
            );
            $creditmemo->setBaseTaxAmount(
                $creditmemo->getBaseTaxAmount() + $creditmemo->getOrder()->getRouteTaxFee()
            );
        }
        return $this;
    }

    /**
     * @param $creditmemo
     * @return bool
     */
    private function isCompleteCreditmemo($creditmemo)
    {
        $orderItems = [];
        foreach ($creditmemo->getOrder()->getAllItems() as $item) {
            $orderItems[$item->getSku()] =  (int) $item->getQtyInvoiced() - (int) $item->getQtyRefunded();
        }
        foreach ($creditmemo->getAllItems() as $item) {
            if (isset($orderItems[$item->getSku()])) {
                if ($orderItems[$item->getSku()] != $item->getQty()) {
                    return false;
                }
            }
        }
        return true;
    }
}
