define([
    'Magento_Checkout/js/model/quote'
], function (quote) {
    'use strict';

    return function (shippingInformation) {
        return shippingInformation.extend({
            defaults: {
                template: 'Magento_Checkout/shipping-information'
            },

            /**
             * @returns {Boolean}
             */
            isStorePickup: function () {
                var shippingMethod = quote.shippingMethod();

                if (!shippingMethod) {
                    return false;
                }

                return shippingMethod['carrier_code'] === 'instore' &&
                    shippingMethod['method_code'] === 'pickup';
            },

            /**
             * @return {String}
             */
            getShippingMethodTitle: function () {
                if (this.isStorePickup && this.isStorePickup()) {
                    return this._super();
                }

                var shippingMethod = quote.shippingMethod(),
                    shippingMethodTitle = '';

                if (!shippingMethod) {
                    return '';
                }

                shippingMethodTitle = shippingMethod['carrier_title'];

                if (typeof shippingMethod['method_title'] !== 'undefined') {
                    shippingMethodTitle += ' - ' + shippingMethod['method_title'];
                }

                return shippingMethodTitle;
            }
        });
    };
});