/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */

/* ------------------------------------------------------------------------
 * jQuery plugin "elementAjaxNotifications"
 * ------------------------------------------------------------------------ */

(function ($) {

    $.fn.extend({
        showAjaxNotification: function (position) {
            position = position || 'left';
            return this.each(function () {
                if ($(this).data('ajax_notification')) {
                    return;
                }

                $(this).wrap('<span class="ajax_notification" />');
                var that = this,
                    notification = $('<span class="notification" />').hide().insertBefore(this),
                    changes = {
                        marginLeft: 0,
                        marginRight: 0
                    };

                changes[position === 'right' ? 'marginRight' : 'marginLeft'] = notification.outerWidth(true);

                $(this).data({
                    ajax_notification: notification
                }).parent().animate(changes, 'fast', function () {
                    var offset = $(that).position(),
                        styles = {
                            left: offset.left - notification.outerWidth(true),
                            top: offset.top + Math.max(0, Math.floor(($(that).height() - notification.outerHeight(true)) / 2))
                        };
                    if (position === 'right') {
                        styles.left += $(this).outerWidth(true);
                    }
                    notification.css(styles).fadeIn('fast');
                });
            });
        },
        hideAjaxNotification: function () {
            return this.each(function () {
                var $this = $(this).stop(),
                notification = $this.data('ajax_notification');
                if (!notification) {
                    return;
                }

                notification.stop().fadeOut('fast', function () {
                    $this.animate({marginLeft: 0, marginRight: 0}, 'fast', function () {
                        $this.unwrap();
                    });
                    $(this).remove();
                });
                $(this).removeData('ajax_notification');
            });
        }
    });

}(jQuery));
