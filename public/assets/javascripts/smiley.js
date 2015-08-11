/*jslint browser: true */
/*global STUDIP, jQuery */

jQuery(function ($) {
    'use strict';

    $('.smiley-select select').change(function () {
        $(this).closest('form').submit();
    });

    $(document).on('click', '.smiley-toggle', function (event) {
        var element = $(this);

        element.attr('disabled', true)
               .addClass('ajax');

        $.getJSON(element.attr('href')).then(function (json) {
            var container = $(element).closest('.ui-dialog-content,#layout_content').first();
            $('.messagebox', container).remove();
            container.prepend(json.message);

            element.toggleClass('favorite', json.state)
                   .removeClass('ajax')
                   .attr('disabled', false);
        });
        event.preventDefault();
    });
});