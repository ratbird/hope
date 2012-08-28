(function ($) {

var stack = {},
    originalTitle, favicon_url;

var process_notifications = function (notifications) {
    stack = {};

    $("#notification_list .notification").remove();
    $.each(notifications, function (index, notification) {
        $("#notification_list > ul").append(notification.html);

        var id = $('#notification_list .notification:last').data().id;
        stack[id] = notification;

    });

    STUDIP.PersonalNotifications.update();
};

STUDIP.PersonalNotifications = {
    newNotifications: function () {},
    checkHTMLids: function () {
        $.each(stack, function (index, notification) {
            
        });
    },
    markAsRead: function (event) {
        var notification = $(this),
            id = notification.data().id;
        $.ajax({
            'url': STUDIP.ABSOLUTE_URI_STUDIP + "dispatch.php/jsupdater/mark_notification_read",
            'data': {
                'id': id
            },
            'success': function () {
                notification.toggle('blind', 'fast', function () {
                    delete stack[id];
                    STUDIP.PersonalNotifications.update();
                });
            }
        });
    },
    update: function () {
        var count = _.values(stack).length;
        $('#notification_marker').text(count);
        Notificon(count || '', {favicon: favicon_url});
        if (count > 0) {
            $("#notification_marker, #notification_container").addClass("alert");
            window.document.title = "(!) " + originalTitle;
        } else {
            $("#notification_marker, #notification_container").removeClass("alert");
            window.document.title = originalTitle;
        }
    }
};

// $(document).bind("mouseover", STUDIP.PersonalNotifications.checkHTMLids);
$("#notification_list .notification").live('click', STUDIP.PersonalNotifications.markAsRead);

$(document).ready(function () {
    if ($("#notification_marker").length > 0) {
        originalTitle = window.document.title;
        favicon_url = $('link[rel="shortcut icon"]').attr('href');
        STUDIP.PersonalNotifications.newNotifications = process_notifications;
    }
});

}(jQuery));
