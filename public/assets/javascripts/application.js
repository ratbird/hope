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
jQuery('[data-behaviour="ajaxContent"]').live('click', function () {
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
jQuery('.messagebox .messagebox_buttons a').live('click', function () {
    if (jQuery(this).is('.details')) {
        jQuery(this).closest('.messagebox').toggleClass('details_hidden');
    } else if (jQuery(this).is('.close')) {
        jQuery(this).closest('.messagebox').hide('blind', 'fast', function () {
            jQuery(this).remove();
        });
    }
    return false;
}).live('focus', function () {
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

    $('table.collapsable .toggler').focus(function () {
        $(this).blur();
    }).live('click', function () {
        $(this).closest('tbody').toggleClass('collapsed');
        return false;
    });

    $('a.load-in-new-row').live('click', function () {
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

    $('.loaded-details a.cancel').live('click', function () {
        $(this).closest('.loaded-details').prev().find('a.load-in-new-row').click();
        return false;
    });

});

/* ------------------------------------------------------------------------
 * Toggle dates in seminar_main
 * ------------------------------------------------------------------------ */
(function ($) {
    $('.more-dates').live('click', function () {
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

    $('.more-location-dates').live('click', function () {
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
jQuery('input.allow-only-numbers').live('keyup', function () {
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
    // Store document height (we will need this to check for changes)
    var doc_height = $(document).height();

    // This function inits the sticky sidebar by using the StickyKit lib
    // <http://leafo.net/sticky-kit/>
    function stickySidebar (is_sticky) {
        if (arguments.length === 0) {
            is_sticky = true;
        }
        if (is_sticky) {
            $('#layout-sidebar .sidebar').stick_in_parent({
                offset_top: $('#barBottomContainer').outerHeight(true),
                inner_scrolling: true
            }).on('sticky_kit:stick sticky_kit:unbottom', function () {
                var stuckHandler = function (top, left) {
                    $('#layout-sidebar .sidebar').css('margin-left', -left);
                };
                STUDIP.Scroll.addHandler('sticky.horizontal', stuckHandler);
                stuckHandler(0, $(window).scrollLeft());
            }).on('sticky_kit:unstick sticky_kit:bottom', function () {
                STUDIP.Scroll.removeHandler('sticky.horizontal');
                $(this).css('margin-left', 0);
            });
        } else {
            STUDIP.Scroll.removeHandler('sticky.horizontal');
            $('#layout-sidebar .sidebar').trigger('sticky_kit:unstick').trigger('sticky_kit:detach');
        }
    };
    stickySidebar();

    // (De|Re)activate when help tours start|stop
    $(document).on('tourstart.studip', function () {
        stickySidebar(false);
    }).on('tourend.studip', function () {
        stickySidebar();
    });

    if (window.MutationObserver !== undefined) {
        // Attach mutation observer to #layout_content and trigger it on
        // changes to class and style attributes (which affect the height
        // of the content). Trigger a recalculation of the sticky kit when
        // a mutation occurs so the sidebar will
        var target = $('#layout_content')[0],
            stickyObserver = new MutationObserver(function (mutations) {
                $(document.body).trigger('sticky_kit:recalc');
            });
        stickyObserver.observe(target, {
            attributes: true,
            attributeFilter: ['style', 'class'],
            characterData: true,
            subtree: true
        });
    } else {
        // Recalculcate positions on ajax and img load events.
        // Inside the handlers the current document height is compared
        // to the previous height before the event occured so recalculation
        // only happens on actual changes
        $(document).on('ajaxComplete', function () {
            var curr_height = $(document).height();
            if (doc_height !== curr_height) {
                doc_height = curr_height;
                $(document.body).trigger('sticky_kit:recalc');
            }
        });
        $(document).on('load', '#layout_content img', function () {
            var curr_height = $(document).height();
            if (doc_height !== curr_height) {
                doc_height = curr_height;
                $(document.body).trigger('sticky_kit:recalc');
            }
        });

        // Specialized handler to trigger recalculation when wysiwyg
        // instances are created.
        if (STUDIP.wysiwyg) {
            $(document).on('load.wysiwyg', 'textarea', function () {
                $(document.body).trigger('sticky_kit:recalc');
            });
        }
    }

    $('a.print_action').live('click', function (event) {
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

        if (!unchanged && !confirm('Ihre Eingaben wurden bislang noch nicht gespeichert.'.toLocaleString())) {
            event.preventDefault();
            event.stopPropagation();
            return false;
        }

        submissionHandlerWindow();
        return true;
    }

    $(document).on('dialog-open', function (event, ui) {
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
