/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _, Notificon */

(function ($) {

    var stack = {},
        originalTitle,
        favicon_url,
        audio_notification = false
        directlydeleted = [];

    var process_notifications = function (notifications) {
        var ul        = $('<ul/>'),
            changed   = false,
            new_stack = {};

        $.each(notifications, function (index, notification) {
            if ($.inArray(notification.personal_notification_id, directlydeleted) === -1) {
                ul.append(notification.html);

                var id = $('.notification:last', ul).data().id;
                new_stack[id] = notification;
                if (notification.html_id) {
                    $("#" + notification.html_id).bind("mouseenter", STUDIP.PersonalNotifications.isVisited);
                }

                changed = (changed || !(id in stack));

                if (typeof Notification !== "undefined" && Notification.permission === "granted") {
                    if (typeof sessionStorage !== "undefined" && !sessionStorage['desktop.notification.exists.' + notification.id]) {
                        // If it's okay let's create a notification
                        var message = new Notification(notification.text, {
                            "icon": notification.avatar,
                            "tag": notification.id
                        });
                        message.addEventListener("click", function () {
                            location.href = STUDIP.ABSOLUTE_URI_STUDIP + "dispatch.php/jsupdater/mark_notification_read/" + this.tag;
                        });
                        message.addEventListener("close", function () {
                            STUDIP.PersonalNotifications.sendReadInfo(this.tag);
                        });
                        sessionStorage['desktop.notification.exists.' + notification.id] = 1;
                    }
                }
            }
        });

        if (changed || _.values(stack).length !== _.values(new_stack).length) {
            stack = new_stack;
            $('#notification_list > ul').replaceWith(ul);
        }
        STUDIP.PersonalNotifications.update();
        directlydeleted = [];
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
                            $(this).remove();
                        });
                    }
                }
            });
        },
        update: function () {
            var count      = _.values(stack).length,
                old_count  = parseInt($('#notification_marker').text(), 10),
                really_new = 0;
            $('#notification_list > ul > li').each(function () {
                if (parseInt($(this).attr("data-timestamp"), 10) > parseInt($('#notification_marker').attr("data-lastvisit"), 10)) {
                    really_new += 1;
                }
            });
            if (really_new > 0) {
                $("#notification_marker").data('seen', false).addClass("alert");
                window.document.title = "(!) " + originalTitle;
            } else {
                $("#notification_marker").removeClass("alert");
                window.document.title = originalTitle;
            }
            if (count) {
                $("#notification_container").addClass("hoverable");
                if (count > old_count && audio_notification !== false) {
                    audio_notification.play();
                }
            } else {
                $("#notification_container").removeClass("hoverable");
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
                    delete stack[index];
                    jQuery(".notification[data-id=" + notification.personal_notification_id + "]")
                        .fadeOut(function () { jQuery(this).remove(); });
                    directlydeleted.push(notification.personal_notification_id);
                    STUDIP.PersonalNotifications.update();
                }
            });
        },
        setSeen: function () {
            if ($('#notification_marker').data('seen')) {
                return;
            }
            $('#notification_marker').data('seen', true);

            $.ajax({
                'url': STUDIP.ABSOLUTE_URI_STUDIP + "dispatch.php/jsupdater/notifications_seen",
                'success': function (time) {
                    $("#notification_marker").removeClass("alert").attr("data-lastvisit", time);
                }
            });
        }
    };

    $("#notification_list .mark_as_read").live('click', STUDIP.PersonalNotifications.markAsRead);
    $("#notification_list").live('mouseenter', STUDIP.PersonalNotifications.setSeen);

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
