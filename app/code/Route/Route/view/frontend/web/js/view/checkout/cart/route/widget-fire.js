define([
        'uiComponent',
        'ko',
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/cart/totals-processor/default',
        'Magento_Checkout/js/model/cart/cache',
        'Magento_Checkout/js/model/full-screen-loader',
        'route-protection-widget'
    ], function (Component, ko, $, quote, defaultTotal, cartCache, fullScreenLoader) {
        'use strict';

        let checkoutConfig = window.checkoutConfig;
        let subtotal = checkoutConfig.subtotal;
        let controllerUrl = checkoutConfig.controller_url;
        let controllerWidgetUrl = checkoutConfig.controller_widget;
        let enable = checkoutConfig.enable;
        let currentState = checkoutConfig.is_checked;
        let isCarbonNeutral = checkoutConfig.is_carbon_neutral;
        let merchantId = checkoutConfig.merchant_id;
        let storeDomain = checkoutConfig.store_domain;
        let currencyCode = checkoutConfig.currency;
        let template = checkoutConfig.template;
        let routeEnvironment = checkoutConfig.route_environment;
        let quoteFunctionURL = checkoutConfig.quote_function_url;

        const CARBON_LABEL = ".pw-row-3";
        const STAGE = 'stage';

        return Component.extend({
            defaults: {
                template: template
            },

            config: {
                isEnabled: () => enable,
                renderWidgetId: () => 'RouteWidget',
                renderWidgetClassName: () => 'route-div',
                renderWidgetTitleId: () => 'RouteWidgetTitle'
            },

            initWidget: function () {

                let self = this;

                function checkVisibility(paymentMethod, shippingMethod) {
                    self.checkRouteWidgetVisibility({
                        payment_method: paymentMethod,
                        shipping_method: shippingMethod
                    });
                }

                function handlePaymentMethodChange(paymentMethod) {
                    if (!paymentMethod || typeof paymentMethod !== 'object' || typeof paymentMethod.method === 'undefined') {
                        return;
                    }
                    let shippingMethod = quote.shippingMethod() ? quote.shippingMethod().carrier_code + '_' + quote.shippingMethod().method_code : 'not_selected';
                    checkVisibility(paymentMethod.method, shippingMethod);
                }

                function handleShippingMethodChange(shippingMethod) {
                    if (!shippingMethod || typeof shippingMethod !== 'object' || typeof shippingMethod.carrier_code === 'undefined') {
                        return;
                    }
                    let paymentMethod = quote.paymentMethod() ? quote.paymentMethod().method : 'not_selected';
                    checkVisibility(paymentMethod, shippingMethod.carrier_code + '_' + shippingMethod.method_code);
                }

                if (enable) {
                    quote.paymentMethod.subscribe(handlePaymentMethodChange, null, 'change');
                    quote.shippingMethod.subscribe(handleShippingMethodChange, null, 'change');
                }

                if (typeof window.Route == "undefined") {
                    return;
                }

                //protection widget was injecting this script without proper csp nonce
                $.getScript(quoteFunctionURL).done(function () {
                    window.Route.Protection.render({
                        allowMultipleRenders:true,
                        storeDomain: storeDomain,
                        subtotal: subtotal,
                        currency: currencyCode,
                        environment: routeEnvironment === STAGE ? window.Route.Environment.Dev : window.Route.Environment.Production,
                        status: currentState ? window.Route.Coverage.ActiveByDefault : window.Route.Coverage.InactiveByDefault,
                        merchantId: merchantId,
                        theming: {
                            mode: window.Route.Theme.Mode.LightMode,
                            alignment: window.Route.Theme.Alignment.Left
                        }
                    });

                    window.Route.Protection.on("status_change", self.toggleInsurance);

                    window.Route.Protection.on("get_quote", function(data){
                        if ($('#checkout-shipping-method-load .table-checkout-shipping-method .row').length) {
                            let selectedShippingMethod = $('#checkout-shipping-method-load .table-checkout-shipping-method .radio:checked').val();
                            if (selectedShippingMethod !== undefined) {
                                let paymentMethod = quote.paymentMethod() ? quote.paymentMethod().method : 'not_selected';
                                self.checkRouteWidgetVisibility({payment_method: paymentMethod, shipping_method: selectedShippingMethod});
                            }
                        }
                        currentState = data?.quote?.paymentResponsible?.toggleState === "checked";
                    });
                }).fail(function( jqxhr, settings, exception ) {
                    console.error('Failed to load quote function', exception);
                });
            },

            toggleInsurance: function (data, changeState = true){
                fullScreenLoader.startLoader();
                if(changeState){
                    currentState = data.to
                }
                $.post(controllerUrl, {"is_route_insured": data.to}, function (data) {
                    if (!data.success) {
                        return
                    }
                    cartCache.set('totals', null);
                    let estimationResponse = defaultTotal.estimateTotals(quote.shippingAddress());
                    if (typeof estimationResponse == 'object') {
                        estimationResponse.then(function () {
                            fullScreenLoader.stopLoader();
                        }, function (e) {
                            fullScreenLoader.stopLoader();
                        });
                    }
                    $(CARBON_LABEL).toggle(isCarbonNeutral);
                });
            },

            checkRouteWidgetVisibility: function (params){
                let body = $('body').loader();
                let self = this;
                body.loader('show');
                $.post(controllerWidgetUrl, params, function (data) {
                    //control the visibility of the widget
                    if ($(".route-div").length) {
                        $(".route-div, #RouteWidgetTitle").toggle(enable && data.show_widget);
                    }

                    if(!data.show_widget){
                        self.toggleInsurance({to: data.show_widget}, false);
                    }

                }).done(()=> {
                    body.loader('hide');
                });
            }

        });
    }
);
