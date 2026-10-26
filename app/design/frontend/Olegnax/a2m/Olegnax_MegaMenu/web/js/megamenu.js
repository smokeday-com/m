define([
	'jquery',
	'jquery-ui-modules/widget',
	'jquery-ui-modules/core',
], function ($) {
	'use strict';

	$.widget('ox.OxMegaMenu', {
		options: {
			btn: false,
			actionActive: 'active',
			toggleTransitionDuration: 400,
			classNavigation: '.ox-megamenu-navigation',
			classDropdown: '.ox-megamenu__dropdown',
			dupParents: false,
		},
		_create: function () {
			var _self = this,
				$ox_megamenu = this.element;
			this.is_desktop = false;
			this._widthHandlers({
				elem: $ox_megamenu.find('li.parent > .ox-mm-a-wrap, li.ox-dropdown--megamenu > .ox-mm-a-wrap'),
				namespace: 'togglelist',
				desktop: false,
				events: {
					'click': function (e) {
						if (('A' === e.target.tagName || 'SPAN' === e.target.tagName) && $(this).parent().hasClass('ox-megamenu--opened') || !$(this).closest('li').eq(0).find('.submenu, .ox-submenu, .ox-megamenu__dropdown').length)
							return;
						_self._toggleList(this);

						e.preventDefault();
						return false;
					},
				}
			});
			this._duplicateParents();
			$(this.window).trigger('resize');
		},
		_duplicateParents: function () {
			if(!this.options.dupParents){
				return;
			}
			/* duplicate top level items */
			var subMenus = this.element.find('.level-top');
			$.each(subMenus, $.proxy(function (index, item) {
				var linkWrapper = $(item).find('> .ox-mm-a-wrap');
				if(!linkWrapper.length) return;
				var category = $(linkWrapper).find('a .name').text(),
					categoryUrl = $(linkWrapper).find('a').attr('href'),
					menu = $(item).find('> .ox-megamenu__dropdown');

				var categoryLink = $('<a>')
					.attr('href', categoryUrl)
					.text($.mage.__('All %1').replace('%1', category));

				var categoryParent = $('<li>')
					.addClass('level1 category-item hide-on-desktop all-category')
					.html(categoryLink);
				var newLinkWrapper = $('<div>').addClass('ox-mm-a-wrap').html(categoryParent);
				if (menu.find('.all-category').length === 0 && categoryUrl !== '#' && !item.hasClass('nolink')) {
					$(menu).find('.ox-megamenu-list').prepend(newLinkWrapper);
				}
			}, this));
		},
		_toggleList: function (btn) {
			var _self = this,
				$btn = $(btn),
				$li = $btn.parent(),
				$lvl = $li.find('> *:not(.ox-mm-a-wrap)');
				$lvl.css('height', '');
				$lvl.stop();
			if ($li.hasClass('ox-megamenu--opened')) {
				var $li_other = $li.find('.ox-megamenu--opened');

				$li.removeClass('ox-megamenu--opened');
				$lvl.animate({
					height: "toggle"
					}, _self.options.toggleTransitionDuration, function() {
						$li_other.removeClass('ox-megamenu--opened').removeAttr('style');
						$li_other.find('ul').removeAttr('style');
					});
			} else {
				$lvl.animate({
					height: "toggle"
					}, _self.options.toggleTransitionDuration);
				$li.addClass('ox-megamenu--opened');
				$('body').trigger('contentUpdated');
			}
		},
		_widthHandlers: function (obj) {
			var _self = this;
			
			$(window).on('resize.handlerswidth', function () {

				if (obj.desktopBp === _self.is_desktop)
					return; 
				else
					obj.desktopBp = _self.is_desktop; 

				var $elem = $(obj.elem),
					ns = obj.namespace ? '.' + obj.namespace : '';

				if (obj.desktop === _self.is_desktop) {
					$.each(obj.events, function (key, val) {
						$elem.on(key + ns, obj.delegate, val);
					});
				} else {
					$.each(obj.events, function (key) {
						$elem.off(key + ns);
					});
				}
			});
		},
		_setOption: function (key, value, is_attr) {
			if (is_attr && !value)
				return;

			$.Widget.prototype._setOption.apply(this, arguments);
		},
		destroy: function () {
			$.Widget.prototype.destroy.call(this);
		}
	});

	return $.ox.OxMegaMenu;
});
