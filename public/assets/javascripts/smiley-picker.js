/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */

/**
 * smiley-picker.js - Smiley Picker
 *
 * Creates a SmileyPicker object in the global STUDIP namespace with
 * the methods show, hide and toggle.
 * show and toggle accept two arguments "triggerElement, onSelect":
 * - triggerElement is the element that triggered the event
 * - onSelect is a function to be executed once a smiley is selected
 *
 * The picker requires a php based backend under the route
 * "smileys/picker" which renders the html for the picker.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

(function ($) {

    var initialized = false,
        picker_element = $('<div/>'),
        select_handler = function () {};

    // Loads a url
    function loadURL(url, callback) {
        $.get(url, function (response) {
            response = $(response);

            // Add a preload icon for each smiley to avoid a potential flash
            // of the alternative text
            $('.smileys img', response).each(function () {
                var that    = this,
                    src     = this.src,
                    image   = new Image();
                this.src = STUDIP.ASSETS_URL + 'images/ajax_indicator_small.gif';

                image.onload = image.onerror = function () { 
                    that.src = src;
                };
                image.src = src;
            });

            picker_element.html(response);

            if ($.isFunction(callback)) { 
                callback();
            }
        });
    }

    // Create smiley picker object and bind it to global STUDIP namespace
    STUDIP.SmileyPicker = {
        // Show smiley picker, triggered by a specific element and handle
        // a selected smiley by the passed function
        show: function (triggerElement, onSelect) {
            select_handler = onSelect;
            
            if (!initialized) {
                // Setup picker dialog
                picker_element.dialog({
                    autoOpen: false,
                    width: 420, // needs to be hardcoded, unfortunately.
                    dialogClass: 'smiley-picker-dialog',
                    resizable: false,
                    title: 'Smileys'.toLocaleString(),
                    show: 'fade',
                    hide: 'fade',
                    buttons: [
                        {
                            text: 'Zur Gesamt�bersicht'.toLocaleString(),
                            click: function () {
                                var url = STUDIP.URLHelper.getURL('dispatch.php/smileys');
                                picker_element.dialog('close');
                                STUDIP.Dialog.fromURL(url);
                            }
                        },
                        {
                            text: 'Schliessen'.toLocaleString(),
                            click: function () {
                                picker_element.dialog('close');
                            }
                        }
                    ]
                });

                // Initial load with spinner next to trigger element
                $(triggerElement).showAjaxNotification();
                loadURL(STUDIP.URLHelper.getURL('dispatch.php/smileys/picker'), function () {
                    $(triggerElement).hideAjaxNotification();
                    picker_element.dialog('open');
                });

                initialized = true;
            } else {
                picker_element.dialog('open');
            }
        },
        // Hide smiley picker
        hide: function () {
            picker_element.dialog('close');
        },
        // Toggle smiley picker display (pass the same arguments as for show)
        toggle: function (triggerElement, onSelect) {
            if (initialized && picker_element.dialog('isOpen')) {
                STUDIP.SmileyPicker.hide();
            } else {
                STUDIP.SmileyPicker.show(triggerElement, onSelect);
            }
        }
    };

    // Attach global event handlers:

    // Navigation: Load any url in this very same dialog
    $('.smiley-picker .navigation a').live('click', function () {
        loadURL(this.href);
        return false;
    });

    // Smiley:
    // Execute select handler with selected smiley's code
    // (typically adds the code to a certain textarea)
    $('.smiley-picker .smiley').live('click', function () {
        select_handler($(this).data().code);
        picker_element.dialog('close');
        return false;
    });

}(jQuery));
