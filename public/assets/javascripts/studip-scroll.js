(function ($, STUDIP) {
    STUDIP = STUDIP || {};
    
    var handlers  = {},
        activated = false,
        throttle  = 30,
        timeout   = false;
        
    function scrollHandler (event) {
        var scrollTop = $(document).scrollTop();
        $.each(handlers, function (index, handler) {
            handler(scrollTop);
        });
        if (arguments.length > 0) {
            clearTimeout(timeout);
            timeout = setTimeout(scrollHandler, throttle + 10);
        }
    };

    function refresh() {
        var hasHandlers = !$.isEmptyObject(handlers);
        if (!hasHandlers && activated) {
            $(document).off('scroll.studip'); 
            activated = false;
        } else if (hasHandlers) {
            if (!activated) {
                $(document).on('scroll.studip', _.throttle(scrollHandler, throttle));
                activated = true;
            }
            $(document).trigger('scroll.studip');
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