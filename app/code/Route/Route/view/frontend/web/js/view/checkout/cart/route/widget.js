define([
    'uiComponent',
    'ko',
    'jquery',
    'Magento_Checkout/js/model/quote',
    'Route_Route/js/view/checkout/cart/route/route-helper',
    'Route_Route/js/view/checkout/cart/route/widget-helper',
], function (Component, ko, $, quote, routeHelper, widgetHelper) {
    'use strict';

    return Component.extend({
        defaults: {
            template: checkoutConfig.template
        },
        config: {
            isEnabled: () => routeHelper.isEnabled(),
            renderWidgetId: () => 'RouteWidget',
            renderWidgetClassName: () => 'route-div',
            renderWidgetTitleId: () => 'RouteWidgetTitle'
        },
        initialize: function () {
            this._super();
            if (routeHelper.isEnabled()) {
                this.initWidget();
            }
        },
        initWidget: function () {
            widgetHelper.renderWidget();
            this.setupSubscriptions();
        },
        setupSubscriptions: function () {
            quote.paymentMethod.subscribe(this.handlePaymentMethodChange.bind(this));
            quote.shippingMethod.subscribe(this.handleShippingMethodChange.bind(this));

        },
        handlePaymentMethodChange: function (paymentMethod) {
            widgetHelper.handlePaymentMethodChange(paymentMethod);
        },
        handleShippingMethodChange: function (shippingMethod) {
            widgetHelper.handleShippingMethodChange(shippingMethod);
        }
    });
});
