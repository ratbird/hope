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
                width = $element.css('width') || $element.width(),
                wrap,
                toolbar,
                temp,
                height;

            // Bail out if the element is not a tetarea or a toolbar has already
            // been applied
            if (!$element.is('textarea') || $element.data('toolbar-added')) {
                return;
            }

            button_set = button_set || STUDIP.Toolbar.buttonSet;

            // Add flag so one element will never have more than one toolbar
            $element.data('toolbar-added', true);

            // Create toolbar element
            toolbar = $('<div class="buttons" />');

            // Assemble toolbar
            _.each(['left', 'right'], function (position) {
                var buttons = $('<span/>').addClass(position);
                _.each(button_set[position], function (format, name) {
                    var button = $('<span />').addClass(name),
                        label  = format.label || name;

                    if (format.icon) {
                        label = $('<img/>').attr('alt', format.label || name)
                                           .attr('src', STUDIP.ASSETS_URL + "images/icons/16/blue/" + format.icon + '.png');
                    }

                    button.html(label).button().click(function () {
                        var selection   = $element.getSelection(),
                            result      = format.evaluate(selection, $element, this) || selection,
                            replacement = _.isObject(result) ? result.replacement : (_.isUndefined(result) ? selection : result),
                            offset      = _.isObject(result) ? result.offset : (result || '').length;
                        $element.replaceSelection(replacement, offset).change();
                        return false;
                    });

                    buttons.append(button);
                });
                toolbar.append(buttons);
            });

            // Attach toolbar to the specified element
            wrap = $('<div class="editor_toolbar"/>').css('width', width);
            $element.css('width', '100%').wrap(wrap).before(toolbar);
        }
    };

    // Add functionality as jQuery extensions
    $.fn.extend({
        // Adds the toolbar to an element
        addToolbar: function (button_set) {
            return this.each(function () {
                var wysiwygDisabled = !STUDIP.wysiwyg;
                var wysiwygTextarea = $(this).hasClass('wysiwyg');
                if (wysiwygDisabled || !wysiwygTextarea) {
                    STUDIP.Toolbar.initialize(this, button_set);
                }
            });
        }
    });

}(jQuery));
