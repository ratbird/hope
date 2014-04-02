/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */

/* ------------------------------------------------------------------------
 * JSUpdater
 * ------------------------------------------------------------------------ */

STUDIP.JSUpdater = {
    lastAjaxDuration: 200, //ms of the duration of an ajax-call
    currentDelayFactor: 0,
    lastJsonResult: {},
    dateOfLastCall: new Date(),
    idOfCurrentQueue: "",
    ajaxRequestPending: false,

    processUpdate: function (json) {
        jQuery.each(json, function (index, value) {
            index = index.split(".");
            var func = STUDIP;
            while (index.length > 0) {
                if (!func[index[0]]) {
                    break;
                }
                func = func[index.shift()];
            }
            if (typeof func === "function") {
                func(value);
            }
        });
    },

    /**
     * function to generate a queue of repeated calls
     * @call_id : id of the call-queue
     */
    call: function (queue_id) {
        if (queue_id !== STUDIP.JSUpdater.idOfCurrentQueue) {
            //stop this queue if there is another one
            return false;
        }
        if (STUDIP.JSUpdater.ajaxRequestPending) {
            STUDIP.JSUpdater.nextCall(queue_id);
            return false;
        }
        
        STUDIP.JSUpdater.ajaxRequestPending = true;
        STUDIP.JSUpdater.dateOfLastCall = new Date();
        var page = window.location.href.replace(STUDIP.ABSOLUTE_URI_STUDIP, ""),
            page_info = {},
            url = STUDIP.ABSOLUTE_URI_STUDIP + 'dispatch.php/jsupdater/get';
        jQuery.each(STUDIP, function (index, element) {
            if (typeof element.periodicalPushData === "function") {
                page_info[index] = element.periodicalPushData();
            }
        });
        jQuery.ajax(url, {
            data: {
                page: page,
                page_info: page_info
            },
            dataType: 'json',
            timeout: 5000
        }).done(function (json) {
            STUDIP.JSUpdater.resetJsonMemory(json);
            STUDIP.JSUpdater.processUpdate(json);
        }).fail(function (jqXHR, textStatus, errorThrown) {
            STUDIP.JSUpdater.resetJsonMemory({ 'text' : textStatus, 'error': errorThrown });
        }).always(function () {
            STUDIP.JSUpdater.ajaxRequestPending = false;
            STUDIP.JSUpdater.nextCall(queue_id);
        });
    },
    resetJsonMemory: function (json) {
        json = JSON.stringify(json);
        if (json !== STUDIP.JSUpdater.lastJsonResult) {
            STUDIP.JSUpdater.currentDelayFactor = 0;
        }
        STUDIP.JSUpdater.lastJsonResult = json;
        var now = new Date();
        STUDIP.JSUpdater.lastAjaxDuration = Number(now) - Number(STUDIP.JSUpdater.dateOfLastCall);
    },
    nextCall: function (queue_id) {
        var pause_time = STUDIP.JSUpdater.lastAjaxDuration *
            Math.pow(1.33, STUDIP.JSUpdater.currentDelayFactor) *
            15; //bei 200 ms von einer Anfrage, sind das mindestens 4 Sekunden bis zum n�chsten Request
        window.setTimeout(function () {
            STUDIP.JSUpdater.call(queue_id);
        }, pause_time);
        STUDIP.JSUpdater.currentDelayFactor += 1;
    }
};
jQuery(function () {
    if (STUDIP.jsupdate_enable) {
        jQuery("body").bind("mousemove", function () {
            STUDIP.JSUpdater.currentDelayFactor = 0;
            if (Number(new Date()) - Number(STUDIP.JSUpdater.dateOfLastCall) > 5000) {
                STUDIP.JSUpdater.idOfCurrentQueue = Math.floor(Math.random() * 1000000);
                STUDIP.JSUpdater.call(STUDIP.JSUpdater.idOfCurrentQueue);
            }
        });
        STUDIP.JSUpdater.idOfCurrentQueue = Math.floor(Math.random() * 1000000);
        STUDIP.JSUpdater.nextCall(STUDIP.JSUpdater.idOfCurrentQueue);
    }
});
