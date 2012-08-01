/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */

/* ------------------------------------------------------------------------
 * jQuery plugin "addToolbar"
 * ------------------------------------------------------------------------ */
(function ($) {

    var getSelection = function (element)  {
        if (!!document.selection) {
            return document.selection.createRange().text;
        } else if (!!element.setSelectionRange) {
            return element.value.substring(element.selectionStart, element.selectionEnd);
        } else {
            return false;
        }
    };

    var replaceSelection = function (element, text) {
        var scroll_top = element.scrollTop;
        if (!!document.selection) {
            element.focus();
            var range = document.selection.createRange();
            range.text = text;
            range.select();
        } else if (!!element.setSelectionRange) {
            var selection_start = element.selectionStart;
            element.value = element.value.substring(0, selection_start) +
                text +
                element.value.substring(element.selectionEnd);
            element.setSelectionRange(selection_start + text.length,
                                      selection_start + text.length);
        }
        element.focus();
        element.scrollTop = scroll_top;
    };

    $.fn.extend({
        addToolbar: function (button_set) {
            // Bail out if no button set is defined
            if (!button_set) {
                return this;
            }

            return this.each(function () {
                if (!$(this).is('textarea') || $(this).data('toolbar_added')) {
                    return;
                }

                var $this = $(this),
                toolbar = $('<div class="editor_toolbar" />');

                _.each(button_set, function (value) {
                    $('<button />')
                        .html(value.label)
                        .addClass(value.name)
                        .appendTo(toolbar)
                        .click(function () {
                            var replacement = value.open + getSelection($this[0]) + value.close;
                            replaceSelection($this[0], replacement);
                            return false;
                        });
                });

                $this.before(toolbar).data('toolbar_added', true);
            });
        }
    });
}(jQuery));
