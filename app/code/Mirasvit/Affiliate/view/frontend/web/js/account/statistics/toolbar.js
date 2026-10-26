define([
    'jquery'
], function ($) {
    'use strict';

    $.widget('affiliate.statisticsToolbar', {
        options:              {},
        formSelector:         '#mst-affiliate-statistics-toolbar',
        submitButtonSelector: '#mst-affiliate-statistics-toolbar button[type="button"].affiliate__statistics-toolbar-apply',

        _create: function () {
            let submitButton = $(this.submitButtonSelector);
            submitButton.on('click', function (e) {
                let formData = new FormData(document.querySelector(this.formSelector));
                let url = new URL(window.location.href)

                url.searchParams.set('from', formData.get('from'));
                url.searchParams.set('to', formData.get('to'));
                url.searchParams.set('current-site', formData.get('current-site'));
                window.location.href = url.href;
            }.bind(this));
        }
    });

    return $.affiliate.statisticsToolbar;
});
