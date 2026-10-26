define([
    'jquery',
    'underscore',
    'ko',
    'uiComponent',
    'uiCollection',
    'uiLayout',
    'mageUtils',
    'Magento_Ui/js/dynamic-rows/dynamic-rows'
], function ($, _, ko, uiComponent, uiCollection, uiLayout, utils, dynamicRows) {
    'use strict';

    return dynamicRows.extend({
        defaults: {
            template:  'Mirasvit_Affiliate/component/tier/container',
            templates: {
                record: {
                    parent:       '${ $.$data.collection.name }',
                    name:         '${ $.$data.index }',
                    dataScope:    '${ $.$data.collection.index }.${ $.name }',
                    nodeTemplate: '${ $.parent }.${ $.$data.collection.recordTemplate }'
                }
            },
            links:     {
                tiers: '${ $.provider }:${ $.dataScope }.${ $.index }',
                type:  '${ $.provider }:${ $.dataScope }.type'
            },
            listens:   {
                childTemplate: 'initHeader'
            },

            tiers:  [],
            labels: []
        },

        initialize: function () {
            this._super();

            _.bindAll(this, 'handleAdd', 'handleDelete');

            // init tiers only for active program type
            if (this.type === this.program) {
                this.initElements(this.tiers());
            }

            return this;
        },

        initObservable: function () {
            this._super();

            this.track('childTemplate');

            this.observe([
                'tiers',
                'labels'
            ]);

            return this;
        },

        initElements: function (data) {
            console.log(data);
            _.each(data, function (data, idx) {
                this.addChild(data, idx);
            }.bind(this));

            return this;
        },

        initHeader: function () {
            var data;

            if (!this.labels().length) {
                _.each(this.childTemplate.children, function (cell) {
                    var label = cell.config.label;

                    if (label.indexOf('Commission From') >= 0) {
                        var newLabel = label.replace('Commission ', 'Commission<br><small>');
                        newLabel += '</small>';

                        label = newLabel;
                    }

                    this.labels.push(label);
                }, this);
            }
        },

        addChild: function (data, index) {
            if (this.type !== this.program) {
                return;
            }
            var template = this.templates.record;

            index = index !== undefined ? index : this.tiers().length;

            _.extend(this.templates.record, {
                recordId: index,
                tier:     index + 1
            });

            var child = utils.template(template, {
                collection: this,
                index:      index
            });

            uiLayout([child], this);

            return this;
        },

        handleAdd: function () {
            this.addChild({});

            return this;
        },

        handleDelete: function (index) {
            this.deleteRecord(index);

            // for magento 2.1.*
            if(this.relatedData.length < this.tiers().length) {
                this.tiers.splice(index, 1);
            }
        }
    })
});
