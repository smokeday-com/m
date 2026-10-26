define([
    'jquery'
], function ($) {
    'use strict';

    return function (productGallery) {
        $.widget('mage.productGallery', productGallery, {
            _bind: function () {
                // Call the original _bind method
                this._super();

                this._on({
                    updateOXTC: '_updateOXTC',
                });
            },
            /**
             * Add Thumb Carousel Checkbox value to image data
             *
             * @param {jQuery.Event} event
             * * @param {Object} data
             * @private
             */
            _updateOXTC: function (event, data) {
                var imageData = data.imageData,
                    thumbcarousel = +data.thumbcarousel,
                    $imageContainer = this.findElement(imageData);

                $imageContainer.find('[name*="thumbcarousel"]').val(thumbcarousel);
                imageData.thumbcarousel = thumbcarousel;

                this._contentUpdated();
            },

            _initDialog: function () {
                this._super();
                var $_dialog = this.$dialog;
                $_dialog.on('change', '[data-role=thumb-carousel]', $.proxy(function (e) {
                    var imageData = $_dialog.data('imageData');
                        this.element.trigger('updateOXTC', {
                        thumbcarousel: $(e.currentTarget).is(':checked'),
                        imageData: imageData
                    });
                }, this));

                this.$dialog = $_dialog;
            },

        });
        return $.mage.productGallery;
    };
});
