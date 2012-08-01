/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */

STUDIP.RoomRequestDialog = {
    dialog: null,
    reloadUrlOnClose: null,
    initialize: function (url) {
        if (STUDIP.RoomRequestDialog.dialog === null) {
            jQuery.ajax({
                url: url,
                data: {},
                success: function (data) {
                    STUDIP.RoomRequestDialog.dialog =
                        jQuery('<div id="RoomRequestDialogbox">' + data.content + '</div>').dialog({
                            show: '',
                            hide: 'scale',
                            title: data.title,
                            draggable: true,
                            modal: true,
                            resizable: false,
                            width: Math.min(1000, jQuery(window).width() - 64),
                            height: 'auto',
                            maxHeight: jQuery(window).height(),
                            close: function () {
                                jQuery(this).remove();
                                STUDIP.RoomRequestDialog.dialog = null;
                            }
                        });
                    STUDIP.RoomRequestDialog.bindevents();
                }
            });
        }
    },
    bindevents: function () {
        jQuery('form[name=room_request]').find('button, input[type=image]').bind('click dblclick', function () {
            var button_clicked = this.name;
            var form = jQuery('form[name=room_request]')[0];
            STUDIP.RoomRequestDialog.submit(form, button_clicked);
            return false;
        });
    },
    submit: function (form, button_clicked) {
        if (form) {
            var form_data = jQuery(form).serializeArray();
            form_data.push({name: button_clicked, value: 1});
            STUDIP.RoomRequestDialog.update(form.action, form_data);
        }
    },
    update: function (url, data) {
        var zIndex = jQuery('#RoomRequestDialogbox').parent().zIndex();
        jQuery('#RoomRequestDialogbox').parent().zIndex(zIndex - 1);
        jQuery.ajax({
            type: 'POST',
            url: url,
            data: data,
            success: function (data) {
                if (STUDIP.RoomRequestDialog.dialog !== null) {
                    if (data.auto_close === true) {
                        STUDIP.RoomRequestDialog.dialog.dialog('close');
                        if (data.auto_reload === true && STUDIP.RoomRequestDialog.reloadUrlOnClose !== null) {
                            document.location.replace(STUDIP.RoomRequestDialog.reloadUrlOnClose);
                        }
                    } else {
                        STUDIP.RoomRequestDialog.dialog.dialog('option', 'title', data.title);
                        jQuery('#RoomRequestDialogbox').html(data.content);
                        jQuery('#RoomRequestDialogbox').parent().zIndex(zIndex);
                        STUDIP.RoomRequestDialog.bindevents();
                    }
                }
            }
        });
    }
};
