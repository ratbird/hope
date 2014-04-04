/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */

/* ------------------------------------------------------------------------
 * JSUpdater - periodically polls for new data from server
 * ------------------------------------------------------------------------
 * Exposes the following method on the global STUDIP.JSUpdater object:
 *
 * - start()
 * - stop()
 * - register(index, callback, data)
 * - unregister(index)
 *
 * Refer to the according function definitions for further info.
 * ------------------------------------------------------------------------ */

(function ($, STUDIP) {
    var active = false,
        lastAjaxDuration = 200, //ms of the duration of an ajax-call
        currentDelayFactor = 0,
        lastJsonResult = null,
        dateOfLastCall = +(new Date()), // Get milliseconds of date object
        ajaxRequest = null,
        timeout = null,
        registeredHandlers = {};

    // Reset json memory, used to delay polling if consecutive requests always
    // return the same result
    function resetJSONMemory(json) {
        json = JSON.stringify(json);
        if (json !== lastJsonResult) {
            currentDelayFactor = 0;
        }
        lastJsonResult = json;
    }

    // Process returned json object by calling registered handlers
    function process(json) {
        $.each(json, function (index, value) {
            // Call registered handler callback by index
            if (registeredHandlers.hasOwnProperty(index)) {
                registeredHandlers[index].callback(value);
                return;
            }

            // Legacy: Iterate over global STUDIP object and try to locate
            // the function to call by it's index in the resulting json
            // object
            var func  = STUDIP,
                nodes = index.split('.'),
                node = nodes.shift();
            while (node && func.hasOwnProperty(node)) {
                func = func[node];
                node = nodes.shift();
            }
            if (nodes.length === 0 && $.isFunction(func)) {
                func(value);
            }
        });

        // Reset json memory
        resetJSONMemory(json);
    }

    // Registers next poll
    function registerNextPoll() {
        // Define delay by last poll request (respond to load on server) and
        // current delay factor (respond to user activity)
        var delay = lastAjaxDuration * Math.pow(1.33, currentDelayFactor) * 15;

        // Clear any previously scheduled polling
        window.clearTimeout(timeout);
        timeout = window.setTimeout(poll, delay);
        
        // Increase current delay factor
        currentDelayFactor += 1;
    }

    // Collect data for polling
    function collectData() {
        var data = {};
        // Legacy: Pull data from periodicalPushData-methods attached to objects
        // on global STUDIP object
        $.each(STUDIP, function (index, element) {
            if ($.isFunction(element.periodicalPushData)) {
                data[index] = element.periodicalPushData();
            }
        });
        // Pull data from all registered handlers, either by collecting the data
        // itself or by calling the appropriate function
        $.each(registeredHandlers, function (index, handler) {
            var thisData = null;
            if (handler.data && $.isFunction(handler.data)) {
                thisData = handler.data();
            } else if (handler.data) {
                thisData = handler.data;
            }
            if (thisData !== null && !$.isEmptyObject(thisData)) {
                data[index] = thisData;
            }
        });
        return data;
    }

    // User activity handler
    function userActivityHandler() {
        currentDelayFactor = 0;
        if (+(new Date()) - dateOfLastCall > 5000) {
            poll(true);
        }
    }
    
    // Window activity handler
    function windowActivityHandler(event) {
        if (event.type === 'blur') {
            // Increase delay factor and reschedule next polling
            currentDelayFactor += 10;
            registerNextPoll();
        } else if (event.type === 'focus') {
            // Reset delay factor and start polling if neccessary
            userActivityHandler();
        }
    }

    // Actually poll data
    function poll(forced) {
        // Skip polling if an ajax request is already running, unless forced
        if (!forced && ajaxRequest) {
            registerNextPoll();
            return false;
        }

        // If forced, abort potential current ajax request
        if (ajaxRequest) {
            ajaxRequest.abort();
            ajaxRequest = null;
        }
        // Abort potentially scheduled polling
        window.clearTimeout(timeout);

        // Store current timestamp
        dateOfLastCall = +(new Date());
        
        // Prepare variables
        var url  = STUDIP.ABSOLUTE_URI_STUDIP + 'dispatch.php/jsupdater/get',
            page = window.location.href.replace(STUDIP.ABSOLUTE_URI_STUDIP, '');
        
        // Actual poll request, uses promises
        ajaxRequest = $.ajax(url, {
            data: {
                page: page,
                page_info: collectData()
            },
            type: 'POST',
            dataType: 'json',
            timeout: 5000
        }).done(function (json) {
            process(json);
        }).fail(function (jqXHR, textStatus, errorThrown) {
            resetJSONMemory({
                text : textStatus,
                error: errorThrown
            });
        }).always(function () {
            ajaxRequest = null;
            lastAjaxDuration = +(new Date()) - dateOfLastCall;

            registerNextPoll();
        });
    }

    // Register global object
    STUDIP.JSUpdater = {};

    // Starts the updater, also registers the activity handlers
    STUDIP.JSUpdater.start = function () {
        if (!active) {
            STUDIP.jsupdate_enable = true;
            $(document).on('mousemove', userActivityHandler);
            $(window).on('blur focus', windowActivityHandler);
            registerNextPoll();
        }
        active = true;
    };
    
    // Stops the updater, also unregisters the activity handlers
    STUDIP.JSUpdater.stop = function () {
        if (active) {
            STUDIP.jsupdate_enable = false;
            $(document).off('mousemove', userActivityHandler);
            $(window).off('blur focus', windowActivityHandler);
            if (ajaxRequest) {
                ajaxRequest.abort();
                ajaxRequest = null;
            }
            window.clearTimeout(timeout);
        }
        active = false;
    };
    
    // Registers a new handler by an index, a callback and an optional data
    // object or function
    STUDIP.JSUpdater.register = function (index, callback, data) {
        registeredHandlers[index] = {
            callback: callback,
            data: data || null
        };
    };
    
    // Unregisters/removes a previously registered handler
    STUDIP.JSUpdater.unregister = function (index) {
        delete registeredHandlers[index];
    };

    // Start js updater if global settings says so
    $(window).on('load', function () {
        if (STUDIP.jsupdate_enable) {
            STUDIP.JSUpdater.start();
        }
    });
    
    // Try to stop js updater if window is unloaded (might not work in all
    // browsers)
    $(window).on('unload', STUDIP.JSUpdater.stop);

}(jQuery, STUDIP));
