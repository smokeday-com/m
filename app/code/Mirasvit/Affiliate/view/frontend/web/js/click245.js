define([
    'jquery',
    'Mirasvit_Affiliate/js/click-abstract',
    'js-cookie/js.cookie'
], function ($, clickAbstract, cookie) {
    'use strict';

    $.widget('mirasvit.affiliateClick245', clickAbstract, {
        getCookie: function (name) {
            return cookie.get(name);
        },

        setCookie: function (name, value, options) {
            cookie.set(name, value, options);
        },

        removeCookie: function (name, options) {
            cookie.remove(name, options);
        }
    });

    return $.mirasvit.affiliateClick245;
});
