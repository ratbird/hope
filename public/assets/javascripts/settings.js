/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */

(function ($) {

    // 
    $.tools.validator.fn('input[data-must-equal]', function (el, value) {
        var target = $(el).data().mustEqual,
            labels = $.map([target, el], function (element) { 
                var label = $(element).closest('label').text();
                label = label || $('label[for="' + $(element).attr('id') + '"]').text();
                return $.trim(label.split(':')[0]);
            }),
            error_message = 'Die beiden Werte "$1" und "$2" stimmen nicht überein. '.toLocaleString(),
            matches = error_message.match(/\$\d/g);

        $.each(matches, function (i) {
            error_message = error_message.replace(this, labels[i]);
        });

        return ($(target).val() === value) ? true : error_message;
    });

    // Copy elements value to another element on change
    // Used for title choosers
    $('[data-target]').live('change', function () {
        var target = $(this).data().target;
        $(target).val(this.value);
    });
}(jQuery));

//
$('.notification.settings :checkbox').live('change', function () {
    var name = $(this).attr('name');
    
    if (name === 'all[all]') {
        $(this).closest('table').find(':checkbox').attr('checked', this.checked);
        return;
    }

    if (/all\[columns\]/.test(name)) {
        var index = $(this).closest('td').index() + 2;
        $(this).closest('table').find('tbody td:nth-child(' + index + ') :checkbox').attr('checked', this.checked);
    } else if (/all\[rows\]/.test(name)) {
        $(this).closest('td').siblings().find(':checkbox').attr('checked', this.checked);
    }

    $('.notification.settings tbody :checkbox[name^=all]').each(function () {
        var other = $(this).closest('td').siblings().find(':checkbox');
        this.checked = other.filter(':not(:checked)').length === 0;
    });

    $('.notification.settings thead :checkbox').each(function () {
        var index = $(this).closest('td').index() + 2,
            other = $(this).closest('table').find('tbody td:nth-child(' + index + ') :checkbox');
        this.checked = other.filter(':not(:checked)').length === 0;
    });
});

