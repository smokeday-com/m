define([
    'jquery',
    'Magento_Checkout/js/model/cart/cache',
    'Magento_Checkout/js/action/get-totals',
    'Magento_Checkout/js/model/quote',
    'Route_Route/js/view/checkout/cart/route/route-helper',
    'route-protection-widget'
], function ($, cartCache, getTotalsAction, quote, routeHelper) {
    'use strict';

    const CARBON_LABEL = ".pw-row-3";

    return {
        renderWidget: function () {
            const checkoutConfig = window.checkoutConfig;
            // 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
            // "Fix broken Route_Route QuoteApi caching and FulfilledPromise bug, increase Merchant cache TTL to 86400s, use Magento config cache in Setup::getSetupCheckWidget, and eliminate duplicate checkWidget calls and full-screen loader blocking":
            // https://github.com/smokeday-com/m/issues/23
            window._routeWidgetHelperActive = true;

            const widgetParams = {
                allowMultipleRenders:true,
                storeDomain: checkoutConfig.store_domain,
                subtotal: checkoutConfig.subtotal,
                currency: checkoutConfig.currency,
                environment: this.getEnvironment(),
                status: this.getStatus(),
                merchantId: checkoutConfig.merchant_id,
                theming: this.getTheming()
            };

            $.getScript(checkoutConfig.quote_function_url).done( () => {
                console.log('Login widget', widgetParams);
                window.Route.Protection.render(widgetParams);

                window.Route.Protection.on("status_change", this.toggleInsurance);

                window.Route.Protection.on("get_quote", (data)=> {
                    if ($('#checkout-shipping-method-load .table-checkout-shipping-method .row').length) {
                        let selectedShippingMethod = $('#checkout-shipping-method-load .table-checkout-shipping-method .radio:checked').val();
                        if (selectedShippingMethod !== undefined) {
                            let paymentMethod = quote.paymentMethod() ? quote.paymentMethod().method : 'not_selected';
                            this.checkRouteWidgetVisibility({payment_method: paymentMethod, shipping_method: selectedShippingMethod});
                        }
                    }
                    routeHelper.setState(data?.quote?.paymentResponsible?.toggleState === "checked");
                });
            }).fail(function (jqxhr, settings, exception) {
                console.error('Failed to load quote function', exception);
            });
        },
        getEnvironment: function () {
            const routeEnv = window.checkoutConfig.route_environment;
            return routeEnv === 'stage' ? window.Route.Environment.Dev : window.Route.Environment.Production;
        },
        getStatus: function () {
            return window.checkoutConfig.is_checked ? window.Route.Coverage.ActiveByDefault : window.Route.Coverage.InactiveByDefault;
        },
        getTheming: function () {
            return {
                mode: window.Route.Theme.Mode.LightMode,
                alignment: window.Route.Theme.Alignment.Left
            };
        },
        handlePaymentMethodChange: function (paymentMethod) {
            if (!paymentMethod || typeof paymentMethod !== 'object' || !paymentMethod.method) {
                return;
            }
            let shippingMethod = quote.shippingMethod() ? quote.shippingMethod().carrier_code + '_' + quote.shippingMethod().method_code : 'not_selected';
            this.checkVisibility(paymentMethod.method, shippingMethod);
        },
        handleShippingMethodChange: function (shippingMethod) {
            if (!shippingMethod || typeof shippingMethod !== 'object' || !shippingMethod.carrier_code) {
                return;
            }
            let paymentMethod = quote.paymentMethod() ? quote.paymentMethod().method : 'not_selected';
            this.checkVisibility(paymentMethod, shippingMethod.carrier_code + '_' + shippingMethod.method_code);
        },
        checkVisibility(paymentMethod, shippingMethod) {
            this.checkRouteWidgetVisibility({
                payment_method: paymentMethod,
                shipping_method: shippingMethod
            });
        },
        // 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
        // "Fix broken Route_Route QuoteApi caching and FulfilledPromise bug, increase Merchant cache TTL to 86400s, use Magento config cache in Setup::getSetupCheckWidget, and eliminate duplicate checkWidget calls and full-screen loader blocking":
        // https://github.com/smokeday-com/m/issues/23
        checkRouteWidgetVisibility: function (params){
            const key = (params.payment_method || '') + '|' + (params.shipping_method || '');
            const applyVisibility = (data) => {
                $(CARBON_LABEL).toggle(routeHelper.isCarbonNeutral());
                if ($(".route-div").length) {
                    $(".route-div, #RouteWidgetTitle").toggle(routeHelper.isEnabled() && data.show_widget);
                }
                if (!data.show_widget) {
                    this.toggleInsurance({to: data.show_widget}, false);
                }
            };
            if (window._lastRouteCheckWidgetKey === key && window._lastRouteCheckWidgetPromise) {
                window._lastRouteCheckWidgetPromise.done(applyVisibility);
                return;
            }
            window._lastRouteCheckWidgetKey = key;
            window._lastRouteCheckWidgetPromise = $.post(checkoutConfig.controller_widget, params, applyVisibility);
        },
        toggleInsurance: function (data, changeState = true){
            if(changeState){
                routeHelper.setState(data.to)
            }
            $.post(checkoutConfig.controller_url, {"is_route_insured": data.to}, (data) => {
                if (!data.success) {
                    return
                }
                cartCache.set('totals', null);
                let deferred = $.Deferred();
                getTotalsAction([], deferred);
            });
        },
    };
});
