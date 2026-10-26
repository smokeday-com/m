/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* Theme JS */
require([
    'jquery',
    'mage/cookies',
    'domReady!'
], function ($) {
    'use strict';
    // scroll to and open tabs
    $('body').on('click', '.js-tab-link', function(e){
        e.preventDefault();
        var anchor = $(this).attr('href');
        if(!anchor && !$(anchor).length && anchor !== '#'){
            return;
        }
        if ($('.product.data.items '+ anchor +'[data-role="content"]').length) {
            $('.product.data.items [data-role="content"]').each(function (index) {
                if (this.id == anchor.substring(1)) {
                    let $wrap = jQuery('.product.data.items');
                    if ($wrap.data('mageAccordion') || $wrap.data('mage-Accordion')) {
                        $('.product.data.items').accordion('activate', index);
                    } else {
                        $('.product.data.items').tabs('activate', index);
                    }
                }
            });
        }
        $('html, body').animate({
            scrollTop: $(anchor).offset().top - 200
        }, 200);
    });
    /* dynamic input size for overlay search */
    $.fn.textWidth = function (text, font) {
        if (!$.fn.textWidth.fakeEl) $.fn.textWidth.fakeEl = $('<span>').hide().appendTo(document.body);
        $.fn.textWidth.fakeEl.text(text || this.val() || this.text() || this.attr('placeholder')).css({
            'font': font || this.css('font'),
            'text-transform': 'uppercase',
            'letter-spacing': this.css('letter-spacing')
        });
        return $.fn.textWidth.fakeEl.width();
    };
    $('.width-dynamic').on('input', function () {
        var inputWidth = $(this).textWidth() + 40; //40 is padding
        $(this).css({
            width: inputWidth
        })
    }).trigger('input');
    $('.width-dynamic').on('blur',function () {
        if (!$(this).val().length) {
            $(this).css({
                width: ''
            })
        }
    });

    /* qty */
    const qtyD = {
        min: 0,
        max: 10000000,
        i: 1
    };
    let qtyMin= qtyD.min,
        qtyMax= qtyD.max,
        qtyIncrements= qtyD.i;
    function qtyValidate(_input){
        var data = _input.data('validate');
        qtyMin = qtyD.min;
        qtyMax = qtyD.max;
        qtyIncrements = qtyD.i;
        if (data && data['validate-item-quantity']) {
            qtyIncrements = data['validate-item-quantity']['qtyIncrements'] ?? qtyD.i;
            qtyMax = data['validate-item-quantity']['maxAllowed'] ?? qtyD.max;
        }
        qtyMin = _input.attr('min') ?? qtyD.min;
        return 
    }
    /* input plus minus */
    $("body").on('click', ".qty-controls-wrap .qty-plus", function (event) {
        event.preventDefault();
        const _input = $(this).parent().find('input');
        if(_input.length){
            qtyValidate(_input);
            _input.val(parseInt(_input.val()) + qtyIncrements);
            _input.change();
            $(this).parent().find('button.update-cart-item').show();
        }
    });

    $("body").on('click', ".qty-controls-wrap .qty-minus", function (event) {
        event.preventDefault();
        const _input = $(this).parent().find('input'); 
        if(_input.length){
            qtyValidate(_input);
            var count = parseInt(_input.val()) - qtyIncrements;
            count = count < 0 ? qtyMin : count;
            _input.val(count).change();
            $(this).parent().find('button.update-cart-item').show();
        }
    });
       
    $("body").on('change', ".qty-controls-wrap input", function () {
        const $this = $(this);
        qtyValidate($this);
        var val = parseInt($this.val(), 10);
        val = Math.min(val, qtyMax);
        val = Math.max(val, qtyMin);
        if (isNaN(val)) {
            val = qtyMin;
        }
        $this.val(Math.round(val / qtyIncrements) * qtyIncrements);
        $this.trigger('keypress');
    });


    /* Filter toggle */
    var item = $('.block.filter, .filters-slideout-content').find('.filter-options, .filter-current');

    if (item.length) {
        $(document).on('click', '.filter-options-title, .filter-current-subtitle', function (e) {
            e.preventDefault();
            var speed = 300;
            var thisParent = $(this).parent(),
                nextLevel = $(this).next('.filter-options-content, .ox-toggle-content');
            if (thisParent.hasClass('collapsible')) {
                if (thisParent.hasClass('open')) {
                    thisParent.removeClass('open');
                    nextLevel.slideUp(speed, function () {
                        $('body').trigger('oxToggleUpdated');
                    });
                } else {
                    thisParent.addClass('open');
                    nextLevel.slideDown(speed, function () {
                        $('body').trigger('oxToggleUpdated');
                    });
                }
            }
        })
    }

    $.fn.ox_toggles = function () {
        $(this).on('click', '.ox-toggle-title', function (e) {
            e.preventDefault();
            var $title = $(this);
            var $parentToggle = $title.parent();
            if ($parentToggle.hasClass('collapsible')) {
                var isOpen = !$parentToggle.hasClass('open'); // Toggle open state
                $parentToggle.toggleClass('open', isOpen).attr('aria-expanded', isOpen); // Toggle open class and aria-expanded
                var $content = $title.next('.ox-toggle-content');
                $content.attr('aria-hidden', !isOpen).css('max-height', isOpen ? $content[0].scrollHeight : 0);
                setTimeout(function () {
                    $('body').trigger('oxToggleUpdated');
                }, 300);
            }
        });
    };    
    $('.ox-toggle').ox_toggles();

    /* Collapsible text */
    $('body').off('click.OXExpand').on('click.OXExpand', '.ox-expand .ox-expand__link', function (e) {
        e.preventDefault();
        var $this = $(this).closest('.ox-expand').eq(0),
            max_height = $this.data('max-height') || 90,
            isMin = $this.hasClass('minimized');
        $this.attr('aria-expanded', !isMin)
        $this.toggleClass('minimized', !isMin)
        .children('.ox-expand__inner')
        .attr("aria-hidden", isMin)
        .css('max-height', isMin ? '100%' : max_height);
    });
    $(function () {
        $('.ox-expand').each(function () {
            let $this = $(this),
                $inner = $this.find('.ox-expand__inner'),
                max_height = $this.data('max-height') || 90;
            if (parseInt($inner.css('height')) < max_height) {
                let isMin = true;
                $this.attr('aria-expanded', !isMin)
                $this.toggleClass('minimized', !isMin)
                .children('.ox-expand__inner')
                .attr("aria-hidden", isMin)
                .css('max-height', isMin ? '100%' : max_height);
                $this.find('.ox-expand__link').hide();
            }
        });
    });
});

require(['jquery', 'matchMedia'], function ($) {
    $(function () {
        var _this = '.header__item-account-links',
            _class = 'store.links',
            mediaBreakpoint = '(min-width: 1025px)';
        if (_this.length) {
            mediaCheck({
                media: mediaBreakpoint,
                entry: function () {
                    var $this = $(_this),
                        $desktop_parent = $('[data-move-desktop="header.myaccount"]').eq(0),
                        position = $this.data('moveDesktopPosition') || 0;
                    if (!$desktop_parent.length || $this.parent().is($desktop_parent)) {
                        return;
                    }

                    var element = $this.detach();
                    if (0 < position) {
                        var prev = $desktop_parent.children().eq(position - 1);
                        if (prev.length) {
                            prev.after(element);
                        } else {
                            $desktop_parent.append(element);
                        }
                    } else {
                        $desktop_parent.append(element);
                    }
                },
                exit: function () {
                    var $this = $(_this),
                        $mobile_parent = $('[data-move-mobile="' + _class + '"]').eq(0);
                    if (!$this.length || !$mobile_parent.length || $this.parent().is($mobile_parent)) {
                        return;
                    }
                    $this.data('moveDesktopPosition', $this.parent().children().index($this));
                    var element = $this.detach();
                    $mobile_parent.append(element);
                }
            });
        }
    });
});

/* resize fullheight menu if its width bigger than the header area */
require(["jquery", "domReady!"], function ($) {
    "use strict";
    $(function () {
        const body = document.body;
            if (!body.matches('.menu-style-2,.menu-style-4,.menu-style-5')){
                return;
            }
        const headerContent = $('.page-header'),
            headerLeft = headerContent.find('.ox-megamenu-navigation');
        var calcMenuWidth = function () {
            if (headerLeft.length && window.innerWidth > 1024) {
                let count = 0,
                    padding = body.matches('.menu-style-5') ? 24 : 0;
                headerLeft.find("> li").each(function () {
                    count += $(this).outerWidth(true);
                });
                $('body').toggleClass('ox-mm-resize', (headerLeft.innerWidth() - padding) < count);
            }
        };
        calcMenuWidth();
        $(window).on('resize', function () {
            requestAnimationFrame(calcMenuWidth);
        });
    });
});

require(["jquery"], function ($) {
    "use strict";
    $(function () {
        function searchInputFocus() {
            $(".js-input-focus").each(function () {
                if ($(this).val().length) {                    
                    searchInputFocusTrigger(this);
                }
            });
        }
        searchInputFocus();
        $('body').on('modal_opened.OXmodal', function(){
            var $searchModal = $(".ox-slideout-top ");
            if($searchModal.length){
                if ($searchModal.hasClass('active')) {
                    searchInputFocus();
                }
            }
        });
        function searchInputFocusTrigger(element) {
           $(element).closest(".control").addClass('input-focused');
            $("html").addClass('ox-search-focused');
        }
        $(document).on('focus', '.js-input-focus',  function(){
            searchInputFocusTrigger(this);
        }).on('blur', '.js-input-focus', function () {
            if (!$(this).val().length) {
                $(this).closest(".control").removeClass('input-focused');
                $("html").removeClass('ox-search-focused');

            }
        })

    });
});

require(["jquery"], function ($) {
    "use strict";
    $(function () {
        //check device type
        //touch on IOS and Android
        var isAndroid = /(android)/i.test(navigator.userAgent);
        var isMobile = /(mobile)/i.test(navigator.userAgent);
        if (navigator.userAgent.match(/(iPod|iPhone|iPad)/) || isAndroid || isMobile) {
            $('html').addClass('touch').removeClass('no-touch');
        } else {
            $('html').addClass('no-touch').removeClass('touch');
        }

    });
});

(() => {
    const toTop = document.getElementById("toTop");
    if (toTop !== null) {
        window.addEventListener('scroll', function () {
            if (window.scrollY > 100) {
                toTop.style.display = "block";
            } else {
                toTop.style.display = "none";
            }
        }, { passive: true });

        toTop.addEventListener("click", function () {
            window.scrollTo({
                top: 0,
                behavior: "smooth"
            });
            return false;
        });
    }
})();