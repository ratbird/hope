/*jslint browser: true, unparam: true, todo: true */
/*global STUDIP, jQuery */

(function ($, STUDIP) {
    'use strict';

    STUDIP.Sidebar = {};

    // This function inits the sticky sidebar by using the StickyKit lib
    // <http://leafo.net/sticky-kit/>
    STUDIP.Sidebar.setSticky = function (is_sticky) {
        if (is_sticky === undefined || is_sticky) {
            $('#layout-sidebar .sidebar').stick_in_parent({
                offset_top: $('#barBottomContainer').outerHeight(true),
                inner_scrolling: true
            }).on('sticky_kit:stick sticky_kit:unbottom', function () {
                var stuckHandler = function (top, left) {
                    $('#layout-sidebar .sidebar').css('margin-left', -left);
                };
                STUDIP.Scroll.addHandler('sticky.horizontal', stuckHandler);
                stuckHandler(0, $(window).scrollLeft());
            }).on('sticky_kit:unstick sticky_kit:bottom', function () {
                STUDIP.Scroll.removeHandler('sticky.horizontal');
                $(this).css('margin-left', 0);
            });
        } else {
            STUDIP.Scroll.removeHandler('sticky.horizontal');
            $('#layout-sidebar .sidebar').trigger('sticky_kit:unstick').trigger('sticky_kit:detach');
        }
    };

    // (De|Re)activate when help tours start|stop
    $(document).on('tourstart.studip tourend.studip', function () {
        STUDIP.Sidebar.setSticky(event.type === 'tourend.studip');
    });

    // Handle dynamic content
    if (window.MutationObserver !== undefined) {
        // Attach mutation observer to #layout_content and trigger it on
        // changes to class and style attributes (which affect the height
        // of the content). Trigger a recalculation of the sticky kit when
        // a mutation occurs so the sidebar will
        $(document).ready(function () {
            if ($('#layout_content').length === 0) {
                return;
            }
            var target = $('#layout_content').get(0),
                stickyObserver = new window.MutationObserver(function () {
                    window.requestAnimationFrame(function () {
                        $(document.body).trigger('sticky_kit:recalc');
                    });
                });
            stickyObserver.observe(target, {
                attributes: true,
                attributeFilter: ['style', 'class'],
                characterData: true,
                childList: true,
                subtree: true
            });
        });
    } else {
        // Stores document height (we will need this to check for changes)
        var doc_height,
            heightChangeHandler = function () {
                var curr_height = $(document).height();
                if (doc_height !== curr_height) {
                    doc_height = curr_height;
                    $(document.body).trigger('sticky_kit:recalc');
                }
            };

        $(document).on('ready', function () {
            doc_height = $(document).height();
        });

        // Recalculcate positions on ajax and img load events.
        // Inside the handlers the current document height is compared
        // to the previous height before the event occured so recalculation
        // only happens on actual changes
        $(document).on('ajaxComplete', heightChangeHandler);
        $(document).on('load', '#layout_content img', heightChangeHandler);

        // Specialized handler to trigger recalculation when wysiwyg
        // instances are created.
        if (STUDIP.wysiwyg) {
            $(document).on('load.wysiwyg', 'textarea', function () {
                $(document.body).trigger('sticky_kit:recalc');
            });
        }
    }

    // Engage
    $(document).on('ready', function () {
        STUDIP.Sidebar.setSticky();
    });

    // Legacy: Expose STUDIP.Sidebar.setSticky as global stickySidebar
    // function to not break stuff
    // TODO Remove this after STUD.IP 3.5
    window.stickySidebar = STUDIP.Sidebar.setSticky;
}(jQuery, STUDIP));
