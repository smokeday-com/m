define([
    'jquery'
], function ($) {
    'use strict';

    $.widget('mirasvit.affiliateClickAbstract', {
        options: {
            urlCookieReset: null,
            url:            null,
            param:          null,
            cookieLifetime: 365
        },

        _create: function () {
            var identifierFromUrl = this.getUrlParam(this.options.param);
            var banner = this.getUrlParam('b');
            var currentDate = new Date();
            var sessionCookieName = 'affiliate';
            var referrerCookieName = 'affiliate_referrer';
            var identifierCookieName = 'affiliate_identifier';
            var referrer = '';
            var session = this.getCookie(sessionCookieName);

            if (!session) {
                session = '' + Math.floor(currentDate.getTime() / 1000) + Math.floor(Math.random() * 10000001);
            }

            if (session) {
                if (identifierFromUrl) {
                    let dateCookieEnd = new Date();
                    dateCookieEnd.setTime(dateCookieEnd.getTime() + this.options.cookieLifetime * 24 * 60 * 60 * 1000);
                    this.setCookie(sessionCookieName, session, {samesite: "Lax", expires: dateCookieEnd, path: '/', domain: window.location.hostname});
                    this.setCookie(identifierCookieName, identifierFromUrl, {samesite: "Lax", expires: dateCookieEnd, path: '/', domain: window.location.hostname});

                    referrer = this.getCookie(referrerCookieName);
                    if (!referrer && document.referrer) {
                        if ((new URL(document.referrer)).hostname != window.location.hostname) {
                            referrer = document.referrer;
                            this.setCookie(referrerCookieName, referrer, {samesite: "Lax", expires: this.options.cookieLifetime, path: '/', domain: window.location.hostname});
                        }
                    }

                    var url = this.options.url
                        + '?rnd=' + Math.floor(Math.random() * 10000001)
                        + "&identifier=" + identifierFromUrl
                        + "&session=" + session
                        + "&banner=" + banner
                        + "&referer=" + referrer;
                    $.ajax(url);
                } else {
                    $.ajax({
                        type:    'POST',
                        url:     this.options.urlCookieReset,
                        data:    {},
                        success: function (response) {
                            if (response.cookie_end && response.cookie_end > 0) {
                                let cookieEndTs = response.cookie_end;
                                let sessionCookie = this.getCookie(sessionCookieName);
                                let identifierCookie = this.getCookie(identifierCookieName);
                                let referrerCookie = this.getCookie(referrerCookieName);
                                let dateCookieEnd = new Date();
                                dateCookieEnd.setTime(cookieEndTs * 1000);
                                let date0 = new Date();
                                date0.setTime(0);

                                if (sessionCookie) {
                                    this.removeCookie(sessionCookieName, {samesite: "Lax", expires: date0, path: '/', domain: window.location.hostname});
                                    this.removeCookie(sessionCookieName, {samesite: "Lax", expires: date0, path: '/', domain: '.' + window.location.hostname});
                                    this.setCookie(sessionCookieName, sessionCookie, {samesite: "Lax", expires: dateCookieEnd, path: '/', domain: window.location.hostname});
                                }
                                if (identifierCookie) {
                                    this.removeCookie(identifierCookieName, {samesite: "Lax", expires: date0, path: '/', domain: window.location.hostname});
                                    this.removeCookie(identifierCookieName, {samesite: "Lax", expires: date0, path: '/', domain: '.' + window.location.hostname});
                                    this.setCookie(identifierCookieName, identifierCookie, {samesite: "Lax", expires: dateCookieEnd, path: '/', domain: window.location.hostname});
                                }
                                if (referrerCookie) {
                                    this.removeCookie(referrerCookieName, {samesite: "Lax", expires: date0, path: '/', domain: window.location.hostname});
                                    this.removeCookie(referrerCookieName, {samesite: "Lax", expires: date0, path: '/', domain: '.' + window.location.hostname});
                                    this.setCookie(referrerCookieName, referrerCookie, {samesite: "Lax", expires: dateCookieEnd, path: '/', domain: window.location.hostname});
                                }
                            }
                        }.bind(this)
                    });
                }
            }
        },

        getUrlParam: function (name) {
            let results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
            if (results === null) {
                return '';
            } else {
                return results[1] || 0;
            }
        },

        getCookie: function (name) {
            console.log('error: mirasvit.affiliateClickAbstract.getCookie() must be overridden');
            return '';
        },

        setCookie: function (name, value, options) {
            console.log('error: mirasvit.affiliateClickAbstract.setCookie() must be overridden');
        },

        removeCookie: function (name, options) {
            console.log('error: mirasvit.affiliateClickAbstract.removeCookie() must be overridden');
        }
    });

    return $.mirasvit.affiliateClickAbstract;
});
