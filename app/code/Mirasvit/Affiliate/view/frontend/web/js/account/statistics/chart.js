define([
    'jquery',
    'Mirasvit_Affiliate/lib/chart.js-3.5.0/chart.min'
], function ($) {
    'use strict';

    $.widget('affiliate.chartBalance', {
        options: {
            chartConfig: {}
        },

        _create: function () {
            this.addCurrencyFormatter();

            const popCanvas = $("#popChart");
            let chart = new Chart(popCanvas, this.options.chartConfig.ChartJsConfig);
        },

        addCurrencyFormatter: function () {
            this.options.chartConfig.ChartJsConfig.options = (this.options.chartConfig.ChartJsConfig.options || {});
            this.options.chartConfig.ChartJsConfig.options.plugins = (this.options.chartConfig.ChartJsConfig.options.plugins || {});
            this.options.chartConfig.ChartJsConfig.options.plugins.tooltip = (this.options.chartConfig.ChartJsConfig.options.plugins.tooltip || {});
            this.options.chartConfig.ChartJsConfig.options.plugins.tooltip.callbacks = (this.options.chartConfig.ChartJsConfig.options.plugins.tooltip.callbacks || {});

            this.options.chartConfig.ChartJsConfig.options.plugins.tooltip.callbacks.label = this.currencyFormatter(this.options.chartConfig.currencyCode);
        },

        currencyFormatter: function (currencyCode) {
            return function (context) {
                var label = context.dataset.label || '';
                if (label) {
                    label += ': ';
                }
                if (context.parsed.y !== null) {
                    label += new Intl.NumberFormat('en-US', {style: 'currency', currency: currencyCode}).format(context.parsed.y);
                }
                return label;
            }
        }

    });

    return $.affiliate.chartBalance;
});
