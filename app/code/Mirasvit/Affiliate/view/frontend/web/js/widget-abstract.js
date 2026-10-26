define([
    'jquery'
], function ($) {
    'use strict';

    $.widget('mirasvit.affiliateWidgetAbstract', {
        options: {
            cookieLifetime: 365
        },

        _create: function () {
            $('a').on('click', function (e) {
                e.preventDefault();

                const $a = $(e.currentTarget);
                const clickUrl = $a.attr('href')
                const referrerCookieName = 'affiliate_referrer';
                let referrer = this.getCookie(referrerCookieName);

                if (!clickUrl) {
                    return;
                }

                if (!referrer) {
                    if (document.referrer) {
                        if ((new URL(document.referrer)).hostname != window.location.hostname) {
                            referrer = document.referrer;
                            this.setCookie(referrerCookieName, referrer, {
                                samesite: "lax",
                                expires: this.options.cookieLifetime,
                                path: '/'
                            });
                        }
                    }
                }
                top.window.location.href = clickUrl;
            }.bind(this))
        },
        getCookie: function (name) {
            console.log('error: mirasvit.affiliateWidgetAbstract.getCookie() must be overridden');
            return '';
        },
        setCookie: function (name, value, options) {
            console.log('error: mirasvit.affiliateWidgetAbstract.setCookie() must be overridden');
        },
    });

    return $.mirasvit.affiliateWidgetAbstract;
});
