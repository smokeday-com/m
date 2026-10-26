require([
    'jquery',
    'domReady!'
], function ($) {
    'use strict';
    
    /*Social share open/close */
    var socialShare = $('.social-share__action');
    if (socialShare.length) {
        socialShare.on('click', function (e) {
            $('.social-share__content').addClass('opened');
            e.stopPropagation();
        });
        $('body').on('click', function () {
            $('.social-share__content').removeClass('opened');
        });
    }

});
