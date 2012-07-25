STUDIP.Schedule = {

    inst_changed : false,

    /**
     * this function is called, when an entry shall be created in the calendar
     *
     * @param  object  the empty entry in the calendar
     * @param  int     the day that has been clicked
     * @param  int     the start-hour that has been clicked
     */
    newEntry: function (entry, day, start_hour, end_hour) {
        // do not allow creation of new entry, if one of the following popups is visible!
        if (jQuery('#edit_sem_entry').is(':visible') ||
            jQuery('#edit_entry').is(':visible') ||
            jQuery('#edit_inst_entry').is(':visible')) {
            jQuery(entry).remove();
            return;
        }

        // if there is already an entry set, kick him first before showing a new one
        if (this.entry) {
            jQuery(this.entry).fadeOut('fast');
            jQuery(this.entry).remove();
        }

        this.entry = entry;

        // fill values of overlay
        jQuery('#entry_hour_start').text(start_hour);
        jQuery('#entry_hour_end').text(end_hour);
        jQuery('#entry_day').text(STUDIP.Calendar.day_names[day].toLocaleString());

        jQuery('#new_entry_start_hour').val(start_hour);
        jQuery('#new_entry_end_hour').val(end_hour);
        jQuery('#new_entry_day').val(day);

        // show the overlay
        jQuery('#schedule_new_entry').show();

        // set the position of the overlay
        jQuery('#schedule_new_entry').css({
            top: Math.floor(entry.offset().top - jQuery('#schedule_new_entry').height() - 20),
            left: Math.floor(entry.offset().left)
        });

        if (jQuery('#schedule_new_entry').offset().top < 0) {
            jQuery('#schedule_new_entry').css({
                top:  Math.floor(entry.offset().top + entry.height() + 20)
            });
        }
    },

    /**
     * cancel adding of a new entry and fade out/remove all faded in/added boxes
     *
     * @param bool fade: if fade is true, fade out all boxes, otherwise just hide them
     *
     * @return: void
     */
    cancelNewEntry: function () {
        if (jQuery(this.entry).is(':visible')) {
            jQuery('#schedule_new_entry').fadeOut('fast');
            jQuery(this.entry).fadeOut('fast').remove();
        }

        jQuery('#edit_entry').fadeOut('fast');
        jQuery('#edit_inst_entry').fadeOut('fast');
    },

    /**
     * this function morphs from the quick-add box for adding a new entry to the schedule
     * to the larger box with more details to edit
     *
     * @return: void
     */
    showDetails: function () {

        // set the values for detailed view
        jQuery('select[name=entry_day]').val(Number(jQuery('#new_entry_day').val()) + 1);
        jQuery('input[name=entry_start_hour]').val(parseInt(jQuery('#new_entry_start_hour').val(), 10));
        jQuery('input[name=entry_start_minute]').val('00');
        jQuery('input[name=entry_end_hour]').val(parseInt(jQuery('#new_entry_end_hour').val(), 10));
        jQuery('input[name=entry_end_minute]').val('00');

        jQuery('input[name=entry_title]').val(jQuery('#entry_title').val());
        jQuery('textarea[name=entry_content]').val(jQuery('#entry_content').val());

        jQuery('#edit_entry_drag').html(jQuery('#new_entry_drag').html());

        // morph to the detailed view
        jQuery('#schedule_new_entry').animate({
            left: Math.floor(jQuery(window).width() / 4),  // for safari
            width: '50%',
            top: '180px'
        }, 500, function () {
            jQuery('#edit_entry').fadeIn(400, function () {
                // reset the box
                jQuery('#schedule_new_entry').css({
                    display: 'none',
                    left: 0,
                    width: '400px',
                    top: 0,
                    height: '230px',
                    'margin-left': 0
                });
            });
        });
    },

    /**
     * show a popup conatining the details of the passed seminar
     * at the passed cycle
     *
     * @param  string  the seminar to be shown
     * @param  string  the cycle-id of the regular time-entry to be shown
     *                 (a seminar can have multiple of these
     */
    showSeminarDetails: function (seminar_id, cycle_id) {
        STUDIP.Schedule.cancelNewEntry();
        jQuery('#edit_sem_entry').fadeOut('fast');
        jQuery.get(STUDIP.ABSOLUTE_URI_STUDIP + 'dispatch.php/calendar/schedule/entryajax/' + seminar_id + '/' + cycle_id, function (data) {
            jQuery('#edit_sem_entry').remove();
            jQuery('body').append(data);
        });
    },

    /**
     * show a popup with the details of a regular schedule entry with passed id
     *
     * @param  string  the id of the schedule-entry
     */
    showScheduleDetails: function (id) {
        STUDIP.Schedule.cancelNewEntry();
        jQuery('#edit_entry').fadeOut('fast');
        jQuery.get(STUDIP.ABSOLUTE_URI_STUDIP + 'dispatch.php/calendar/schedule/entryajax/' + id, function (data) {
            jQuery('#edit_entry').remove();
            jQuery('body').append(data);
        });
    },

    /**
     * show a popup with the details of a group entry, containing several seminars
     *
     * @param  string  the id of the grouped entry to be displayed
     */
    showInstituteDetails: function (id) {
        STUDIP.Schedule.cancelNewEntry();
        jQuery('#edit_inst_entry').fadeOut('fast');
        jQuery.get(STUDIP.ABSOLUTE_URI_STUDIP + 'dispatch.php/calendar/schedule/groupedentry/' + id + '/true', function (data) {
            jQuery('#edit_inst_entry').remove();
            jQuery('body').append(data);
        });

        return false;
    },

    /**
     * hide a seminar-entry in the schedule (admin-version)
     *
     * @param  string  the seminar to be shown
     * @param  string  the cycle-id of the regular time-entry to be shown
     *                 (a seminar can have multiple of these
     */
    instSemUnbind : function (seminar_id, cycle_id) {
        STUDIP.Schedule.inst_changed = true;
        jQuery.ajax({
            type: 'GET',
            url: STUDIP.ABSOLUTE_URI_STUDIP + 'dispatch.php/calendar/schedule/adminbind/' + seminar_id + '/' + cycle_id + '/0/true'
        });

        jQuery('#' + seminar_id + '_' + cycle_id + '_hide').fadeOut('fast', function () {
            jQuery('#' + seminar_id + '_' + cycle_id + '_show').fadeIn('fast');
        });
    },

    /**
     * make a hidden seminar-entry visible in the schedule again
     *
     * @param  string  the seminar to be shown
     * @param  string  the cycle-id of the regular time-entry to be shown
     *                 (a seminar can have multiple of these
     */
    instSemBind : function (seminar_id, cycle_id) {
        STUDIP.Schedule.inst_changed = true;
        jQuery.ajax({
            type: 'GET',
            url: STUDIP.ABSOLUTE_URI_STUDIP + 'dispatch.php/calendar/schedule/adminbind/' + seminar_id + '/' + cycle_id + '/1/true'
        });

        jQuery('#' + seminar_id + '_' + cycle_id + '_show').fadeOut('fast', function () {
            jQuery('#' + seminar_id + '_' + cycle_id + '_hide').fadeIn('fast');
        });
    },

    /**
     * hide the popup of grouped-entry, containing a list of seminars.
     * returns true if the visiblity of one of the entries has been changed,
     * false otherwise
     *
     * @param  object  the element to be hidden
     *
     * @return  bool  true if the visibility of one seminar hase changed, false otherwise
     */
    hideInstOverlay: function (element) {
        if (STUDIP.Schedule.inst_changed) {
            return true;
        }
        jQuery(element).fadeOut('fast');

        STUDIP.Calendar.click_in_progress = false;

        return false;
    },

    /**
     * calls STUDIP.Calendar.checkTimeslot to check that the time is valid
     *
     * @param  bool  returns true if the time is valid, false otherwise
     */
    checkFormFields: function () {
        if (!STUDIP.Calendar.checkTimeslot(jQuery('#schedule_entry_hours > input[name=entry_start_hour]'),
                                           jQuery('#schedule_entry_hours > input[name=entry_start_minute]'),
                                           jQuery('#schedule_entry_hours > input[name=entry_end_hour]'),
                                           jQuery('#schedule_entry_hours > input[name=entry_end_minute]'))) {

            jQuery('#schedule_entry_hours').addClass('invalid');
            jQuery('#schedule_entry_hours > span[class=invalid_message]').show();
            return false;
        }

        return true;
    }
};

STUDIP.Instschedule = {
    /**
     * show the details of a grouped-entry in the isntitute-calendar, containing several seminars
     *
     * @param  string  the id of the grouped-entry to be displayed
     */
    showInstituteDetails: function (id) {
        STUDIP.Schedule.cancelNewEntry();
        jQuery('#edit_inst_entry').fadeOut('fast');
        jQuery.get(STUDIP.ABSOLUTE_URI_STUDIP + 'dispatch.php/calendar/instschedule/groupedentry/' + id + '/true', function (data) {
            jQuery('#edit_inst_entry').remove();
            jQuery('body').append(data);
        });

        return false;
    }
};
