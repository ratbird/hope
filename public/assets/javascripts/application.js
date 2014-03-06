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
jQuery('[data-behaviour="\'ajaxContent\'"]').live('click', function () {
    var parameters = jQuery(this).metadata(),
    indicator = ("indicator" in parameters) ? parameters.indicator : this,
    target    = ("target" in parameters) ? parameters.target : jQuery(this).next(),
    url       = ("url" in parameters) ? parameters.url : jQuery(this).attr('href');

    jQuery(indicator).showAjaxNotification('right');
    jQuery(target).load(url, function () {
        jQuery(indicator).hideAjaxNotification();
    });
    return false;
});


// Global handler:
// Open a link in a lightbox (jQuery UI's Dialog). The Dialog's title
// can be set via a specific "X-Title"-header in the xhr response.
// Any included link with a rel value containing "option" in the response
// will be transformed into a button of the lightbox and removed from the
// response. A close button is always present.
jQuery('a[rel~="lightbox"], button[rel~="lightbox"]').live('click', function (event) {
    var $that       = jQuery(this),
        href        = $that.attr('href'),
        container   = jQuery('<div/>'),
        that_form   = null,
        that_params = null;
    if ($that.prop('form') !== undefined) {
        that_form = jQuery($that.prop('form'));
        that_params = that_form.serializeArray();
        that_params.push({'name' : $that.attr('name'), 'value' : 1});
        href = that_form.attr('action');
    }
    // Load response into a helper container, open dialog after loading
    // has finished.
    container.load(href, that_params || '', function (response, status, xhr) {
        var width   = jQuery('body').width() * 2 / 3,
            height  = jQuery('body').height() * 2 / 3,
            buttons = {},
            title   = xhr.getResponseHeader('X-Title') || '',
            scripts = jQuery(response).filter('script');

        // Create buttons
        if (!xhr.getResponseHeader('X-No-Buttons')) {
            jQuery('a[rel~="option"]', this).remove().each(function () {
                var label = jQuery(this).text(),
                    href  = jQuery(this).attr('href');
                buttons[label] = function () {
                    location.href = href;
                };
            });
            buttons["Schliessen".toLocaleString()] = function () {
                jQuery(this).dialog('close');
            };
        }
        jQuery('a[rel~="close"]', this).live('click', function (event) {
            event.preventDefault();
            container.dialog('close');
        });
        jQuery('form button[rel~="lightbox"]', this).live('click', function (event) {
            event.preventDefault();
            jQuery(this).attr('disabled', true);
            var form = jQuery(this).closest('form');
            var params = form.serializeArray();
            params.push({'name' : jQuery(this).attr('name'), 'value' : 1});
            jQuery.ajax({
                type: "POST",
                url: form.attr('action'),
                data: jQuery.param(params),
                success: function (data, status, xhr) {
                    var title = xhr.getResponseHeader('X-Title') || '',
                        location = xhr.getResponseHeader('X-Location');
                    if (location) {
                        container.dialog('close');
                        document.location.replace(location);
                    } else {
                        if (title) {
                            container.dialog('option', 'title', title);
                        }
                        container.html(data);
                    }
                }
            });
        });
        if (xhr.getResponseHeader('X-Location')) {
            document.location.replace(xhr.getResponseHeader('X-Location'));
        } else {
            // Create dialog
            jQuery(this).dialog({
                width :  width,
                height:  height,
                buttons: buttons,
                title:   title,
                modal:   true,
                open: function () {
                    jQuery('head').append(scripts);
                },
                close: function () {
                    jQuery(this).remove();
                }
            });
        }
    });

    event.preventDefault();
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

    if (!STUDIP.WYSIWYG) {
        // add toolbar only if WYSIWYG editor is not activated
        jQuery('.add_toolbar').addToolbar();
    }

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
    }).click(function () {
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
        yearSuffix: ''
    };
    $.datepicker.setDefaults($.datepicker.regional.de);
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
    function securityHandler(event) {
        var message = 'Ihre Eingaben wurden bislang noch nicht gespeichert.'.toLocaleString();
        event = event || window.event || {};
        event.returnValue = message;
        return message;
    }
    function submissionHandler() {
        $(window).off('beforeunload', securityHandler);
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
            $(window)[action]('beforeunload', securityHandler);

            // (at|de)tach submit handler that will remove the securityHandler
            // on form submission
            $(this).closest('form')[action]('submit', submissionHandler);

            // Store current state
            $(this).data('secured', action === 'on');
        }
    });
}(jQuery));

// Global handler:
// Use a checkbox as a proxy for a set of other checkboxes. Define
// proxied elements as a css selector in attribute "data-proxyfor".
(function ($) {
    $(document).on('change', ':checkbox[data-proxyfor]', function () {
        var proxied = $(this).data('proxyfor');
        $(proxied).filter(':not(:disabled)').attr('checked', this.checked);
    }).on('update.studip', ':checkbox[data-proxyfor]', function () {
        var proxied  = $(this).data('proxyfor'),
            $proxied = $(proxied),
            $checked = $proxied.filter(':not(:disabled)').filter(':checked');
        $(this).attr('checked', $proxied.length > 0 && $proxied.length === $checked.length);
        $(this).prop('indeterminate', $checked.length > 0 && $checked.length < $proxied.length);
    }).on('change', ':checkbox[data-proxiedby]', function () {
        var proxy = $(this).data('proxiedby');
        $(proxy).trigger('update.studip');
    }).ready(function () {
        $(':checkbox[data-proxyfor]').each(function () {
            var proxied = $(this).data('proxyfor');
            // The following seems like a hack but works perfectly fine.
            $(proxied).attr('data-proxiedby', true).data('proxiedby', this);
        }).trigger('update.studip');
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
