/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */

/* ------------------------------------------------------------------------
 * jQuery plugin "addToolbar"
 * ------------------------------------------------------------------------ */
(function ($) {

    STUDIP.Toolbar = {

        // For better readability, the button set is externally defined in the file
        // toolbar-buttonset.js
        buttonSet: {},

        // Initializes (adds) a toolbar the passed textarea element
        initialize: function (element, button_set) {
            var $element = $(element),
                toolbar;

            // Bail out if the element is not a tetarea or a toolbar has already
            // been applied
            if (!$element.is('textarea') || $element.data('toolbar-added')) {
                return;
            }

            button_set = button_set || STUDIP.Toolbar.buttonSet;

            // Add flag so one element will never have more than one toolbar
            $element.data('toolbar-added', true);

            // Create toolbar element
            toolbar = $('<div class="editor_toolbar" />').width($element.outerWidth(true));

            // Assemble toolbar
            _.each(['left', 'right'], function (position) {
                var buttons = $('<div/>');
                _.each(button_set[position], function (format, name) {
                    var button = $('<button tabindex="-1" />').addClass(name);
                    if (format.icon) {
                        $("<img/>").attr("alt", format.label || name).attr("src", STUDIP.ASSETS_URL + "images/icons/16/blue/" + format.icon + ".png").appendTo(button);
                    } else {
                        button.html(format.label || name);
                    }
                    button.click(function () {
                        var selection = $element.getSelection(),
                            result    = format.evaluate(selection, $element, this) || selection;
                        $element.replaceSelection(result);
                        return false;
                    });
                    buttons.append(button);
                });
                toolbar.append(buttons);
            });

            // Attach toolbar to the specified element
            $element.before(toolbar);
        }
    };

    // Add functionality as jQuery extensions
    $.fn.extend({
        // Adds the toolbar to an element
        addToolbar: function (button_set) {
            return this.each(function () {
                STUDIP.Toolbar.initialize(this, button_set);
            });
        },
        // Obtains the currently selected text from an element
        getSelection: function () {
            var that = this[0];
            if (!!document.selection) {
                return document.selection.createRange().text;
            }
            if (!!this[0].setSelectionRange) {
                return this[0].value.substring(this[0].selectionStart, this[0].selectionEnd);
            }
            return false;
        },
        // Replaces the currently selected text of an element with the given
        // replacement
        replaceSelection: function (replacement) {
            return this.each(function () {
                var scroll_top = this.scrollTop,
                    range,
                    selection_start;
                if (!!document.selection) {
                    this.focus();
                    range = document.selection.createRange();
                    range.text = replacement;
                    range.select();
                } else if (!!this.setSelectionRange) {
                    selection_start = this.selectionStart;
                    this.value = this.value.substring(0, selection_start) +
                        replacement +
                        this.value.substring(this.selectionEnd);
                    this.setSelectionRange(selection_start + replacement.length,
                                           selection_start + replacement.length);
                }
                this.focus();
                this.scrollTop = scroll_top;
            });
        }
    });
}(jQuery));
