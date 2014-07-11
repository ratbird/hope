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

/* Copies a value from a select to another element*/
jQuery(document).on('change', 'select[data-copy-to]', function () {
    var target = jQuery(this).data().copyTo,
        value  = jQuery(this).val() || jQuery(target).prop('defaultValue');
    jQuery(target).val(value);
});

jQuery(document).ready(function ($) {
    $('#checkAll').attr('checked', $('.sem_checkbox:checked').length !== 0);
});

STUDIP.HeaderIcons = {
    canvasRender: function () {
        var img = this;
        jQuery(img).closest("a").find("canvas.headericon").each(function (index, canvas) {
            jQuery(canvas).attr({
                'width': "42px",
                'height': "32px"
            });

            var icon   = jQuery(img)[0];
            var number = parseInt(jQuery(img).data("badge"), 10);
            canvas = jQuery(canvas)[0];
            var ctx = canvas.getContext("2d");
            if (window.devicePixelRatio) {
                var hidefCanvasWidth = parseInt(jQuery(canvas).attr('width'), 10);
                var hidefCanvasHeight = parseInt(jQuery(canvas).attr('height'), 10);
                var hidefCanvasCssWidth = hidefCanvasWidth;
                var hidefCanvasCssHeight = hidefCanvasHeight;
                jQuery(canvas).attr('width', hidefCanvasWidth * window.devicePixelRatio);
                jQuery(canvas).attr('height', hidefCanvasHeight * window.devicePixelRatio);
                jQuery(canvas).css('width', hidefCanvasCssWidth);
                jQuery(canvas).css('height', hidefCanvasCssHeight);
                ctx.scale(window.devicePixelRatio, window.devicePixelRatio);
            }

            ctx.clearRect(0,0,42,32);
            ctx.drawImage(icon, 7, 2, 28, 28);
            var filterCanvas = function (filter) {
                if (canvas.width > 0 && canvas.height > 0) {
                    var imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                    filter(imageData);
                    ctx.clearRect(0,0,42,32);
                    ctx.putImageData(imageData, 0, 0);
                }
            }
            var brightness = function(pixels, args) {
                var d = pixels.data;
                for (var i = 0; i < d.length; i += 4) {
                    d[i] = 255;     // red
                    d[i + 1] = 255; // green
                    d[i + 2] = 255; // blue
                }
                return pixels;
            };
            if (jQuery(canvas).is(".highlighted")) {
                filterCanvas(brightness);
            }


            if (number > 0) {
                var x = 34;
                ctx.globalCompositeOperation = "destination-out";
                ctx.beginPath();
                ctx.arc(x, 8, 11, 0, 2*Math.PI);
                ctx.fill();

                ctx.globalCompositeOperation = "source-over";
                ctx.beginPath();
                ctx.arc(x, 8, 8, 0, 2*Math.PI);
                ctx.fillStyle="#d60000";
                ctx.fill();

                ctx.font = "10px " + jQuery("body").css("font-family");
                ctx.textAlign = "center";
                ctx.fillStyle="white";
                ctx.fillText("" + number ,x, 11);
            }
        });
        jQuery(img).closest("a").addClass("canvasready");
    },
    render: function (selector, hovered) {
        jQuery.each(jQuery(selector), function (index, img) {
            if (img.complete) {
                STUDIP.HeaderIcons.canvasRender.call(img);
            } else {
                jQuery(img).on('load', STUDIP.HeaderIcons.canvasRender);
            }
        });
    }
};

jQuery(function () {
    STUDIP.HeaderIcons.render("img.headericon");
    jQuery("img.headericon").on("badgechange", function () { STUDIP.HeaderIcons.render(this); });
});
