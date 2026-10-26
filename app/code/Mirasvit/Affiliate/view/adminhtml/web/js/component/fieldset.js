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
                '${$.provider}:data.type': 'toggleVisibility'
            }
        },

        initialize: function () {
            this._super();

            this.setLinks({
                toggleVisibility: this.toggle.selector
            }, 'imports');
        },

        toggleVisibility: function (selected) {
            if (selected === this.toggle.value) {
                this.visible(true);
            } else {
                this.visible(false);
            }
        }
    });
});
