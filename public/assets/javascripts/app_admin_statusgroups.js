/*jslint browser: true, unparam: true */
/*global jQuery, STUDIP */

(function ($, STUDIP) {
    'use strict';

    var ajax_endpoint = false;

    STUDIP.statusgroups = {
        apply: function () {
            $('.movable tbody').sortable({
                axis: 'y',
                handle: '.dragHandle',
                helper: function (event, ui) {
                    ui.children().each(function () {
                        $(this).width($(this).width());
                    });
                    return ui;
                },
                start: function (event, ui) {
                    $(this).closest('table').addClass('nohover');
                },
                stop: function (event, ui) {
                    var table    = $(this).closest('table'),
                        group    = table.attr('id'),
                        user     = ui.item.attr('data-userid'),
                        position = $(ui.item).prevAll().length;

                    table.removeClass('nohover');

                    $.ajax({
                        type: 'POST',
                        url: ajax_endpoint,
                        dataType: 'html',
                        data: {group: group, user: user, pos: position},
                        async: false
                    }).done(function (data) {
                        $('tbody', table).html(data);
                        STUDIP.statusgroups.apply();
                    });
                }
            });
        }
    };

    $(document).ready(function () {
        ajax_endpoint = $('meta[name="statusgroups-ajax-movable-endpoint"]').attr('content');
        STUDIP.statusgroups.apply();
    }).on('ready dialog-open dialog-update', function () {
        $('.nestable').nestable({
            rootClass: 'nestable'
        });
    }).on('submit', '#order_form', function () {
        var structure = $('.nestable').nestable('serialize'),
            json_data = JSON.stringify(structure);
        $('#ordering').val(json_data);
    });

}(jQuery, STUDIP));

