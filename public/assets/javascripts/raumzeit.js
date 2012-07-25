jQuery(function ($) {
    $('.bookable_rooms_action').bind('click', function (event) {
        var select = $(this).next('select')[0];
        var me = $(this);
        if (select !== null && select !== undefined) {
            me.attr('src', STUDIP.ASSETS_URL + 'images/ajax_indicator_small.gif');
            if (me.data('options') === undefined) {
                me.data('options', $(select).children('option').clone(true));
            } else {
                $(select).empty().append(me.data('options').clone(true));
            }
            $.ajax({
                type: 'POST',
                url: STUDIP.ABSOLUTE_URI_STUDIP + 'dispatch.php/resources/helpers/bookable_rooms',
                data: {
                    rooms: _.pluck(select.options, 'value'),
                    selected_dates : _.pluck($('input[name="singledate[]"]:checked'), 'value'),
                    singleDateID: $('input[name=singleDateID]').attr('value'),
                    new_date: _.map($('#day,#month,#year,#start_stunde,#start_minute,#end_stunde,#end_minute'), function (v) {return { name:v.id, value:v.value};})
                },
                success: function (result) {
                  if ($.isArray(result)) {
                      if (result.length) {
                          var not_bookable_rooms = _.map(result, function (v) {return $(select).children('option[value=' + v + ']').text();});
                          select.title = 'Nicht buchbare Räume:' . toLocaleString() + ' ' + not_bookable_rooms.join(', ');
                      } else {
                          select.title = '';
                      }
                      _.each(result, function (v) {$(select).children('option[value=' + v + ']').remove();});
                  } else {
                      select.title = '';
                  }
                  me.attr('src', STUDIP.ASSETS_URL + 'images/icons/16/blue/room_clear.png');
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
            $('#block_appointments_days input:checkbox').click(function () {
                if (this.id == 'block_appointments_days_0') {
                    $('#block_appointments_days input:checkbox').attr('checked', function(i) {return i == 0;});
                } else {
                    $('#block_appointments_days_0').attr('checked', false);
                }
            });
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
