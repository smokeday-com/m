define([
        'uiComponent',
        'ko',
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/cart/cache',
        'Magento_Checkout/js/action/get-totals',
        'Magento_Checkout/js/model/shipping-rate-processor/new-address',
        'Magento_Checkout/js/model/shipping-rate-processor/customer-address',
        'Magento_Checkout/js/model/shipping-rate-registry',
        'route-protection-widget'
    ], function (Component, ko, $, quote, cartCache, getTotalsAction, defaultProcessor, customerAddressProcessor, rateRegistry) {


        const STAGE = 'stage';

        let subtotal = '';
        let controllerUrl = '';
        let controllerWidgetUrl = '';
        let enable = '';
        let currencyCode = '';
        let isChecked = false;
        let routeEnvironment = '';
        let isCarbonNeutral = false;
        let merchantId = '';
        let storeDomain  = '';

        let main = {
            toggleInsurance: function (data) {
                $.post(controllerUrl, {"is_route_insured": data.to}, function (data){
                    if(data.success){
                        if (quote.shippingMethod() != undefined) {
                            //force reloading of shipping methods
                            let processors = [];

                            rateRegistry.set(quote.shippingAddress().getCacheKey(), null);

                            processors.default =  defaultProcessor;
                            processors['customer-address'] = customerAddressProcessor;

                            let type = quote.shippingAddress().getType();

                            if (processors[type]) {
                                processors[type].getRates(quote.shippingAddress());
                            } else {
                                processors.default.getRates(quote.shippingAddress());
                            }
                        } else {
                            //trigger full totals refresh
                            cartCache.set('totals',null);
                            let deferred = $.Deferred();
                            getTotalsAction([], deferred);
                        }
                    }
                });
            },
            getSubtotal: function () {
                $.getJSON(controllerUrl, {}, function (data) {
                    subtotal = data.subtotal;
                    currencyCode = data.currency;
                    main.render()
                });
            },
            render: function () {
                let self = this;
                quote.shippingMethod.subscribe(function(shippingMethod){
                    if(shippingMethod === null || typeof shippingMethod !== 'object' || typeof shippingMethod.carrier_code === 'undefined'){
                        return;
                    }

                    let paymentMethod = quote.paymentMethod() ? quote.paymentMethod().method : 'not_selected';
                    self.checkRouteWidget({
                        payment_method: paymentMethod,
                        shipping_method: shippingMethod.carrier_code + '_' + shippingMethod.method_code
                    });
                }, null, 'change');

                if (typeof window.Route != "undefined" && !$('route-protect-widget').length) {

                    window.Route.Protection.render({
                        storeDomain: storeDomain,
                        subtotal: subtotal,
                        currency: currencyCode,
                        environment: routeEnvironment === STAGE ? window.Route.Environment.Dev : window.Route.Environment.Production,
                        status: isChecked ? window.Route.Coverage.ActiveByDefault : window.Route.Coverage.InactiveByDefault,
                        merchantId: merchantId,
                        theming: {
                            mode: window.Route.Theme.Mode.LightMode,
                            alignment: window.Route.Theme.Alignment.Left
                        }
                    });

                    window.Route.Protection.on("status_change", self.toggleInsurance);
                    window.Route.Protection.on("get_quote", function(){
                        if ($('#checkout-shipping-method-load .table-checkout-shipping-method .row').length) {
                            let selectedShippingMethod = $('#checkout-shipping-method-load .table-checkout-shipping-method .radio:checked').val();
                            if (selectedShippingMethod !== undefined) {
                                let paymentMethod = quote.paymentMethod() ? quote.paymentMethod().method : 'not_selected';
                                self.checkRouteWidget({payment_method: paymentMethod, shipping_method: selectedShippingMethod});
                            }
                        }
                    });
                }

                let previousSubtotal = quote.totals()['base_subtotal'];

                quote.totals.subscribe(function () {
                    let cartSubTotal = quote.totals()['base_subtotal'];
                    if (previousSubtotal !== cartSubTotal) {
                        subtotal = cartSubTotal
                        previousSubtotal = cartSubTotal;
                        main.render()
                    }
                }, null, 'change');
            },
            initWidget: function (config, element) {
                let self = this;

                if (!config.enable)
                    return;

                // 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
                // "Fix broken Route_Route QuoteApi caching and FulfilledPromise bug, increase Merchant cache TTL to 86400s, use Magento config cache in Setup::getSetupCheckWidget, and eliminate duplicate checkWidget calls and full-screen loader blocking":
                // https://github.com/smokeday-com/m/issues/23
                if (window._routeWidgetHelperActive || (window.checkoutConfig && window.checkoutConfig.quote_function_url)) {
                    return;
                }

                controllerUrl = config.controller_url;
                controllerWidgetUrl = config.controller_widget;
                enable = config.enable;
                isCarbonNeutral = config.is_carbon_neutral;
                subtotal = config.subtotal;
                isChecked = config.is_checked;
                currencyCode = config.currency;
                routeEnvironment = config.route_environment;
                merchantId = config.merchant_id;
                storeDomain  = config.store_domain;
                if (config.subtotal == null)
                    main.getSubtotal();
                else
                    main.render()
            },
            // 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
            // "Fix broken Route_Route QuoteApi caching and FulfilledPromise bug, increase Merchant cache TTL to 86400s, use Magento config cache in Setup::getSetupCheckWidget, and eliminate duplicate checkWidget calls and full-screen loader blocking":
            // https://github.com/smokeday-com/m/issues/23
            checkRouteWidget: function (params) {
                const key = (params.payment_method || '') + '|' + (params.shipping_method || '');
                const applyWidget = function (data) {
                    if ($(".route-div").length) {
                        $(".route-div, #RouteWidgetTitle").toggle(enable && data.show_widget);
                    }
                };
                if (window._lastRouteCheckWidgetKey === key && window._lastRouteCheckWidgetPromise) {
                    window._lastRouteCheckWidgetPromise.done(applyWidget);
                    return;
                }
                window._lastRouteCheckWidgetKey = key;
                window._lastRouteCheckWidgetPromise = $.post(controllerWidgetUrl, params, applyWidget);
            }
        }

        return {
            'route_magento_widget': main.initWidget
        };
    }
);
