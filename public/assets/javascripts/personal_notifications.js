/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _, Notificon */

(function ($) {

    var stack = {},
        originalTitle,
        favicon_url,
        audio_notification = false;

    var process_notifications = function (notifications) {
        var ul        = $('<ul/>'),
            changed   = false,
            new_stack = {};

        $.each(notifications, function (index, notification) {
            ul.append(notification.html);

            var id = $('.notification:last', ul).data().id;
            new_stack[id] = notification;
            if (notification.html_id) {
                $("#" + notification.html_id).bind("mouseenter", STUDIP.PersonalNotifications.isVisited);
            }

            changed = (changed || !(id in stack));
        });

        if (changed || _.values(stack).length !== _.values(new_stack).length) {
            stack = new_stack;
            $('#notification_list > ul').replaceWith(ul);
        }
        STUDIP.PersonalNotifications.update();
    };

    STUDIP.PersonalNotifications = {
        newNotifications: function () {},
        markAsRead: function (event) {
            var notification = $(this).closest('.notification'),
                id = notification.data().id;
            STUDIP.PersonalNotifications.sendReadInfo(id, notification);
            return false;
        },
        sendReadInfo: function (id, notification) {
            $.ajax({
                'url': STUDIP.ABSOLUTE_URI_STUDIP + "dispatch.php/jsupdater/mark_notification_read/" + id,
                'success': function () {
                    if (notification) {
                        notification.toggle('blind', 'fast', function () {
                            delete stack[id];
                            STUDIP.PersonalNotifications.update();
                        });
                    }
                }
            });
        },
        update: function () {
            var count     = _.values(stack).length,
                old_count = parseInt($('#notification_marker').text(), 10);
            if (count > 0) {
                if (count > old_count && audio_notification !== false) {
                    audio_notification.play();
                }
                $("#notification_marker, #notification_container").addClass("alert");
                window.document.title = "(!) " + originalTitle;
            } else {
                $("#notification_marker, #notification_container").removeClass("alert");
                window.document.title = originalTitle;
            }
            if (old_count !== count) {
                $('#notification_marker').text(count);
            }
            Notificon(count || '', {favicon: favicon_url});
        },
        isVisited: function () {
            var id = this.id;
            $.each(stack, function (index, notification) {
                if (notification.html_id === id) {
                    STUDIP.PersonalNotifications.sendReadInfo(notification.personal_notification_id);
                }
            });
        }
    };

    // $(document).bind("mouseover", STUDIP.PersonalNotifications.checkHTMLids);
    $("#notification_list .mark_as_read").live('click', STUDIP.PersonalNotifications.markAsRead);

    $(document).ready(function () {
        if ($("#notification_marker").length > 0) {
            originalTitle = window.document.title;
            favicon_url = $('link[rel="shortcut icon"]').attr('href');
            STUDIP.PersonalNotifications.newNotifications = process_notifications;

            if ($('#audio_notification').length > 0) {
                audio_notification = $('#audio_notification').get(0);
                audio_notification.load();
            }
        }
    });

}(jQuery));
