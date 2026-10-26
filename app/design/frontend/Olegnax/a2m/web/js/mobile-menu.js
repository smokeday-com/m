define([
	'jquery',
	'mage/template',
	'OXmodal'
], function ($, mageTemplate) {
	'use strict';

	$.widget('mage.mobileMenu', $.mage.OXmodal, {
		options: {
			htmlClass: 'ox-fixed',
			closeOnEscape: true,
			overlayClass: 'ox-slideout-shadow',
			positionSlideout: 'left',
			triggerTarget: '[data-action="toggle-mobile-nav"]',
			type: 'slideout',
		},
		_init: function () {
			this._super();

                        this._toggleMobileMode();
			this._on(this.uiDialog, {
				'swipeleft': $.proxy(function () {
					this.close();
				}, this)
			});
			$('body').on('click', '.navigation a.ui-corner-all:not(.ui-state-active):not(.ui-state-focus)', function (e) {
				e.preventDefault();
			});
		},
		open: function () {
			var wrapper = this.element;
			wrapper.find('.ox-nav-sections-item-content').each(function () {
				var $this = $(this),
					$control = wrapper.find('[aria-controls="' + $this.attr('id') + '"]'),
					trigger = 0 < $this.children().length;
				$control.toggleClass('no-display', !trigger).toggle(trigger);
			});
			wrapper.attr('data-count-tabs', wrapper.find('.ox-nav-sections-item-title:not(.no-display)').length)
			this._super();
		},
        /**
         * @private
         */
        _toggleMobileMode: function () {
            $('.ox-move-item').each($.proxy(function (index, item) {
                var $this = $(item),
                    _class = (($this.attr('class') || '').match(/ox-move-item-([^ ]{1,})/i) || ['', ''])[1],
                    $mobile_parents = $('[data-move-mobile="' + _class + '"]'),
                    $mobile_parent = $mobile_parents.eq(0);
                if (!_class || !$mobile_parent.length || $this.parent().is($mobile_parent)) {
                    return;
                }

                $this.data('moveDesktopParent', $this.parent());
                $this.data('moveDesktopPosition', $this.parent().children().index($this));
                $this.appendTo($mobile_parent);
            }, this));
        }

	});

	return $.mage.mobileMenu;
});
