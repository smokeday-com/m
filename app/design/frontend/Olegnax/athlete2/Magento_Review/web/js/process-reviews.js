/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'tabs',
    'collapsible'
], function ($) {
    'use strict';

    /**
     * @param {String} url
     * @param {*} fromPages
     * @returns {jqXHR}
     */
    function processReviews(url, fromPages) {
        /*
         * 2026-10-29 Dmitrii Fediuk https://github.com/dmitrii-fediuk
         * "Defer product reviews AJAX request (Magento_Review/js/process-reviews) via IntersectionObserver until #reviews approaches the viewport or review links are clicked":
         * https://github.com/smokeday-com/m/issues/43
         */
        return $.ajax({
            url: url,
            cache: true,
            dataType: 'html',
            showLoader: false,
            loaderContext: $('.product.data.items')
        }).done(function (data) {
            $('#product-review-container').html(data).trigger('contentUpdated');
            $('[data-role="product-review"] .pages a').each(function (index, element) {
                $(element).on('click', function (event) {
                    //eslint-disable-line max-nested-callbacks
                    processReviews($(element).attr('href'), true);
                    event.preventDefault();
                });
            });
        }).always(function () {
            if (fromPages == true) { //eslint-disable-line eqeqeq
                $('html, body').animate({
                    scrollTop: $('#reviews').offset().top - 50
                }, 300);
            }
        });
    }

    return function (config) {
        /*
         * 2026-10-29 Dmitrii Fediuk https://github.com/dmitrii-fediuk
         * "Defer product reviews AJAX request (Magento_Review/js/process-reviews) via IntersectionObserver until #reviews approaches the viewport or review links are clicked":
         * https://github.com/smokeday-com/m/issues/43
         */
        var reviewTab = $(config.reviewsTabSelector),
            requiredReviewTabRole = 'tab',
            reviewsLoaded = false,
            reviewsXhr = null;

        function loadReviewsOnce(fromPages) {
            if (!reviewsLoaded) {
                reviewsLoaded = true;
                reviewsXhr = processReviews(config.productReviewUrl, fromPages);
            }
            return reviewsXhr;
        }

        if (reviewTab.length) {
            if (reviewTab.attr('role') === requiredReviewTabRole && reviewTab.hasClass('active')) {
                loadReviewsOnce(location.hash === '#reviews');
            } else {
                reviewTab.one('beforeOpen', function () {
                    loadReviewsOnce(false);
                });
            }
        } else if (location.hash === '#reviews' || location.hash === '#review-form') {
            loadReviewsOnce(location.hash === '#reviews');
        } else if (typeof window !== 'undefined') {
            var observeReviews = function () {
                var target = document.getElementById('reviews') || document.getElementById('product-review-container'),
                    observer = null,
                    cleaned = false;
                if (!target) {
                    loadReviewsOnce(false);
                    return;
                }
                function cleanup() {
                    if (cleaned) {
                        return;
                    }
                    cleaned = true;
                    if (observer) {
                        observer.disconnect();
                        observer = null;
                    }
                    window.removeEventListener('scroll', checkPosition);
                    window.removeEventListener('resize', checkPosition);
                }
                function triggerLoad() {
                    cleanup();
                    loadReviewsOnce(false);
                }
                function checkPosition() {
                    if (reviewsLoaded) {
                        cleanup();
                        return;
                    }
                    var rect = target.getBoundingClientRect(),
                        viewHeight = window.innerHeight || document.documentElement.clientHeight || 0;
                    if (rect.top <= viewHeight + 300 && rect.bottom >= -300) {
                        triggerLoad();
                    }
                }
                if ('IntersectionObserver' in window) {
                    observer = new window.IntersectionObserver(function (entries) {
                        for (var i = 0; i < entries.length; i++) {
                            if (entries[i].isIntersecting || entries[i].intersectionRatio > 0) {
                                triggerLoad();
                                break;
                            }
                        }
                    }, {
                        rootMargin: '300px 0px'
                    });
                    observer.observe(target);
                }
                window.addEventListener('scroll', checkPosition, { passive: true });
                window.addEventListener('resize', checkPosition, { passive: true });
                checkPosition();
            };
            if (document.getElementById('reviews') || document.getElementById('product-review-container')) {
                observeReviews();
            } else {
                $(observeReviews);
            }
        } else {
            loadReviewsOnce(false);
        }
        $(function () {
            $(':is(#ox-reviews-mini, .product-info-main) .reviews-actions a').on('click', function (event) {
                var anchor, addReviewBlock, hadLoadedContent, xhr;

                event.preventDefault();
                anchor = $(this).attr('href').replace(/^.*?(#|$)/, '');
                addReviewBlock = $('#' + anchor);
                hadLoadedContent = $.trim($('#product-review-container').html()).length > 0;
                xhr = loadReviewsOnce(false);
                $('.product.data.items [data-role="content"]').each(function (index) {
                    //eslint-disable-line
                    if (this.id == 'reviews') { //eslint-disable-line
                        let $wrap = jQuery('.product.data.items');
                        if ($wrap.data('mageAccordion') || $wrap.data('mage-Accordion')) {
                            $('.product.data.items').accordion('activate', index);
                        } else {
                            $('.product.data.items').tabs('activate', index);
                        }
                    }
                });
                if (addReviewBlock.length) {
                    $('html, body').animate({
                        scrollTop: addReviewBlock.offset().top - 50
                    }, 300);
                }
                if (!hadLoadedContent && xhr && typeof xhr.always === 'function') {
                    xhr.always(function () {
                        var $target = $('#' + anchor);
                        if ($target.length) {
                            $('html, body').stop(true).animate({
                                scrollTop: $target.offset().top - 50
                            }, 200);
                        }
                    });
                }
            });
        });
    };
});

