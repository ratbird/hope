/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */

STUDIP.CalendarDialog = {
    
    closeMps: function (form) {
        var added_users = [];
        jQuery("#calendar-manage_access_selectbox option:selected").each(function () {
            added_users[added_users.length] = jQuery(this).attr("value");
        });
        jQuery.ajax({
            'url': STUDIP.ABSOLUTE_URI_STUDIP + "dispatch.php/calendar/single/add_users/",
            'data': {
                'added_users': added_users
            },
            'type': "post"
        });
        jQuery(form).closest(".ui-dialog-content").dialog("close");
        STUDIP.Dialog.fromElement("#calendar-open-manageaccess");
        return false;
    },
    
    removeUser: function (element) {
        var url = jQuery(element).attr('href');
        jQuery(element).removeAttr('href');
        jQuery.ajax({
            'url': url,
            'type': "get",
            'success': function () {
                var head_tr = jQuery(element).closest('tr').prev('.calendar-user-head');
                jQuery(element).closest('tr').remove();
                if (head_tr.nextUntil(".calendar-user-head").size() === 0) {
                    head_tr.remove();
                }
            }
        });
        return false;
    }
};
