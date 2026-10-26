define([
    'underscore',
    'uiComponent'
], function (_, uiComponent) {
    'use strict';
    
    return uiComponent.extend({
        defaults: {
            defaults:         {
                template: 'Mirasvit_Affiliate/component/tier/record'
            },
            imports:          {
                data: '${ $.provider }:${ $.dataScope }'
            },
            exports:          {
                recordId: '${ $.provider }:${ $.dataScope }.record_id'
            },
            modules:          {
                parentComponent: '${ $.parentName }'
            }
        },
        
        initialize: function () {
            this._super();
        }
    });
});
