/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */

$(document).ready(function () {

    STUDIP.modalDialog.apply();
    STUDIP.statusgroups.apply();
});

STUDIP.statusgroups = {
    apply: function () {
        $('.moveable tbody').sortable({
            axis: "y",
            handle: ".dragHandle",
            helper: function (e, ui) {
                ui.children().each(function () {
                    jQuery(this).width(jQuery(this).width());
                });
                return ui;
            },
            start: function (event, ui) {
                $(this).closest('table').addClass('nohover');
            },
            stop: function (event, ui) {
                $(this).closest('table').removeClass('nohover');
                var group = $(this).closest('table').attr('id');
                var user = ui.item.attr('data-userid');
                var position = $(ui.item).prevAll().length;
                $.ajax({
                    type: 'POST',
                    url: $('#ajax_move').val(),
                    dataType: 'html',
                    data: {group: group, user: user, pos: position},
                    async: false
                }).done(function (data) {
                    $('#' + group + ' tbody').html(data);
                    STUDIP.statusgroups.apply();
                    STUDIP.modalDialog.apply();
                });
            }
        });
    }
};

STUDIP.modalDialog = {
    apply: function () {
        $('a.modal').click(function () {
            var dialog = $("<div></div>");
            dialog.load($(this).attr('href'), function () {
                STUDIP.modalDialog.load($(this));
            });
            $('<img/>', {
                src: STUDIP.ASSETS_URL + 'images/ajax_indicator_small.gif'
            }).appendTo(dialog);
            dialog.dialog({
                autoOpen: true,
                autoResize: true,
                resizable: false,
                position: 'center',
                close: function () {
                    $(this).remove();
                },
                width: 'auto',
                title: $(this).attr('title'),
                modal: true
            });
            return false;
        });
    },
    load: function (dialog) {
        dialog.find('.abort').click(function (e) {
            e.preventDefault();
            dialog.remove();
        });
        dialog.find('.stay_on_dialog').click(function (e) {
            $(this).attr('disabled', 'true');
            e.preventDefault();
            var button = jQuery(this).attr('name');
            var form = $(this).closest('form');
            $.ajax({
                type: "POST",
                url: form.attr('action'),
                data: form.serialize() + '&' + button + '=1', // serializes the form's elements.
                success: function (data)
                {
                    dialog.html(data); // show response from the php script.
                    STUDIP.modalDialog.load(dialog);
                }
            });
        });
        dialog.dialog({position: 'center'});
    }
};

STUDIP.statusgroups.addMembers = {
    init: function () {
        $('#search_persons_select_all').show();
        $('#search_persons_deselect_all').show();
    },
    // select all persons from selectable box
    selectAll: function () {
        $('#search_persons_selectable option').prop('selected', 'selected');
        $('#search_persons_add').click();
    },

    // deselect all persons from selected box
    deselectAll: function () {
        $('#search_persons_selected option').prop('selected', 'selected');
        $('#search_persons_remove').click();
    }
};
