/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */

/* ------------------------------------------------------------------------
 * Termin
 * ------------------------------------------------------------------------ */

STUDIP.Termine = {
    openclose: function (id) {
        if (jQuery("#termin_item_" + id + "_content").is(':visible')) {
            STUDIP.Termine.close(id);
        } else {
            STUDIP.Termine.open(id);
        }
    },
    opencloseSem: function (id, showadmin, type, info) {
        if (jQuery("#termin_item_" + id + "_content").is(':visible')) {
            STUDIP.Termine.closeSem(id);
        } else {
            STUDIP.Termine.openSem(id, showadmin, type, info);
        }
    },

    openSem: function (id, showadmin, type, info) {
        jQuery("#termin_item_" + id + "_content").load(
            STUDIP.ABSOLUTE_URI_STUDIP + 'dispatch.php/termine/get_termin/' + id + '/' + showadmin + '/' + type + '/' + info,
            function () {
                jQuery("#termin_item_" + id + "_content").slideDown(400);
                jQuery("#termin_item_" + id + " .printhead2 img")
                    .attr('src', STUDIP.ASSETS_URL + "images/forumgraurunt2.png");
                jQuery("#termin_item_" + id + " .printhead2")
                    .removeClass("printhead2")
                    .addClass("printhead3");
                jQuery("#termin_item_" + id + " .printhead b").css("font-weight", "bold");
                jQuery("#termin_item_" + id + " .printhead a.tree").css("font-weight", "bold");
            }
        );
    },


    open: function (id) {
        jQuery("#termin_item_" + id + "_content").slideDown(400);
        jQuery("#termin_item_" + id + " .printhead2 img")
            .attr('src', STUDIP.ASSETS_URL + "images/forumgraurunt2.png");
        jQuery("#termin_item_" + id + ".printhead2")
            .removeClass("printhead2")
            .addClass("printhead3");
        jQuery("#termin_item_" + id + " .printhead b").css("font-weight", "bold");
        jQuery("#termin_item_" + id + " .printhead a.tree").css("font-weight", "bold");
           
    },

    close: function (id) {
        jQuery("#termin_item_" + id + "_content").slideUp(400);
        jQuery("#termin_item_" + id + " .printhead3 img")
            .attr('src', STUDIP.ASSETS_URL + "images/forumgrau2.png");
        jQuery("#termin_item_" + id + " .printhead3")
            .removeClass("printhead3")
            .addClass("printhead2");
        jQuery("#termin_item_" + id + " .printhead b").css("font-weight", "normal");
        jQuery("#termin_item_" + id + " .printhead a.tree").css("font-weight", "normal");
    },
    closeSem: function (id) {
        jQuery("#termin_item_" + id + "_content").slideUp(400);
        jQuery("#termin_item_" + id + " .printhead3 img")
            .attr('src', STUDIP.ASSETS_URL + "images/forumgrau2.png");
        jQuery("#termin_item_" + id + " .printhead3")
            .removeClass("printhead3")
            .addClass("printhead2");
        jQuery("#termin_item_" + id + " .printhead b").css("font-weight", "normal");
        jQuery("#termin_item_" + id + " .printhead a.tree").css("font-weight", "normal");
    }
};
