/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */

(function ($, STUDIP) {

    // Cron task: Change tbody class according to inherent input setting
    $('.cron-task input').live('change', function () {
        $(this).closest('tbody').addClass('selected')
               .siblings().removeClass('selected');
    });

    // Global handler:
    // Toggle a table element. The url of the link will be called, an ajax
    // indicator will be shown instead of the element and the whole table row
    // will be replaced with the row with the same id from the response.
    // Thus, in your controller you only have to execute the appropriate
    // action and redraw the page with the new state.
    $('a[data-behaviour~="ajax-toggle"]').live('click', function (event) {
        var $that = $(this),
            href  = $that.attr('href'),
            id    = $that.closest('tr').attr('id');

        $that.attr('disabled', true).addClass('ajaxing');
        $.get(href, function (response) {
            var row = $('#' + id, response);
            $that.closest('tr').replaceWith(row);
        });

        event.preventDefault();
    });

    // Cron item:
    // Display the following element and focus it's inherent input element
    // if no value from a select element has been chosen. Hide the following
    // element if a value has been chosen.
    $('.cron-item select').live('change', function () {
        var state = $(this).val().length > 0,
            $next = $(this).next();

        if (state) {
            $next.show().find('input').focus();
        } else {
            $next.hide();
        }
    });

    // Miscellaneous filters:
    // Submit surrounding form on change
    $('.cronjob-filters select').live('change', function () {
        $(this).closest('form').submit();
    });

    // Active date and time picker as well as the Cron item selector on
    // document ready / page load.
    $(document).ready(function () {
        $('.cronjobs-edit input.has-date-picker').datepicker();
        $('.cronjobs-edit input.has-time-picker').timepicker();

        $('.cron-item select').change();
        $('.cronjobs tfoot select').change();
    });


}(jQuery, STUDIP));