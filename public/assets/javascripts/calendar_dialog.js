/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */

STUDIP.CalendarDialog = {
    dialog: null,
    calendarTimeout: null,
    openBox: function (title, content, element) {
        var coord = "center"; //coordinates of the dialogbox - "center" means center of window
        if (element) {
            coord = jQuery(element).position();
            coord = [coord.left + jQuery(element).width() + 2,
                coord.top - jQuery(window).scrollTop() + jQuery(element).height()];
        }
        if (STUDIP.CalendarDialog.dialog === null) {
            STUDIP.CalendarDialog.dialog =
                jQuery('<div id="CalendarDialog">' + content + '</div>').dialog({
                    show: 'fade',
                    hide: 'fade',
                    position: coord,
                    title: title,
                    draggable: false,
                    modal: false,
                    width: Math.min(jQuery(window).width() / 3, jQuery(window).width() - 64),
                    height: 'auto',
                    maxHeight: jQuery(window).height(),
                    dialogClass: 'ui-dialog-calendar',
                    close: function () {
                        jQuery(this).remove();
                        STUDIP.CalendarDialog.dialog = null;
                    },
                    open: function () {
                        var offs = jQuery('.ui-dialog-calendar').offset();
                        if (coord[0] + 500 > jQuery(window).width()) {
                            offs.left = coord[0] - jQuery('.ui-dialog-calendar').width() - jQuery(element).width() - 10;
                        }
                        if (coord[1] > jQuery(window).height()) {
                            window.alert(coord[1] + jQuery('.ui-dialog-calendar').height());
                            offs.top = coord[1] - jQuery('.ui-dialog-calendar').height();
                        }
                        jQuery('.ui-dialog-calendar').offset(offs);
                    }
                });
        } else {
            jQuery('#CalendarDialog').html(content);
            jQuery('#CalendarDialog').dialog('option', 'position', coord);
            jQuery('#CalendarDialog').dialog('option', 'title', title);
        }
    },

    openCalendarHover: function (title, content, element) {
        STUDIP.CalendarDialog.calendarTimeout = window.setTimeout(function () {
            STUDIP.CalendarDialog.openBox(title, content, element);
        }, 500);
    },

    closeCalendarHover: function () {
        window.clearTimeout(STUDIP.CalendarDialog.calendarTimeout);
        jQuery('#CalendarDialog').dialog('close');
        STUDIP.CalendarDialog.calendarTimeout = null;
    }
};
