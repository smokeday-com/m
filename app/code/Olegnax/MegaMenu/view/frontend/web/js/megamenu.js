define([
	'jquery',
	'matchMedia',
	'jquery-ui-modules/widget',
	'jquery-ui-modules/core',
	'plugins/velocity',
	'plugins/scrollbar',
], function ($, mediaCheck) {
	'use strict';

	$.widget('ox.OxMegaMenu', {
		options: {
			direction: 'horizontal',
			positionHorizontal: 'left',
			btn: false,
			actionActive: 'active',
			header: '.container',
			toggleTransitionDuration: 400,
			mediaBreakpoint: 768,
			classNavigation: '.ox-megamenu-navigation',
			classDropdown: '.ox-megamenu__dropdown',
			classAllCats: 'ox-mm__list-all',
			classAllDD: 'ox-dd--all',
			dupParents: false,
			autoOpen: false,
			autoOpenLast: false,
			doNotClose: false,
			closeDelay: 0,
		},
		_alignFunctions: {
			'horizontal': {
				'container-left': function () {
					var $cont = $(this.options.header);
					if ($cont.length) {

						var cont_l = $cont[0].getBoundingClientRect().left,
							ox_megamenu_l = this.element[0].getBoundingClientRect().left;
						return {
							left: cont_l - ox_megamenu_l
						};
					} else {
						console.warn('Header container not found. Please set correct header selector in Olegnax / Megamenu / Configuration.')
					}
				},
				'container-right': function () {
					var $cont = $(this.options.header),
						cont_r = $cont[0].getBoundingClientRect().right,
						ox_megamenu_r = this.element[0].getBoundingClientRect().right;
					return {
						left: 'auto',
						right: ox_megamenu_r - cont_r
					};
				},
				'container-center': function () {
					var $cont = $(this.options.header),
						cont_l = $cont[0].getBoundingClientRect().left,
						cont_w = $cont.innerWidth(),
						ox_megamenu_l = this.element[0].getBoundingClientRect().left;

					return {
						left: cont_l - ox_megamenu_l + cont_w / 2
					};
				},
				'window-left': function () {
					var ox_megamenu_l = this.element[0].getBoundingClientRect().left;
					return {
						left: ox_megamenu_l * -1
					};
				},
				'window-right': function () {
					var windowWidth = window.innerWidth - this._scrollWidth,
						ox_megamenu_r = this.element[0].getBoundingClientRect().right;

					return {
						left: 'auto',
						right: (windowWidth - ox_megamenu_r) * -1
					};
				},
				'window-center': function () {
					var windowWidth = window.innerWidth - this._scrollWidth,
						ox_megamenu_l = this.element[0].getBoundingClientRect().left;

					return {
						left: ox_megamenu_l * -1 + windowWidth / 2
					};
				},
				'item-left': function () {
					var pos = this.currentButton.position();
					return {
						right:'auto',
						left: pos.left
					};
				},
				'item-right': function () {
					var pos = this.currentButton.position();
					return {
						right:'auto',
						left: pos.left + this.currentButton.outerWidth() - this.currentMegamenu.outerWidth()
					};
				},
				'item-center': function () {
					var pos = this.currentButton.position();
					return {
						right:'auto',
						left: pos.left + Math.round(this.currentButton.outerWidth() / 2) - Math.round(this.currentMegamenu.outerWidth() / 2)
					};
				},
				'define': 'menu-left',
			},
			'vertical': {
				'container-left': function () {
					var $cont = $(this.options.cont),
						cont_l = $cont[0].getBoundingClientRect().left,
						ox_megamenu_l = this.element[0].getBoundingClientRect().left,
						ox_megamenu_w = this.element.innerWidth();

					return {
						left: 'auto',
						right: ox_megamenu_l - cont_l + ox_megamenu_w
					};
				},
				'container-right': function () {
					var $cont = $(this.options.cont),
						cont_r = $cont[0].getBoundingClientRect().right,
						ox_megamenu_r = this.element[0].getBoundingClientRect().right,
						ox_megamenu_w = this.element.innerWidth();

					return {
						left: cont_r - ox_megamenu_r + ox_megamenu_w
					};
				},
				'define': 'container-left',
			}
		},
		_create: function () {
			var _self = this,
				$ox_megamenu = this.element,
				$ox_megamenu__navigation = $ox_megamenu.find(this.options.classNavigation),
				$ox_megamenu__dropdown = $ox_megamenu.find(this.options.classDropdown),
				$ox_megamenu__submenu = $ox_megamenu__dropdown.find('.ox-submenu');
				this.ps = $ox_megamenu.hasClass('ps-enabled');
				this.currentlyOpened = null;
			this._getScrollWidth();

			mediaCheck({
				media: '(min-width:' + this.options.mediaBreakpoint + 'px)',
				entry: $.proxy(function () {
					this._toggleDesktopMode();
				}, this),
				exit: $.proxy(function () {
					this._toggleMobileMode();
				}, this),
			});

				var $window = $(window);
				this._windowWidth = $window.innerWidth();
				var $megamenuSubmenu = $ox_megamenu__submenu.parent('li');
				var $megamenuWrap = $ox_megamenu.find('li.parent > .ox-mm-a-wrap, li.ox-dropdown--megamenu > .ox-mm-a-wrap');
			
				var isDesktop = _self.is_desktop;
			
				// Define event handlers
				var megamenuEvents = {
					'mouseenter': function () {  _self._showMM(this); },
					'mouseleave': function () { _self._hideMM(this); },
					'touchstart': function (e) {
						if ($(this).hasClass('ox-megamenu--opened') || ($(this).find('> a').data('url') === 'custom' && !$(this).hasClass('parent'))) {
							return;
						}
						e.preventDefault();
						_self._showMM(this);
					}
				};
			
				var submenuEvents = {
					'mouseenter': function () { _self._showDD(this); },
					'mouseleave': function () { _self._hideDD(this); },
					'touchstart': function (e) {
						if ($(this).hasClass('js-touch')) {
							return;
						}
						$(this).addClass('js-touch');
						e.preventDefault();
						_self._showDD(this);
					}
				};
			
				var wrapEvents = {
					'click': function (e) {
						if ((e.target.tagName === 'A' || e.target.tagName === 'SPAN') && $(e.target).parent().hasClass('ox-megamenu--opened') || !$(e.target).closest('li').eq(0).find('.submenu, .ox-submenu, .ox-megamenu__dropdown').length) {
							return;
						}
						_self._toggleList(this);
						e.preventDefault();
						return false;
					}
				};
			
				// Function to add or remove events based on desktop state
				function updateEventHandlers(isDesktop) {
					if (isDesktop) {
						// Add desktop events
						$ox_megamenu__navigation.on(megamenuEvents, '>li, li.ox-dropdown--megamenu');
						$megamenuSubmenu.on(submenuEvents);
						$megamenuWrap.off('click').removeClass('js-touch'); // Remove mobile events
						$megamenuSubmenu.removeAttr( 'style' );
					} else {
						// Add mobile events
						$ox_megamenu__navigation.off(megamenuEvents);
						$megamenuSubmenu.off(submenuEvents);
						$megamenuWrap.on(wrapEvents);
					}
				}
			
				// Initial setup
				updateEventHandlers(isDesktop);
			
				// Handle resize event with debounce
				$window.on('resize.handlerswidth oxMenuToggleMode', debounce(function() {
					let newIsDesktop = _self.is_desktop,
					newWindowWidth = $window.innerWidth();
					
					if (newIsDesktop !== isDesktop) {
						isDesktop = newIsDesktop;
						updateEventHandlers(isDesktop);
					}
					if(newWindowWidth !== _self._windowWidth){
						_self._windowWidth = newWindowWidth;
						$ox_megamenu__dropdown.removeAttr( 'style' );
					}

				}, 200));
			
				// Debounce function
				function debounce(func, wait) {
					var timeout;
					return function() {
						clearTimeout(timeout);
						timeout = setTimeout(func, wait);
					};
				}
			
			$(this.window).trigger('resize');
		},
		_getScrollWidth: function () {
			var $tempElement = $('<div>').css({
				overflowY: 'scroll',
				width: '50px',
				height: '50px',
				visibility: 'hidden'
			});
			$('body').append($tempElement);
			this._scrollWidth = $tempElement[0].offsetWidth - $tempElement[0].clientWidth;
			$tempElement.remove();
		},
		_toggleDesktopMode: function () {
			this.is_desktop = true;
			this._hideAllMM();
			this.element.find('.ox-megamenu--opened').removeClass('ox-megamenu--opened');
			this.element.trigger('oxMenuToggleMode');
		},
		_toggleMobileMode: function () {
			this.is_desktop = false;
			this._hideAllMM();
			var dropdown = this.element.find('.ox-megamenu__dropdown');
			dropdown.css({'width': '', 'left': ''});
			dropdown.find('.ox-megamenu-list').first().css({'width': ''});
			dropdown.find('.ox-megamenu-block-left, .ox-megamenu-block-right').css({'width': ''});
			this._duplicateParents();
			this.element.trigger('oxMenuToggleMode');
		},
		_duplicateParents: function () {
			if(!this.options.dupParents){
				return;
			}
			/* duplicate top level items */
			var subMenus = this.element.find('.level-top');
			$.each(subMenus, $.proxy(function (index, item) {
				var category = $(item).find('> .ox-mm-a-wrap a .name').text(),
					categoryUrl = $(item).find('> .ox-mm-a-wrap a').attr('href'),
					menu = $(item).find('> .ox-megamenu__dropdown');

				var categoryLink = $('<a>')
					.attr('href', categoryUrl)
					.text($.mage.__('All %1').replace('%1', category));

				var categoryParent = $('<li>')
					.addClass('level1 category-item hide-on-desktop all-category')
					.html(categoryLink);
				if (menu.find('.all-category').length === 0) {
					$(menu).find('.ox-megamenu-list').prepend(categoryParent);
				}
			}, this));
		},
		_hideAllMM: function () {
			this._hideMM(this.element.find(this.options.classNavigation).find('> li'), true, true);
		},
		_toggleList: function (btn) {
			var _self = this,
				$ox_megamenu = this.element,
				$btn = $(btn),
				$li = $btn.parent(),
				$lvl = $li.find('> *:not(.ox-mm-a-wrap)');

			$lvl.css('height', '');
			$lvl.velocity('stop');

			if ($li.hasClass('ox-megamenu--opened')) {
				var $li_other = $li.find('.ox-megamenu--opened');

				$li.removeClass('ox-megamenu--opened');

				$lvl.velocity('slideUp', {
					duration: _self.options.toggleTransitionDuration,
					complete: function () {
						$li_other.removeClass('ox-megamenu--opened').removeAttr('style');
						$li_other.find('ul').parent().removeAttr('style');
						if(this.ps){
							$ox_megamenu.perfectScrollbar('update');
						}
					}
				});
			} else {
				$lvl.velocity('slideDown', {
					duration: _self.options.toggleTransitionDuration,
					complete: function () {
						if(this.ps){
							$ox_megamenu.perfectScrollbar('update');
						}
					}
				});
				$li.addClass('ox-megamenu--opened');
				$('body').trigger('contentUpdated');
			}
		},
		_showFirst: function($element){
			if(this.options.autoOpen && $element.hasClass(this.options.classAllDD)){
				var $firstChild = (this.options.autoOpenLast && this.currentlyOpened) ? this.currentlyOpened : $element.find('.ox-mm__list-all > .level1:first-child');
				if($firstChild.length && $firstChild !== $element){
					if($firstChild.hasClass('ox-dropdown--megamenu')){
						this._showMM($firstChild, true)
					} else{
						this._showDD($firstChild, true)
					}
				}
			}
		},
		_switchCurrent: function($element, doNotHide, _self){
			if(_self.options.doNotClose && _self._isParentAllCats($element)){
				if(!doNotHide){
					_self._hideCurrent($element);
				}
				_self.currentlyOpened = $element;
			}
		},
		_hideCurrent: function($element){
			if(!$element.hasClass(this.options.classAllDD) && this.currentlyOpened && this.currentlyOpened[0] != $element[0]){
				if(this.currentlyOpened.hasClass('ox-dropdown--megamenu')){
					this._hideMM(this.currentlyOpened, true, true);
				} else{					
					this._hideDD(this.currentlyOpened, true)
				}
			}
		},
		_showMM: function (element, doNotHide) {
			const _self = this,
				$element = $(element),
				$mm = $element.children('.ox-megamenu__dropdown');

			if (!$mm.length){
				return;
			}

			const $mmp = $mm.parents('.ox-megamenu__dropdown').add($mm),
				$ox_megamenu__dropdown = this.element.find('.ox-megamenu__dropdown'),
				$ox_megamenu__btns = $ox_megamenu__dropdown.parent('li'),
				$mmpBtn = $mmp.parent('li').add($element);
				
			_self._switchCurrent($element, doNotHide, _self);

			$mm.off('transitionend.mmclose');
			_self.hideMM($ox_megamenu__btns.not($mmpBtn), $ox_megamenu__dropdown.not($mmp));

			if (this.options.beforeOpenMM)
				this.options.beforeOpenMM($mm, $element);

			this.currentButton = $element;
			this.currentMegamenu = $mm;
			this.currentInner =  $mm.children('.ox-mm-overflow');

			$element.addClass('ox-megamenu--opened');
			$mm.addClass('opened');
			if($element.hasClass('level0')){
				$('body').addClass('ox-mm-opened');
			}
			this._beforeOpenMM();
			var $menu_item = $element.parents('.category-item:first');
			if($menu_item.length && $element.hasClass('ox-dropdown--megamenu') && !($menu_item.hasClass(this.options.classAllDD) && $menu_item.hasClass('ox-dropdown--megamenu'))){
				this.setVerticalAlign();
			}

			$mm.one('transitionend.mmopen', function () {
				_self._afterOpenMM();
				_self._showFirst($element);
			});

			$mm.find('.owl-carousel:not(.owl-mm-reloaded)').trigger('refresh.owl.carousel').addClass('owl-mm-reloaded');
			$mm.addClass('animate');
		},
		_isParentAllCats: function ($element){
			return $element.parent().hasClass(this.options.classAllCats);
		},
		_hideMM: function (btn, forced, is_fast) {
			const _self = this,
				$btn = $(btn),
				$mm = $btn.find('.ox-megamenu__dropdown');
			let isAllCatsChild = _self._isParentAllCats($btn);
			
			if (!$mm.length || (this.options.doNotClose && !forced && isAllCatsChild))
				return;
			$mm.off('transitionend.mmopen');

			if (this.options.beforeCloseMM)
				this.options.beforeCloseMM($mm, $btn);

			this.currentButton = $btn;
			this.currentMegamenu = $mm;
			this.currentInner =  $mm.children('.ox-mm-overflow');

			this._beforeCloseMM();

			$mm.one('transitionend.mmclose', function () {
				_self.hideMM($btn,$mm);
				$mm.removeClass('opened animate');
				_self._afterCloseMM();
			});
			if(!isAllCatsChild && $btn.hasClass('level0')){
				$('body').removeClass('ox-mm-opened');
			}
			if (is_fast){
				$mm.trigger('transitionend.mmclose');
			} else{
				setTimeout(function () {
					$mm.trigger('transitionend.mmclose');
				}, _self.options.closeDelay);
			}
		},
		hideMM: function($btn, $mm){
			$mm.removeClass('opened animate');
			$btn.removeClass('ox-megamenu--opened');
		},
		_isNumeric: function (val) {
			return Number(parseFloat(val)) === val;
		},
		setVerticalAlign: function() {
			if(!(this.currentButton.hasClass('ox-mm__lvl1-top') || this.currentButton.hasClass('ox-mm__lvl1-top-stretch'))){
				let pos = this.currentButton.position(), leftPos = '', topPos = '';
				if(this.currentButton.hasClass('ox-mm__lvl1-right')){
					topPos = pos.top - 20;
				} else{
					leftPos = pos.left + (Math.round(this.currentButton.outerWidth() * 0.8)),
					topPos = pos.top - 20;
				}
				this.currentMegamenu.css({
					top: topPos,
					left: leftPos
				});
			}
		},
		_beforeOpenMM: function () {
			var _self = this;
			function setWidth() {
				var mmWidth = _self.currentMegamenu.data('ox-mm-w') || _self.currentMegamenu.data('oxMmW'),
					w_funcs = _self._widthFunctions[_self.options.direction],
					set_w;

				if (w_funcs.hasOwnProperty(mmWidth))
					set_w = w_funcs[mmWidth].apply(_self, arguments);
				else if (_self._isNumeric(mmWidth))
					set_w = mmWidth;

				if (set_w) {
					if (mmWidth === 'column-max-width') {
						var mm_list = _self.currentMegamenu.find('.ox-megamenu-list').first();
						var mm_block_left = _self.currentMegamenu.find('.ox-megamenu-block-left');
						var mm_block_right = _self.currentMegamenu.find('.ox-megamenu-block-right');
						mm_list.innerWidth(set_w);
						mm_block_left.innerWidth(set_w);
						mm_block_right.innerWidth(set_w);
						_self.currentMegamenu.css('width', 'auto');
					} else {
						_self.currentMegamenu.innerWidth(set_w);
					}
				}
			}
			function setAlign(attr) {
				var align = _self.currentButton.data(attr),
					align_func = _self._alignFunctions[_self.options.direction],
					align = align || align_func['define'],
					css;
				if (align_func.hasOwnProperty(align)) {
					css = align_func[align].apply(_self);
					if (css) {
						_self.currentMegamenu.css(css);
					}
				}
			}

			function checkWidth() {
				var $mm = _self.currentMegamenu,
					mmLeft = $mm[0].getBoundingClientRect().left,
					mmRight = $mm[0].getBoundingClientRect().right,
					mmWidth = $mm.innerWidth(),
					windowWidth = window.innerWidth - _self._scrollWidth;

				if (mmWidth > windowWidth) {
					if (mmLeft > 0) {
						$mm.innerWidth(windowWidth - mmLeft);
					} else if (mmRight < windowWidth) {
						$mm.innerWidth(windowWidth - (windowWidth - mmRight));
					} else {
						$mm.innerWidth(windowWidth);
					}
				} else if (mmLeft < 0) {
					$mm.innerWidth(mmWidth + mmLeft);
				} else if (mmRight > windowWidth) {
					$mm.innerWidth(mmWidth - (mmRight - windowWidth));
				}
			}
			setWidth();

			setAlign('ox-mm-a-h');

			checkWidth();
			
			this._toggleTransition('show');
		},
		_afterOpenMM: function () {
			this._checkWindowLimit();
			this._toggleTransition('show');
		},
		_beforeCloseMM: function () {
			this._toggleTransition('hide');
		},
		_afterCloseMM: function () {
			var $mm_inner = this.currentInner;
			if($mm_inner.length){
				$mm_inner.css('max-height', '');
				if(this.ps && $mm_inner.hasClass('ps')){
					$mm_inner.perfectScrollbar('destroy').removeClass('ps');
				}
			}
		},
		_widthFunctions: {
			'horizontal': {
				'menu': function () {
					return this.element.innerWidth();
				},
				'fullwidth': function () {
					return window.innerWidth - this._scrollWidth;
				},
				'container': function () {
					return $(this.options.header).innerWidth();
				},
				'column-max-width': function () {
					var mm_cw = this.currentMegamenu.data('ox-mm-cw') || this.currentMegamenu.data('oxMmCw'),
						mm_col = this.currentMegamenu.data('ox-mm-col') || this.currentMegamenu.data('oxMmCol');
					if (mm_cw) {
						if (mm_col) {
							return mm_cw * mm_col;
						} else {
							return mm_cw;
						}
					}
				},
				'custom': function (mm) {
					return this.currentMegamenu.data('ox-mm-cw') || this.currentMegamenu.data('oxMmCw');
				},
			},
			'vertical': {
				'fullwidth': function () {
					switch (this.options.positionHorizontal) {
						case 'right':
							return window.innerWidth - this._scrollWidth - (window.innerWidth - this._scrollWidth - this.element[0].getBoundingClientRect().left);
						default:
							return window.innerWidth - this.element[0].getBoundingClientRect().right - this._scrollWidth;
					}
				},
			},
		},
		_toggleTransition: function (act) {
			var $btn = this.currentButton,
				$mm = this.currentMegamenu,
				$trns = $btn.find('.ox-megamenu-trns');

			switch (act) {
				case 'show':
					var set_obj = {},
						btn_pos = $btn[0].getBoundingClientRect(),
						mm_pos = $mm[0].getBoundingClientRect();

					if (this.options.direction === 'horizontal') {
						set_obj.width = $mm.innerWidth();
						set_obj.height = mm_pos.top - btn_pos.bottom + 10;

						if (mm_pos.left > btn_pos.left) {
							set_obj.width += mm_pos.left - btn_pos.left;
						} else if (mm_pos.right < btn_pos.right) {
							set_obj.width += btn_pos.right - mm_pos.right;
							set_obj.left = (btn_pos.left - mm_pos.left) * -1;
						} else {
							set_obj.left = (btn_pos.left - mm_pos.left) * -1;
						}

					} else if (this.options.direction === 'vertical') {
						set_obj.height = $mm.innerHeight();

						if (this.options.positionHorizontal === 'left') {
							set_obj.width = mm_pos.left - btn_pos.right;

						} else if (this.options.positionHorizontal === 'right') {
							set_obj.width = btn_pos.left - mm_pos.right;
						}

						if (mm_pos.top > btn_pos.top) {
							set_obj.height += mm_pos.top - btn_pos.top;
						} else if (mm_pos.bottom < btn_pos.bottom) {
							set_obj.height += btn_pos.bottom - mm_pos.bottom;
							set_obj.top = (btn_pos.top - mm_pos.top) * -1;
						} else {
							set_obj.top = (btn_pos.top - mm_pos.top) * -1;
						}
					}

					$trns.css(set_obj);
					break;

				case 'hide':
					$trns.removeAttr('style');
					break;
			}
		},
		_checkWindowLimit: function () {
			if (this.options.direction !== 'horizontal')
				return;

			var $mm = this.currentMegamenu,
				$mm_inner = this.currentInner;
			if($mm_inner.length){
				var mm_b = $mm[0].getBoundingClientRect().bottom,
				wind_h = window.innerHeight;

				if (mm_b > wind_h) {
					var mm_h = $mm.innerHeight();
					$mm_inner.css({'max-height': mm_h - (mm_b - wind_h)});
					if(this.ps){
						$mm_inner.perfectScrollbar({
							suppressScrollX: true
						});
					}
				}
			}
		},
		_showDD: function (btn, doNotHide) {
			const $btn = $(btn),
				$dd = $btn.find('> .submenu'),
				$mm_inner = this.currentInner;
				if(!$dd.length){
					return;
				}
			this._switchCurrent($btn, doNotHide, this);

			$dd.off('transitionend.ddclose');
			$dd.addClass('opened');
			$btn.addClass('opened');
			if($btn.hasClass('level0')){
				$('body').addClass('ox-mm-opened');
			}
		
			var menuItemPos = $btn.position();
			$dd.css({
				top: menuItemPos.top - 20,
				left: menuItemPos.left + (Math.round($btn.outerWidth() * 0.8))
			});
			if ($mm_inner.length && $mm_inner.hasClass('ps')) {
				$mm_inner.perfectScrollbar('update');
			} else {
				var dd_pos = $dd[0].getBoundingClientRect(),
					windowWidth = window.innerWidth - this._scrollWidth,
					dd_lim_l = (dd_pos.left - $dd.innerWidth() - $dd.parents('.submenu').first().innerWidth()) < 0,
					dd_lim_r = dd_pos.right > windowWidth,
					is_prnt_reverse = $dd.parents('.submenu').first().hasClass('ox-megamenu-dd--reverse');
				if (dd_lim_r || (is_prnt_reverse && !dd_lim_l)){
					$dd.addClass('ox-megamenu-dd--reverse');
				}
			}
			$dd.addClass('animate');
		},
		_hideDD: function (btn, forced) {
			const $btn = $(btn),
				$dd = $btn.find('> .submenu'),
				$mm_inner = this.currentInner;
				let isAllCatsChild = this._isParentAllCats($btn);
			if(!$dd.length || (this.options.doNotClose && !forced && isAllCatsChild)){
				return;
			}
			$dd.one('transitionend.ddclose', function () {
				$btn.removeClass('opened');
				$dd.removeClass('opened ox-megamenu-dd--reverse').css('max-height', '');
				if ($mm_inner.hasClass('ps'))
					$mm_inner.perfectScrollbar('update');
			});
			if(!isAllCatsChild && $btn.hasClass('level0')){
				$('body').removeClass('ox-mm-opened');
			}
			$dd.removeClass('animate');
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
