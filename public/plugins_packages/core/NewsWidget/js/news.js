
/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */

/* ------------------------------------------------------------------------
 * News
 * ------------------------------------------------------------------------ */

NEWSWIDGET = {

    delNews: function (url, comdelId, comdelnewsId) {

        $.post(url ,
        {
            newsId: comdelnewsId,
            comId: comdelId
        },function (data) {
                jQuery("#" + comdelId).remove();


            });

    },
    comopenNews: function (comopenid, widgetId) {
      //  var $news = $("#show_no_comments_" + comopenid);

     //   if (!$news.length) {
      //      return;
     //   }
      //  var urltmpl = _.template($news.attr("data-url"));
     //   jQuery("#show_comments_" + comopenid).load(urltmpl({action: "comopenNews"}),
     //       {id:comopenid},
       //     function () {
                jQuery("#show_comments_" + comopenid + widgetId).slideDown(400);
                jQuery("#show_no_comments_" + comopenid + widgetId).css("display", "none");
      //      });



    },

    openclose: function (id, admin_link,widgetId) {
        if (jQuery("#news_item_" + id + widgetId + "_content").is(':visible')) {
            NEWSWIDGET.close(id,widgetId);
        } else {
            NEWSWIDGET.open(id, admin_link,widgetId);
        }
    },

    open: function (id, admin_link,widgetId) {
        var $news = $("#news_item_" + id + widgetId + "_content");

        if (!$news.length) {
            return;
        }
        var urltmpl = _.template($news.attr("data-url"));
        jQuery("#news_item_" + id + widgetId + "_content").load(urltmpl({action: "get_news"}),
            {id:id, admin_link: admin_link, widgetId:widgetId},
            function () {
                jQuery("#news_item_"+ id + widgetId + "_content").slideDown(400);
                jQuery("#news_item_"+ id + widgetId + " .printhead2 img")
                    .attr('src', STUDIP.ASSETS_URL + "images/forumgraurunt2.png");
                jQuery("#news_item_" + id + widgetId + " .printhead2")
                    .removeClass("printhead2")
                    .addClass("printhead3");
                jQuery("#news_item_" + id + widgetId + " .printhead b").css("font-weight", "bold");
                jQuery("#news_item_" + id + widgetId + " .printhead a.tree").css("font-weight", "bold");

                jQuery('.add_toolbar').addToolbar();
            });
    },

    close: function (id,widgetId) {
        jQuery("#news_item_" + id + widgetId + "_content").slideUp(400);
        jQuery("#news_item_" + id + widgetId + " .printhead3 img")
            .attr('src', STUDIP.ASSETS_URL + "images/forumgrau2.png");
        jQuery("#news_item_" + id + widgetId + " .printhead3")
            .removeClass("printhead3")
            .addClass("printhead2");
        jQuery("#news_item_" + id + widgetId + " .printhead b").css("font-weight", "normal");
        jQuery("#news_item_" + id + widgetId + " .printhead a.tree").css("font-weight", "normal");
    },
    comsubmit: function(widgetId ){

        jQuery.ajax ({
            type: 'POST',
            url: jQuery("#comsubmit").attr('data-url'),
            data: jQuery("#comsubmit").serialize(),
            success: function (data) {
                jQuery("#commentstable"+ widgetId ).append( '<tr><td>' +data+  '</td></tr>');
                jQuery("textarea[name=comment_content]").val('');
            }
        });

        return false;
    }
};
