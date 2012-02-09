jQuery(function ($) {
    $('.bookable_rooms_action').bind('click', function (event) {
        var select = $(this).next('select')[0];
        if (select !== null && select !== undefined) {
            $.ajax({
                url: STUDIP.ABSOLUTE_URI_STUDIP + 'dispatch.php/resources/helpers/bookable_rooms',
                data: {
                    rooms: _.pluck(select.options, 'value'),
                    selected_dates : _.pluck($('input[name="singledate[]"]:checked'), 'value')
                },
                success: function (result) {
                  if ($.isArray(result)) {
                      _.each(result, function (v) {$(select).children('option[value=' + v + ']').remove();});
                  }
                }
              });
        }
    });
    $('.bookable_rooms_action').show();
});

jQuery(function ($) {
    STUDIP.BlockAppointmentsDialog = {
        dialog: null,
        reloadUrlOnClose: null,
        initialize: function (url) {
            if (STUDIP.BlockAppointmentsDialog.dialog === null) {
                $.ajax({
                    url: url,
                    data: {},
                    success: function (data) {
                        STUDIP.BlockAppointmentsDialog.dialog =
                            jQuery('<div id="BlockAppointmentsDialogbox">' + data.content + '</div>').dialog({
                                show: '',
                                hide: 'scale',
                                title: data.title,
                                draggable: true,
                                modal: true,
                                resizable: false,
                                width: Math.min(800, $(window).width() - 64),
                                height: 'auto',
                                maxHeight: $(window).height(),
                                close: function () {
                                    $(this).remove();
                                    STUDIP.BlockAppointmentsDialog.dialog = null;
                                }
                            });
                        STUDIP.BlockAppointmentsDialog.bindevents();
                    }
                });
            }
        },
        bindevents: function () {
            $('form[name$=block_appointments] button[type=submit]').bind('click dblclick', function () {
                var button_clicked = this.name;
                var form = $('form[name$=block_appointments]')[0];
                STUDIP.BlockAppointmentsDialog.submit(form, button_clicked);
                return false;
            });
            $('form[name$=block_appointments] .hasDatePicker').datepicker();
            $('form[name$=block_appointments] .hasDatePicker').blur();
        },
        submit: function (form, button_clicked) {
            if (form) {
                var form_data = $(form).serializeArray();
                form_data.push({name: button_clicked, value: 1});
                STUDIP.BlockAppointmentsDialog.update(form.action, form_data);
            }
        },
        update: function (url, data) {
            var zIndex = $('#BlockAppointmentsDialogbox').parent().zIndex();
            $('#BlockAppointmentsDialogbox').parent().zIndex(zIndex - 1);
            $.ajax({
                type: 'POST',
                url: url,
                data: data,
                success: function (data) {
                    if (STUDIP.BlockAppointmentsDialog.dialog !== null) {
                        if (data.auto_close === true) {
                            STUDIP.BlockAppointmentsDialog.dialog.dialog('close');
                            if (data.auto_reload === true && STUDIP.BlockAppointmentsDialog.reloadUrlOnClose !== null) {
                                document.location.replace(STUDIP.BlockAppointmentsDialog.reloadUrlOnClose);
                            }
                        } else {
                            STUDIP.BlockAppointmentsDialog.dialog.dialog('option', 'title', data.title);
                            $('#BlockAppointmentsDialogbox').html(data.content);
                            $('#BlockAppointmentsDialogbox').parent().zIndex(zIndex);
                            STUDIP.BlockAppointmentsDialog.bindevents();
                        }
                    }
                }
            });
        }
    };
});
