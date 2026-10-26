/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'jquery-ui-modules/dialog',
    'mage/translate'
], function ($) {
    'use strict';

    var timer = null;

    /**
     * Dropdown Widget - this widget is a wrapper for the jQuery UI Dialog
     */
    $.widget('mage.dropdownDialog', $.ui.dialog, {
        options: {
            triggerEvent: 'click touchstart',
            triggerClass: null,
            parentClass: null,
            triggerTarget: null,
            defaultDialogClass: 'mage-dropdown-dialog',
            dialogContentClass: null,
            shadowHinter: null,
            closeOnMouseLeave: false,
            closeOnClickOutside: true,
            minHeight: null,
            minWidth: null,
            width: null,
            modal: false,
            timeout: null,
            autoOpen: false,
            createTitleBar: false,
            autoPosition: false,
            autoSize: false,
            draggable: false,
            resizable: false,
            bodyClass: '',
            buttons: [
                {
                    'class': 'action close',
                    'text': $.mage.__('Close'),

                    /**
                     * Click action.
                     */
                    'click touchstart': function () {
                        let $this = $(this);
                        if ($this.data('magedropdownDialog')) {
                            $this.dropdownDialog('close');
                        } else if ($this.closest('.ui-dialog-content').length) {
                            let $parent = $this.closest('.ui-dialog-content').eq(0);
                            if ($parent.data('magedropdownDialog')) {
                                $parent.dropdownDialog('close');
                            }
                        }
                    }
                }
            ]
        },

        /**
         * extend default functionality to bind the opener for dropdown
         * @private
         */
        _create: function () {
            var _self = this;

            this._super();
            this.uiDialog.addClass(this.options.defaultDialogClass);

            if (_self.options.triggerTarget) {
                $(_self.options.triggerTarget).on(_self.options.triggerEvent, function (event) {
                    event.preventDefault();
                    event.stopPropagation();

                    if (!_self._isOpen) {
                        $('.' + _self.options.defaultDialogClass + ' > .ui-dialog-content').dropdownDialog('close');
                        _self.open();
                    } else {
                        _self.close(event);
                    }
                });
            }

            if (_self.options.shadowHinter) {
                _self.hinter = $('<div class="' + _self.options.shadowHinter + '"/>');
                _self.element.append(_self.hinter);
            }
        },

        /**
         * Extend default functionality to close the dropdown
         * with custom delay on mouse out and also to close when clicking outside
         */
        open: function () {
            var _self = this;

            this._super();

            if (_self.options.dialogContentClass) {
                _self.element.addClass(_self.options.dialogContentClass);
            }

            if (_self.options.closeOnClickOutside) {
                $('body').on('click.outsideDropdown touchstart.outsideDropdown', function (event) {
                    if (_self._isOpen && !$(event.target).closest('.ui-dialog').length) {
                        if (timer) {
                            clearTimeout(timer);
                        }
                        _self.close(event);
                    }
                });
            }
            // adding the class on the opener and parent element for dropdown
            if (_self.options.triggerClass) {
                $(_self.options.triggerTarget).addClass(_self.options.triggerClass);
            }

            if (_self.options.parentClass) {
                $(_self.options.appendTo).addClass(_self.options.parentClass);
            }

            if (_self.options.bodyClass) {
                $('body').addClass(_self.options.bodyClass);
            }

            if (_self.options.shadowHinter) {
                _self._setShadowHinterPosition();
            }
        },

        /**
         * extend default functionality to reset the timer and remove the active class for opener
         */
        close: function () {
            this._super();

            if (this.options.dialogContentClass) {
                this.element.removeClass(this.options.dialogContentClass);
            }

            if (this.options.triggerClass) {
                $(this.options.triggerTarget).removeClass(this.options.triggerClass);
            }

            if (this.options.parentClass) {
                $(this.options.appendTo).removeClass(this.options.parentClass);
            }

            if (this.options.bodyClass) {
                $('body').removeClass(this.options.bodyClass);
            }

            if (timer) {
                clearTimeout(timer);
            }

            if (this.options.triggerTarget) {
                $(this.options.triggerTarget).off('mouseleave');
            }
            this.uiDialog.off('mouseenter');
            this.uiDialog.off('mouseleave');
            $('body').off('click.outsideDropdown touchstart.outsideDropdown');
        },

        /**
         * _setShadowHinterPosition
         * @private
         */
        _setShadowHinterPosition: function () {
            var _self = this,
                offset;

            offset = _self.options.position.of.offset().left -
                _self.element.offset().left +
                _self.options.position.of.outerWidth() / 2;
            offset = isNaN(offset) ? 0 : Math.floor(offset);
            _self.hinter.css('left', offset);
        },

        /**
         * @private
         */
        _position: function () {
            if (this.options.autoPosition) {
                this._super();
            }
        },

        /**
         * @private
         */
        _createTitlebar: function () {
            if (this.options.createTitleBar) {
                this._super();
            } else {
                // the title bar close button is referenced
                // in _focusTabbable function, so to prevent errors it must be declared
                this.uiDialogTitlebarClose = $('<div>');
            }
        },

        /**
         * @private
         */
        _size: function () {
            if (this.options.autoSize) {
                this._super();
            }
        },

        /**
         * @param {String} key
         * @param {*} value
         * @private
         */
        _setOption: function (key, value) {
            this._super(key, value);

            if (key === 'triggerTarget') {
                this.options.triggerTarget = value;
            }
        }
    });

    return $.mage.dropdownDialog;
});
