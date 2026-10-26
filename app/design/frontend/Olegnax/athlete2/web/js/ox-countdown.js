define([
    'jquery',
    'jquery-ui-modules/widget'
], function ($) {
    'use strict';

    $.widget('mage.OXcountdown', {
        options: {
            relative: 0,
            endDate: 0,
            endTime: 0,
            timeZone: 0,
            timeZoneDiff: true,
            daysToHours: false,
            hideEl: false,
            bodyClass: '',
            hideClass: 'expired',
            loop: false
        },

        _create: function () {
            this._initializeCountdown();
        },
        _getVisitorTimeZoneOffset: function() {
            return ((new Date().getTimezoneOffset()) / 60);
        },
        _initializeCountdown: function () {
            const second = 1000,
                minute = 60000,
                hour = 3600000,
                day = 86400000;
            let inited = false;
            const { relative, endDate, endTime, timeZone, timeZoneDiff, daysToHours, hideEl, bodyClass, hideClass, loop } = this.options;
            const element = this.element;         
            if (!endDate || !endTime || !element) {
                return;
            }   
            const $el_days = element.find(".days .num");
            const $el_hours = element.find(".hours .num");
            const $el_minutes = element.find(".minutes .num");
            const $el_seconds = element.find(".seconds .num");
            
            if(daysToHours && $el_days.length){
                $el_days.parent().hide();
            }

            let timeOffset = 0;
            if(timeZoneDiff){
                timeOffset = (timeZone - this._getVisitorTimeZoneOffset()) * hour;
            }

            let countDown = 0; 
            let distance = 0;
            let elapsedTime = 0;

            if(relative){
                countDown = day * endDate + endTime * hour;
            } else{
                countDown = new Date(`${endDate} ${endTime}`).getTime();
            }
            const x = setInterval(function () {
                const now = new Date().getTime();
                if(relative){
                    elapsedTime += second;
                    distance = countDown - elapsedTime;

                } else{
                    distance = countDown - (now - timeOffset);
                }
                if (distance < 0) {
                    if(!loop){
                        element.addClass(hideClass);
                        if($(hideEl).length){
                            $(hideEl).addClass(hideClass);                       
                        }
                        if(bodyClass){
                            $('body').addClass(bodyClass);                       
                        }
                        clearInterval(x);
                    }else{
                        elapsedTime = 0; // Reset elapsed time
                    }

                } else{
                    if(daysToHours){
                        $el_hours.text(formatNum((distance / hour) % day));
                    } else{
                        $el_days.text(formatNum(distance / day));
                        $el_hours.text(formatNum((distance % day) / hour));
                    }
                    $el_minutes.text(formatNum((distance % hour) / minute));
                    $el_seconds.text(formatNum((distance % minute) / second));
                    if (!inited) {
                        element.addClass('inited');
                        inited = true;
                    }
                }
            }, second);

            function formatNum(number) {
                return String(Math.floor(number)).padStart(2, '0');
            }
        }
    });
});
