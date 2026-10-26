define([
    'Magento_Ui/js/form/components/fieldset'
], function (Fieldset) {
    'use strict';

    return Fieldset.extend({
        defaults: {
            toggle: {
                selector: '',
                value:    ''
            },
            listens: {
                '${$.provider}:data.is_auto_update': 'toggleVisibility'
            }
        },

        initialize: function () {
            this._super();

            this.setLinks({
                toggleVisibility: this.toggle.selector
            }, 'imports');
        },

        toggleVisibility: function (selected) {

            if(this.index == 'sale' && this.elems().length) {
                this.elems()[0].recordData([]);
            }

            if (selected === this.toggle.value) {
                this.visible(true);
            } else {
                this.visible(false);
            }
        }
    });
});
