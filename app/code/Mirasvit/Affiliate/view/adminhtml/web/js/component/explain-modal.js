define([
    'Magento_Ui/js/modal/modal-component',
    'jquery',
    'mage/url',
    'uiRegistry'
], function (modal, $, url, registry) {
    'use strict';

    return modal.extend({
        openModal: function (p1, p2, action) {
            this._super();

            let rowIndex = action.rowIndex;
            if (rowIndex === undefined) {
                return;
            }

            let actionsColumn = registry.get('affiliate_transaction_listing.affiliate_transaction_listing.columns.actionsColumn');
            if (!actionsColumn) {
                return;
            }

            let row = actionsColumn.rows[rowIndex];
            if (!row) {
                return;
            }

            if (!row.transaction_id) {
                return;
            }

            this.load(row.transaction_id);
        },

        load: function (transactionId) {
            this.modal.html('');
            $('body').trigger('processStart');
            $.ajax({
                url: this.options.explainUrl,
                data: {
                    form_key: window.FORM_KEY,
                    transaction_id: transactionId
                },
                dataType: 'html',
                success: function (resp) {
                    this.modal.html(resp);
                }.bind(this),
                complete: function () {
                    $('body').trigger('processStop');
                }
            });
        }
    });
});
