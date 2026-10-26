define([], function () {
    'use strict';
    let currentState = checkoutConfig.is_checked;

    return {
        isEnabled: function () {
            return window.checkoutConfig.enable;
        },
        isCarbonNeutral: function () {
            return window.checkoutConfig.is_carbon_neutral;
        },
        setState: function (state) {
            currentState = state;
        },
        getState: function () {
            return currentState;
        },

    };
});
