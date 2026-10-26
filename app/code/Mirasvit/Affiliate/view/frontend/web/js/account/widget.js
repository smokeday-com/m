define([
    'jquery',
    'underscore',
    'Magento_Ui/js/modal/modal'
], function ($, _, modal) {
    'use strict';

    $.widget('affiliate.accountWidget', {
        options: {
            url: null
        },

        _create: function () {
            _.bindAll(this, 'showCode');

            this.$iframe = $('iframe', this.element);

            _.each($('[data-option]'), function (option) {
                $(option).on('change', function () {
                    this.refreshWidget();
                }.bind(this));
            }, this);

            $('[data-action="code"]').on('click', function () {
                this.showCode();
            }.bind(this));

            this.$iframe.on('load', function () {
                $('body').trigger('processStop');
            });

            this.refreshWidget();
        },

        refreshWidget: function () {
            let url     = this.options.url;
            let options = {};

            _.each($('[data-option]'), function (option) {
                let $option = $(option);
                let val     = $option.val();

                if ($option.attr('type') === 'text') {
                    $option.val(val.replace(/<script>.*<\/script>|<.*?>/gi, ''));
                    val = $option.val();
                }

                if ($option.attr('type') === 'checkbox' || $option.attr('type') === 'radio') {
                    val = $(option).is(':checked') ? 1 : 0;

                    if ($option.attr('data-option') === 'search') {
                        if (val) {
                            $('[data-rel="search"]', this.element).show();
                        } else {
                            $('[data-rel="search"]', this.element).hide();
                        }
                    }
                }

                options[$option.attr('data-option')] = val;
            }, this);

            url += '?' + $.param(options);
            $('body').trigger('processStart');
            this.$iframe.attr('src', url);

            if (options.width) {
                this.$iframe.width(options.width);
            }
            if (options.height) {
                this.$iframe.height(options.height);
            }
        },

        showCode: function () {
            let $element = $('#iframe_modal', this.element).clone();

            $('textarea', $element).html(this.$iframe[0].outerHTML);
            let $modal = modal({}, $element);
            $modal.openModal();
        }
    });

    return $.affiliate.accountWidget;
});
