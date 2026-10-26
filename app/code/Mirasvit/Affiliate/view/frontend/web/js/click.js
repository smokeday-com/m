define([
    'jquery',
    'Mirasvit_Affiliate/js/click-abstract',
    'jquery/jquery.cookie'
], function ($, clickAbstract) {
    'use strict';

    $.widget('mirasvit.affiliateClick', clickAbstract, {
        getCookie: function (name) {
            return $.cookie(name);
        },

        setCookie: function (name, value, options) {
            $.cookie(name, value, options);
        },

        removeCookie: function (name, options) {
            $.cookie(name, null, options);
        }
    });

    return $.mirasvit.affiliateClick;
});
