STUDIP.PersonalNotifications = {
    originalTitle: null,
    stack: [],
    newNotifications: function (notifications) {
        if (STUDIP.PersonalNotifications.originalTitle === null) {
            STUDIP.PersonalNotifications.originalTitle = window.document.title;
        }
        if (jQuery("#notification_marker").length > 0) {
            jQuery("#notification_marker").text(notifications.length);
            if (notifications.length > 0) {
                jQuery("#notification_marker, #notification_container").addClass("alert");
                window.document.title = "(!) " + STUDIP.PersonalNotifications.originalTitle;
            } else {
                jQuery("#notification_marker, #notification_container").removeClass("alert");
                window.document.title = STUDIP.PersonalNotifications.originalTitle;
            }
            jQuery("#notification_list > ul > li").remove();
            jQuery.each(notifications, function (index, notification) {
                jQuery("#notification_list > ul").append(notification.html);
            });
            STUDIP.PersonalNotifications.stack = notifications;
        }
    },
    checkHTMLids: function () {
        jQuery.each(STUDIP.PersonalNotifications.stack, function (index, notification) {
            
        });
    },
    markAsRead: function (event) {
        var id = jQuery(this).attr("id");
        id = id.substr(id.lastIndexOf("_") + 1);
        jQuery.ajax({
            'url': STUDIP.ABSOLUTE_URI_STUDIP + "dispatch.php/jsupdater/mark_notification_read",
            'data': {
                'id': id
            },
            'success': function () {
                jQuery("#notification_" + id).remove();
                jQuery("#notification_marker").text(jQuery("#notification_list > ul > li").length);
                if (jQuery("#notification_list > ul > li").length === 0) {
                    jQuery("#notification_marker, #notification_container").removeClass("alert");
                }
            }
        });
        return false;
    }
};

jQuery(document).bind("mouseover", STUDIP.PersonalNotifications.checkHTMLids);
jQuery("#notification_list > ul > li").live("click", STUDIP.PersonalNotifications.markAsRead);