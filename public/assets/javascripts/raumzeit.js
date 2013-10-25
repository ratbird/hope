/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _, STUDIP */

jQuery(function ($) {
    $('.bookable_rooms_action').bind('click', function (event) {
        var select = $(this).prev('select')[0];
        var me = $(this);
        if (select !== null && select !== undefined) {
            if (me.attr('data-state') === 'enabled') {
                STUDIP.Raumzeit.disableBookableRooms(me);
            } else {
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
                        new_date: _.map($('#startDate,#start_stunde,#start_minute,#end_stunde,#end_minute'), function (v) {
                            return { name: v.id, value: v.value };
                        })
                    },
                    success: function (result) {
                        if ($.isArray(result)) {
                            if (result.length) {
                                var not_bookable_rooms = _.map(result, function (v) {
                                    return $(select).children('option[value=' + v + ']').text().trim();
                                });
                                select.title = 'Nicht buchbare R�ume:'.toLocaleString() + ' ' + not_bookable_rooms.join(', ');
                            } else {
                                select.title = '';
                            }
                            _.each(result, function (v) {
                                $(select).children('option[value=' + v + ']').attr('disabled', 'disabled');
                            });
                        } else {
                            select.title = '';
                        }
                        me.attr('src', STUDIP.ASSETS_URL + 'images/icons/16/blue/room-clear.png');
                        me.attr('title', 'Alle R�ume anzeigen'.toLocaleString());
                        me.attr('data-state', 'enabled');
                    }
                });
            }
        }
    });
    $('.bookable_rooms_action').show();

    $('input[type=checkbox]').bind('change', function () {
        STUDIP.Raumzeit.disableBookableRooms($('img[data-name=bulk_action]'));
    });
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
                var clicked_id = parseInt(this.id.split('_').pop(), 10);
                if (clicked_id === 0 || clicked_id === 1) {
                    $('#block_appointments_days input:checkbox').attr('checked', function (i) {
                        return i === clicked_id;
                    });
                } else {
                    $('#block_appointments_days_0').attr('checked', false);
                    $('#block_appointments_days_1').attr('checked', false);
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

STUDIP.Raumzeit = {
    toggleCheckboxes: function (cycle_id) {
        var checked = false;
        jQuery('table[data-cycleid=' + cycle_id + '] input[name^=singledate]').each(function () {
            if (jQuery(this).attr('checked')) {
                checked = true;
            }
        });

        jQuery('table[data-cycleid=' + cycle_id + '] input[name*=singledate]').attr('checked', !checked);
    },

    addLecturer: function () {
        jQuery('select[name=teachers] option:selected').each(function () {
            var lecturer_id = jQuery(this).val();
            if (lecturer_id === 'none') {
                return;
            }

            jQuery('li[data-lecturerid=' + lecturer_id + ']').show();
            //jQuery('li[data-lecturerid=' + lecturer_id + '] input').val('1');
            jQuery('select[name=teachers] option[value=' + lecturer_id + ']').hide();
            jQuery('select[name=teachers] option[value=none]').attr('selected', 'selected');
        });

        STUDIP.Raumzeit.addFormLecturers();
    },

    removeLecturer: function (lecturer_id) {
        if (jQuery('ul.teachers li:visible').size() > 1) {
            jQuery('li[data-lecturerid=' + lecturer_id + ']').hide();
            //jQuery('li[data-lecturerid=' + lecturer_id + '] input').val('0');
            jQuery('select[name=teachers] option[value=' + lecturer_id + ']').show();
        } else {
            if (jQuery('div.at_least_one_teacher').size() === 0) {
                jQuery('ul.teachers').before('<div class="at_least_one_teacher" style="display: none"><i>' + 'Jeder Termin muss mindestens eine Person haben, die ihn durchf�hrt!'.toLocaleString() + '</i><div>');
                jQuery('div.at_least_one_teacher').slideDown().delay(3000).fadeOut(400, function () {
                    jQuery(this).remove();
                });
                jQuery('li[data-lecturerid=' + lecturer_id + ']').effect('shake', 100);
            }
        }

        STUDIP.Raumzeit.addFormLecturers();
    },

    addFormLecturers: function () {
        var data = [];

        jQuery('ul.teachers li:visible').each(function () {
            data.push(jQuery(this).attr('data-lecturerid'));
        });

        jQuery('input[name=related_teachers]').val(data.join(','));
    },
    addFormGroups: function () {
        var data = [];
        jQuery('ul.groups li:visible').each(function () {
            data.push(jQuery(this).attr('data-groupid'));
        });
        jQuery('input[name=related_statusgruppen]').val(data.join(','));
    },
    addGroup: function () {
        jQuery('select[name=groups] option:selected').each(function () {
            var statusgruppe_id = jQuery(this).val();
            if (statusgruppe_id === 'none') {
                return;
            }

            jQuery('li[data-groupid=' + statusgruppe_id + ']').show();
            //jQuery('li[data-groupid=' + statusgruppe_id + '] input').val('1');
            jQuery('select[name=groups] option[value=' + statusgruppe_id + ']').hide();
            jQuery('select[name=groups] option[value=none]').attr('selected', 'selected');
        });

        STUDIP.Raumzeit.addFormGroups();
    },

    removeGroup: function (statusgruppe_id) {
        jQuery('li[data-groupid=' + statusgruppe_id + ']').hide();
        //jQuery('li[data-lecturerid=' + statusgruppe_id + '] input').val('0');
        jQuery('select[name=groups] option[value=' + statusgruppe_id + ']').show();

        STUDIP.Raumzeit.addFormGroups();
    },

    disableBookableRooms: function (icon) {
        var select = $(icon).prev('select')[0];
        var me = $(icon);

        $(select).children('option').each(function () {
            $(this).attr('disabled', false);
        });

        me.attr('data-state', false);
        me.attr('src', STUDIP.ASSETS_URL + 'images/icons/16/grey/room-clear.png');
        me.attr('title', 'Nur buchbare R�ume anzeigen'.toLocaleString());
    }
};

jQuery(function ($) {
    STUDIP.CancelDatesDialog = {
        dialog: null,
        reloadUrlOnClose: null,
        initialize: function (url) {
            if (STUDIP.CancelDatesDialog.dialog === null) {
                $.ajax({
                    url: url,
                    data: {},
                    success: function (data) {
                        STUDIP.CancelDatesDialog.dialog =
                            jQuery('<div id="CancelDatesDialogbox">' + data.content + '</div>').dialog({
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
                                    STUDIP.CancelDatesDialog.dialog = null;
                                }
                            });
                        STUDIP.CancelDatesDialog.bindevents();
                    }
                });
            }
        },
        bindevents: function () {
            $('form[name$=cancel_dates] button[type=submit]').bind('click dblclick', function () {
                var button_clicked = this.name;
                var form = $('form[name$=cancel_dates]')[0];
                STUDIP.CancelDatesDialog.submit(form, button_clicked);
                return false;
            });
        },
        submit: function (form, button_clicked) {
            if (form) {
                var form_data = $(form).serializeArray();
                form_data.push({name: button_clicked, value: 1});
                STUDIP.CancelDatesDialog.update(form.action, form_data);
            }
        },
        update: function (url, data) {
            var zIndex = $('#CancelDatesDialogbox').parent().zIndex();
            $('#CancelDatesDialogbox').parent().zIndex(zIndex - 1);
            $.ajax({
                type: 'POST',
                url: url,
                data: data,
                success: function (data) {
                    if (STUDIP.CancelDatesDialog.dialog !== null) {
                        if (data.auto_close === true) {
                            STUDIP.CancelDatesDialog.dialog.dialog('close');
                            if (data.auto_reload === true && STUDIP.CancelDatesDialog.reloadUrlOnClose !== null) {
                                document.location.replace(STUDIP.CancelDatesDialog.reloadUrlOnClose);
                            }
                        } else {
                            STUDIP.CancelDatesDialog.dialog.dialog('option', 'title', data.title);
                            $('#CancelDatesDialogbox').html(data.content);
                            $('#CancelDatesDialogbox').parent().zIndex(zIndex);
                            STUDIP.CancelDatesDialog.bindevents();
                        }
                    }
                }
            });
        }
    };
});
