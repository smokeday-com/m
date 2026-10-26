<?php
namespace Mage4\ShowPriceOSP\Plugin;

class ShowPrice
{
    public function afterResolvePrice(
        \Magento\ConfigurableProduct\Pricing\Price\ConfigurablePriceResolver $subject,
        $price,
        $product
    ) {
        $price = $price ?: $product->getData('price');

        return $price;
    }
}
