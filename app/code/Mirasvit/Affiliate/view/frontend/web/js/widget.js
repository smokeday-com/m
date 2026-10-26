define([
    'jquery',
    'Mirasvit_Affiliate/js/widget-abstract',
    'jquery/jquery.cookie'
], function ($, widgetAbstract) {
    'use strict';

    $.widget('mirasvit.affiliateWidget', widgetAbstract, {
        getCookie: function (name) {
            return $.cookie(name);
        },

        setCookie: function (name, value, options) {
            $.cookie(name, value, options);
        }
    });

    return $.mirasvit.affiliateWidget;
});
