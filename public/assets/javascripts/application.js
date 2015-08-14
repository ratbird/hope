/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */
/* ------------------------------------------------------------------------
 * application.js
 * This file is part of Stud.IP - http://www.studip.de
 *
 * Stud.IP is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Stud.IP is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Stud.IP; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor,
 * Boston, MA  02110-1301  USA
 */

/* ------------------------------------------------------------------------
 * ajax_loader
 * ------------------------------------------------------------------------ */
jQuery(document).on('click', '[data-behaviour="ajaxContent"]', function () {
    var parameters = jQuery(this).data(),
        indicator = parameters.hasOwnProperty('indicator') ? parameters.indicator : this,
        target    = parameters.hasOwnProperty('target') ? parameters.target : jQuery(this).next(),
        url       = parameters.hasOwnProperty('url') ? parameters.url : jQuery(this).attr('href');

    jQuery(indicator).showAjaxNotification('right');
    jQuery(target).load(url, function () {
        jQuery(indicator).hideAjaxNotification();
    });
    return false;
});


/* ------------------------------------------------------------------------
 * messages boxes
 * ------------------------------------------------------------------------ */
jQuery(document).on('click', '.messagebox .messagebox_buttons a', function () {
    if (jQuery(this).is('.details')) {
        jQuery(this).closest('.messagebox').toggleClass('details_hidden');
    } else if (jQuery(this).is('.close')) {
        jQuery(this).closest('.messagebox').hide('blind', 'fast', function () {
            jQuery(this).remove();
        });
    }
    return false;
}).on('focus', '.messagebox .messagebox_buttons a', function () {
    jQuery(this).blur(); // Get rid of the ugly "clicked border" due to the text-indent
});


/* ------------------------------------------------------------------------
 * application wide setup
 * ------------------------------------------------------------------------ */
jQuery(function () {
    // AJAX Indicator
    STUDIP.ajax_indicator = true;
    STUDIP.URLHelper.base_url = STUDIP.ABSOLUTE_URI_STUDIP;

    STUDIP.study_area_selection.initialize();

    // validate forms
    STUDIP.Forms.initialize();

    // autofocus for all browsers
    if (!("autofocus" in document.createElement("input"))) {
        jQuery('[autofocus]').first().focus();
    }

    jQuery('.add_toolbar').addToolbar();

    if (document.createElement('textarea').style.resize === undefined) {
        jQuery('textarea.resizable').resizable({
            handles: 's',
            minHeight: 50,
            zIndex: 1
        });
    }
});


/* ------------------------------------------------------------------------
 * application collapsable tablerows
 * ------------------------------------------------------------------------ */
jQuery(function ($) {

    $(document).on('focus', 'table.collapsable .toggler', function () {
        $(this).blur();
    }).on('click', 'table.collapsable .toggler', function () {
        $(this).closest('tbody').toggleClass('collapsed');
        return false;
    });

    $(document).on('click', 'a.load-in-new-row', function () {
        if ($(this).data('busy')) {
            return false;
        }

        if ($(this).closest('tr').next().hasClass('loaded-details')) {
            $(this).closest('tr').next().remove();
            return false;
        }
        $(this).showAjaxNotification().data('busy', true);

        var that = this;
        $.get($(this).attr('href'), function (response) {
            var row = $('<tr />').addClass('loaded-details');

            $('<td />')
                .attr('colspan', $(that).closest('td').siblings().length + 1)
                .html(response)
                .appendTo(row);

            $(that)
                .hideAjaxNotification()
                .closest('tr').after(row);

            $(that).data('busy', false);
            $('body').trigger('ajaxLoaded');
        });

        return false;
    });

    $(document).on('click', '.loaded-details a.cancel', function () {
        $(this).closest('.loaded-details').prev().find('a.load-in-new-row').click();
        return false;
    });

});

/* ------------------------------------------------------------------------
 * Toggle dates in seminar_main
 * ------------------------------------------------------------------------ */
(function ($) {
    $(document).on('click', '.more-dates', function () {
        $('.more-dates-infos').toggle();
        $('.more-dates-digits').toggle();
        if ($('.more-dates-infos').is(':visible')) {
            $('.more-dates').text('(weniger)');
            $('.more-dates').attr('title', 'Blenden Sie die restlichen Termine aus'.toLocaleString());
        } else {
            $('.more-dates').text('(mehr)');
            $('.more-dates').attr('title', 'Blenden Sie die restlichen Termine ein'.toLocaleString());
        }
    });

    $(document).on('click', '.more-location-dates', function () {
        $(this).closest('div').prev().toggle();
        $(this).prev().toggle();

        if ($(this).closest('div').prev().is(':visible')) {
            $(this).text('(weniger)');
            $(this).attr('title', 'Blenden Sie die restlichen Termine aus'.toLocaleString());
        } else {
            $(this).text('(mehr)');
            $(this).attr('title', 'Blenden Sie die restlichen Termine ein'.toLocaleString());
        }
    });
}(jQuery));

/* ------------------------------------------------------------------------
 * only numbers in the input field
 * ------------------------------------------------------------------------ */
jQuery(document).on('keyup', 'input.allow-only-numbers', function () {
    jQuery(this).val(jQuery(this).val().replace(/\D/, ''));
});


/* ------------------------------------------------------------------------
 * additional jQuery (UI) settings for Stud.IP
 * ------------------------------------------------------------------------ */
jQuery.ui.accordion.prototype.options.icons = {
    header: 'arrow_right',
    headerSelected: 'arrow_down'
};


/* ------------------------------------------------------------------------
 * jQuery datepicker
 * ------------------------------------------------------------------------ */
(function ($) {
    $.datepicker.regional.de = {
        closeText: 'schließen',
        prevText: '&#x3c;zurück',
        nextText: 'Vor&#x3e;',
        currentText: 'heute',
        monthNames: ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
            'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'],
        monthNamesShort: ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun',
            'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'],
        dayNames: ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'],
        dayNamesShort: ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'],
        dayNamesMin: ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'],
        weekHeader: 'Wo',
        dateFormat: 'dd.mm.yy',
        firstDay: 1,
        isRTL: false,
        showMonthAfterYear: false,
        yearSuffix: '',
        changeMonth: true,
        changeYear: true
    };
    $.datepicker.setDefaults($.datepicker.regional.de);

    $(document).on('focus', '.has-date-picker', function () {
        $(this).removeClass('has-date-picker').datepicker();
    });
}(jQuery));

/* ------------------------------------------------------------------------
 * jQuery timepicker
 * ------------------------------------------------------------------------ */

/* German translation for the jQuery Timepicker Addon */
/* Written by Marvin */
(function ($) {
    $.timepicker.regional.de = {
        timeOnlyTitle: 'Zeit wählen',
        timeText: 'Zeit',
        hourText: 'Stunde',
        minuteText: 'Minute',
        secondText: 'Sekunde',
        millisecText: 'Millisekunde',
        timezoneText: 'Zeitzone',
        currentText: 'Jetzt',
        closeText: 'Fertig',
        timeFormat: 'HH:mm',
        amNames: ['vorm.', 'AM', 'A'],
        pmNames: ['nachm.', 'PM', 'P'],
        isRTL: false
    };
    $.timepicker.setDefaults($.timepicker.regional.de);
}(jQuery));


jQuery(function ($) {
    $(document).on('click', 'a.print_action', function (event) {
        var url_to_print = this.href;
        $('<iframe/>', {
            name: url_to_print,
            src: url_to_print,
            width: '1px',
            height: '1px',
            frameborder: 0
        })
            .css({top: '-99px', position: 'absolute'})
            .appendTo('body')
            .load(function () {
                this.contentWindow.focus();
                this.contentWindow.print();
            });
        return false;
    });
});

/* Secure textareas by displaying a warning on page unload if there are
 unsaved changes */
(function ($) {
    function securityHandlerWindow(event) {
        var message = 'Ihre Eingaben wurden bislang noch nicht gespeichert.'.toLocaleString();
        event = event || window.event || {};
        event.returnValue = message;
        return message;
    }
    function submissionHandlerWindow() {
        $(window).off('beforeunload', securityHandlerWindow);
    }

    $(document).on('change keyup', 'textarea[data-secure]', function () {
        var secured  = $(this).data('secured'),
            changed  = (this.value !== this.defaultValue),
            action   = null;

        if (changed && !secured) {
            action = 'on';
        } else if (!changed && secured) {
            action = 'off';
        }

        if (action !== null) {
            // (at|de)tach before unload handler that will display the message
            $(window)[action]('beforeunload', securityHandlerWindow);

            // (at|de)tach submit handler that will remove the securityHandlerWindow
            // on form submission
            $(this).closest('form')[action]('submit', submissionHandlerWindow);

            // Store current state
            $(this).data('secured', action === 'on');
        }

        $(this).data('changed', changed);
    });

    function securityHandlerDialog(event, ui) {
        var unchanged = true;
        $('textarea[data-secure]', ui.dialog).each(function () {
            unchanged = unchanged && this.value === this.defaultValue;
        });

        // If WYSIWYG editor is enabled, always assume that the text has been
        // changed.
        if ($('textarea.wysiwyg', ui.dialog).length > 0) {
            unchanged = false;
        }

        if (!unchanged && !confirm('Ihre Eingaben wurden bislang noch nicht gespeichert.'.toLocaleString())) {
            event.preventDefault();
            event.stopPropagation();
            return false;
        }

        submissionHandlerWindow();
        return true;
    }

    $(document).on('dialog-update', function (event, ui) {
        if ($('textarea[data-secure]', ui.dialog).length === 0) {
            return;
        }

        $(ui.dialog).on('dialogbeforeclose', securityHandlerDialog)
            .find('form:has(textarea[data-secure])').on('submit', function () {
                $(this).closest('ui.dialog').off('dialogbeforeclose', securityHandlerDialog);
            });
    });

}(jQuery));

/* Copies a value from a select to another element*/
jQuery(document).on('change', 'select[data-copy-to]', function () {
    var target = jQuery(this).data().copyTo,
        value  = jQuery(this).val() || jQuery(target).prop('defaultValue');
    jQuery(target).val(value);
});

jQuery(document).ready(function ($) {
    $('#checkAll').attr('checked', $('.sem_checkbox:checked').length !== 0);
});

// Fix horizontal scroll issue on domready, window load and window resize.
// This also makes the header and footer sticky regarding horizontal scrolling.
jQuery(document).on('ready', function () {
    var page_margin    = ($('#layout_page').outerWidth(true) - $('#layout_page').width()) / 2,
        content_margin = $('#layout_content').outerWidth(true) - $('#layout_content').innerWidth(),
        sidebar_width  = $('#layout_sidebar').outerWidth(true);

    function fixScrolling () {
        $('#layout_page').removeClass('oversized').css({
            minWidth: '',
            marginRight: '',
            paddingRight: ''
        });

        var max_width    = 0,
            fix_required = $('html').is(':not(.responsified)') && $('#layout_content').get(0).scrollWidth > $('#layout_content').width();

        if (fix_required) {
            $('#layout_content').children().each(function () {
                var width = $(this).get(0).scrollWidth + ($(this).outerWidth(true) - $(this).innerWidth());
                if (width > max_width) {
                    max_width = width;
                }
            });

            $('#layout_page').addClass('oversized').css({
                minWidth: max_width + content_margin + sidebar_width,
                marginRight: 0,
                paddingRight: page_margin
            });

            STUDIP.Scroll.addHandler('horizontal-scroll', (function () {
                var last_left = null;
                return function (top, left) {
                    if (last_left !== left) {
                        $('#flex-header,#layout_footer,#barBottomContainer').css({
                            transform: 'translate3d(' + left + 'px,0,0)'
                        });
                    }
                    last_left = left;
                };
            }()));
        } else {
            STUDIP.Scroll.removeHandler('horizontal-scroll');
        }
    };

    if ($('.no-touch #layout_content').length > 0) {
        // Try to fix now
        fixScrolling();

        // and fix again on window load and resize
        $(window).on('resize load', _.debounce(fixScrolling, 100));
    }
});


/* notify MathJax about new content*/
jQuery(document)
    .on('dialog-open',
        function (event) {
            if (typeof MathJax !== 'undefined') {
                MathJax.Hub.Queue(["Typeset", MathJax.Hub, this.dialog]);
            }
        });

/*override window.print to allow mathjax rendering to finish before printing*/
(function (origPrint) {
     window.print = function () {
       if (typeof MathJax !== 'undefined') {
          MathJax.Hub.Queue(
              ["Delay",MathJax.Callback,700],
                origPrint
                );
         } else {
            origPrint();
         }
     }
})(window.print);
