define([
    'ko',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Magento_Catalog/js/price-utils',
    'Magento_Checkout/js/model/totals'

], function (ko, Component, quote, priceUtils, totals) {
    'use strict';
    let route_label = window.checkoutConfig.route_label;
    let route_fee_amount = window.checkoutConfig.route_fee_amount;

    return Component.extend({

        totals: quote.getTotals(),
        getFormattedPrice: function() {
            let price = 0;
            if (this.totals() && totals.getSegment('route_fee')) {
                price = totals.getSegment('route_fee').value;
            }else{
                price = route_fee_amount;
            }
            return priceUtils.formatPrice(price, quote.getPriceFormat());
        },
        getFeeLabel:ko.observable(route_label),
        isDisplayed: function () {
            return this.getValue() != 0;
        },
        getValue: function() {
            let price = 0;
            if (this.totals() && totals.getSegment('route_fee')) {
                price = totals.getSegment('route_fee').value;
            }
            return price;
        }
    });
});
