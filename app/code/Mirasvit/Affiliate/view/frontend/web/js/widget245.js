define([
    'jquery',
    'Mirasvit_Affiliate/js/widget-abstract',
    'js-cookie/js.cookie'
], function ($, widgetAbstract, cookie) {
    'use strict';

    $.widget('mirasvit.affiliateWidget', widgetAbstract, {
        getCookie: function (name) {
            return cookie.get(name);
        },

        setCookie: function (name, value, options) {
            cookie.set(name, value, options);
        }
    });

    return $.mirasvit.affiliateWidget;
});
