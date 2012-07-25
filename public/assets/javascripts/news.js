/* ------------------------------------------------------------------------
 * News
 * ------------------------------------------------------------------------ */

STUDIP.News = {
    openclose: function (id, admin_link) {
        if (jQuery("#news_item_" + id + "_content").is(':visible')) {
            STUDIP.News.close(id);
        } else {
            STUDIP.News.open(id, admin_link);
        }
    },

    open: function (id, admin_link) {
        jQuery("#news_item_" + id + "_content").load(
            STUDIP.ABSOLUTE_URI_STUDIP + 'dispatch.php/news/get_news/' + id,
            {admin_link: admin_link},
            function () {
                jQuery("#news_item_" + id + "_content").slideDown(400);
                jQuery("#news_item_" + id + " .printhead2 img")
                    .attr('src', STUDIP.ASSETS_URL + "images/forumgraurunt2.png");
                jQuery("#news_item_" + id + " .printhead2")
                    .removeClass("printhead2")
                    .addClass("printhead3");
                jQuery("#news_item_" + id + " .printhead b").css("font-weight", "bold");
                jQuery("#news_item_" + id + " .printhead a.tree").css("font-weight", "bold");
            });
    },

    close: function (id) {
        jQuery("#news_item_" + id + "_content").slideUp(400);
        jQuery("#news_item_" + id + " .printhead3 img")
            .attr('src', STUDIP.ASSETS_URL + "images/forumgrau2.png");
        jQuery("#news_item_" + id + " .printhead3")
            .removeClass("printhead3")
            .addClass("printhead2");
        jQuery("#news_item_" + id + " .printhead b").css("font-weight", "normal");
        jQuery("#news_item_" + id + " .printhead a.tree").css("font-weight", "normal");
    }
};
