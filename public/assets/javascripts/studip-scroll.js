/*jslint browser: true, unparam: true */
/*global jQuery, STUDIP */

// (request/cancel)AnimationFrame polyfill,
// see https://gist.github.com/paulirish/1579671
(function () {
    'use strict';

    var lastTime = 0,
        vendors = ['ms', 'moz', 'webkit', 'o'],
        x;
    for (x = 0; x < vendors.length && !window.requestAnimationFrame; x += 1) {
        window.requestAnimationFrame = window[vendors[x] + 'RequestAnimationFrame'];
        window.cancelAnimationFrame = window[vendors[x] + 'CancelAnimationFrame']
                                   || window[vendors[x] + 'CancelRequestAnimationFrame'];
    }
    if (!window.requestAnimationFrame) {
        window.requestAnimationFrame = function (callback) {
            var currTime = new Date().getTime(),
                timeToCall = Math.max(0, 16 - (currTime - lastTime)),
                id;
            id = window.setTimeout(function () {
                callback(currTime + timeToCall);
            }, timeToCall);
            lastTime = currTime + timeToCall;
            return id;
        };
    }
    if (!window.cancelAnimationFrame) {
        window.cancelAnimationFrame = function (id) {
            clearTimeout(id);
        };
    }
}());

/**
 * Provides means to hook into the scroll event. Registered callbacks are
 * called with the current scroll top and scroll left position so both
 * vertical and horizontal scroll events can be handled.
 *
 * Updates/calls to the callback are synchronized to screen refresh by using
 * the animation frame method (which will fallback to a timer based solution).
 */
(function ($, STUDIP) {
    'use strict';

    STUDIP = STUDIP || {};

    var handlers  = {},
        animId    = false;

    function scrollHandler() {
        var scrollTop  = $(document).scrollTop(),
            scrollLeft = $(document).scrollLeft();
        $.each(handlers, function (index, handler) {
            handler(scrollTop, scrollLeft);
        });
        animId = window.requestAnimationFrame(scrollHandler);
    }

    function refresh() {
        var hasHandlers = !$.isEmptyObject(handlers);
        if (!hasHandlers && animId !== false) {
            window.cancelAnimationFrame(animId);
            animId = false;
        } else if (hasHandlers && animId === false) {
            animId = window.requestAnimationFrame(scrollHandler);
        }
    }

    STUDIP.Scroll = {};

    STUDIP.Scroll.addHandler = function (index, handler) {
        handlers[index] = handler;
        refresh();
    };
    STUDIP.Scroll.removeHandler = function (index) {
        delete handlers[index];
        refresh();
    };

}(jQuery, STUDIP));
