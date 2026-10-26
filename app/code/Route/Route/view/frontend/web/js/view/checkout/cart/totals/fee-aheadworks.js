define([
    'ko',
    'jquery',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Magento_Catalog/js/price-utils',
    'Magento_Checkout/js/model/totals'

], function (ko, $, Component, quote, priceUtils, totals) {
    'use strict';
    let route_label = window.checkoutConfig.route_label;
    let route_fee_amount = window.checkoutConfig.route_fee_amount;
    return Component.extend({
        initialize: function() {
            this._super();
            this.totals = quote.getTotals();
            this.feeLabel = ko.observable(route_label);
            this.feeAmount = ko.observable(this.getFormattedPrice());
            
            // Recompute fee amount whenever totals
            this.totals.subscribe(this.updateFeeAmount, this);

            this.isDisplayed = ko.computed(function() {
                return this.getValue() != 0;
            }, this);
            
        },
        getFormattedPrice: function() {
            let price = 0;
            if (this.totals() && totals.getSegment('route_fee')) {
                price = totals.getSegment('route_fee').value;
            } else {
                price = route_fee_amount;
            }
            return priceUtils.formatPrice(price, quote.getPriceFormat());
        },
        getFeeLabel: function() {
            return this.feeLabel();
        },
        updateFeeAmount: function() {
            this.feeAmount(this.getFormattedPrice());
        },
        getValue: function() {
            let price = 0;
            if (this.totals() && totals.getSegment('route_fee')) {
                price = totals.getSegment('route_fee').value;
            }
            if (window.Route && window.Route.Protection) {
                if (!window.Route.Protection.status) {
                    return 0;
                }
            }
            return price;
        },
    });
});