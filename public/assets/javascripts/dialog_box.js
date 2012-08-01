/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */

/* ------------------------------------------------------------------------
 * Dialogbox
 * ------------------------------------------------------------------------ */

/**
 * The dialogbox is an element from jQuery UI that presents content like in
 * a window that is draggable and resizable. All you need is a title and a
 * content for that window. You can also define an id to identify that window
 * later on. Only one window with the same id will be shown at a time.
 * Also you can define a scope, so only one window of one scope will be shown
 * at the same time.
 */
STUDIP.Dialogbox = {
    currentScopes: {},
    currentBoxes: {},
    forumTimeout: null,
    cache: {},

    openBox: function (id, title, content, coord, scope) {
        if (scope && this.currentBoxes[this.currentScopes[scope]] && (id !== this.currentBoxes[this.currentScopes[scope]])) {
            this.closeScope(scope);
        }
        if (!this.currentBoxes[id]) {
            jQuery('<div id="Dialogbox_' + id + '">' + content + '</div>').dialog({
                show: 'slide',
                hide: 'slide',
                title: title,
                position: coord,
                width: Math.min(600, jQuery(window).width() - 64),
                height: 'auto',
                maxHeight: jQuery(window).height(),
                close: function () {
                    STUDIP.Dialogbox.closeBox(id, true);
                },
                drag: function () {
                    STUDIP.Dialogbox.closeBox(id, false);
                }
            });

            this.currentScopes[scope] = id;
            this.currentBoxes[id] = true;
        }
    },

    closeScope: function (scope) {
        jQuery("#Dialogbox_" + this.currentScopes[scope]).dialog('close');
        delete this.currentScopes[scope];
    },

    closeBox: function (id, kill) {
        delete this.currentBoxes[id];
        if (kill) {
            jQuery("#Dialogbox_" + id).remove();
        } else {
            jQuery("#Dialogbox_" + id).attr("id", "#Dialogbox_" + id + "_dragged");
        }
    },

    openForumPosting: function (id, element) {
        var coord = "center", //coordinates to give to dialogbox - "center" means center of window
        data = STUDIP.Dialogbox.cache["forum_" + id];

        if (element) {
            coord = jQuery(element).position();
            coord = [coord.left + jQuery(element).width() + 2, coord.top - jQuery(window).scrollTop()];
        }

        STUDIP.Dialogbox.closeForumPosting(id);
        STUDIP.Dialogbox.forumTimeout = window.setTimeout(function () {
            if (!data) {
                jQuery.getJSON("dispatch.php/content_element/get_formatted/forum/" + id, function (new_data) {
                    STUDIP.Dialogbox.cache["forum_" + id] = new_data;
                    STUDIP.Dialogbox.openBox(id, new_data.title, new_data.content, coord, "forum");
                });
            } else {
                STUDIP.Dialogbox.openBox(id, data.title, data.content, coord, "forum");
            }
        }, 300);
    },

    closeForumPosting: function () {
        window.clearTimeout(STUDIP.Dialogbox.forumTimeout);
        STUDIP.Dialogbox.forumTimeout = null;

        STUDIP.Dialogbox.closeScope("forum");
    }
};


/* setup event handler */
jQuery('.forum-icon').live('mouseenter', function () {
    STUDIP.Dialogbox.openForumPosting(jQuery(this).metadata().forumid, this);
}).live('mouseleave', function () {
    STUDIP.Dialogbox.closeForumPosting();
});
