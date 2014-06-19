/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */

/* ------------------------------------------------------------------------
 * Evaluationen
 * ------------------------------------------------------------------------ */

EVALUATIONSWIDGET = {

    comsubmitvote: function(id,cont,url){
        jQuery("#"+cont+"_item_"+ id + "_content").load(url,jQuery("#comsubmit").serialize(),
            function () {

                jQuery("#"+cont+"_item_"+ id + "_content").slideDown(400);
                jQuery("#"+cont+"_item_"+ id + " .printhead2 img")
                .attr('src', STUDIP.ASSETS_URL + "images/forumgraurunt2.png");
                jQuery("#"+cont+"_item_" + id +  " .printhead2")
                .removeClass("printhead2")
                .addClass("printhead3");
                jQuery("#"+cont+"_item_" + id +  " .printhead b").css("font-weight", "bold");
                jQuery("#"+cont+"_item_" + id +  " .printhead a.tree").css("font-weight", "bold");

                jQuery('.add_toolbar').addToolbar();
            });
        return false;
    },

    createopenvotesortunsort: function(id,url,prev){

        jQuery.ajax ({
            type: 'POST',
            url: url,
            data: {
                id:id,
                prev:prev
            },
            success: function (data) {

                jQuery("#vote_item_" + id + "_content").empty();
                jQuery("#vote_item_" + id + "_content").append( '<tr><td">' +data+  '</td></tr>');

            }
        });

        return false;
    },

    showNames: function(id,url){

        jQuery.ajax ({
            type: 'POST',
            url: url,
            data: {
                id:id
            },
            success: function (data) {

                jQuery("#vote_item_" + id + "_content").empty();
                jQuery("#vote_item_" + id + "_content").append( '<tr><td">' +data+  '</td></tr>');

            }
        });

        return false;
    },
    createopenvotechanged: function(id,url){

        jQuery.ajax ({
            type: 'POST',
            url: url,
            data: {id: id, change_answer: true}, // data: jQuery("#comsubmit").serialize(),
            success: function (data) {
                node = jQuery("#vote_item_" + id + "_content");
                node.empty();
                node.append( '<tr><td>' +data+  '</td></tr>');
            }
        });

        return false;
    },
    back: function(id,url,cont){
        jQuery("#"+cont+"_item_"+ id + "_content").load(url,{
            "id":id
        },
        function () {

            jQuery("#"+cont+"_item_"+ id + "_content").slideDown(400);
            jQuery("#"+cont+"_item_"+ id + " .printhead2 img")
            .attr('src', STUDIP.ASSETS_URL + "images/forumgraurunt2.png");
            jQuery("#"+cont+"_item_" + id +  " .printhead2")
            .removeClass("printhead2")
            .addClass("printhead3");
            jQuery("#"+cont+"_item_" + id +  " .printhead b").css("font-weight", "bold");
            jQuery("#"+cont+"_item_" + id +  " .printhead a.tree").css("font-weight", "bold");

            jQuery('.add_toolbar').addToolbar();
        });


        return false;
    },
    createopenvotepreview: function(id,url,cont){
        jQuery("#"+cont+"_item_"+ id + "_content").load(url,{
            "id":id
        },
        function () {

            jQuery("#"+cont+"_item_"+ id + "_content").slideDown(400);
            jQuery("#"+cont+"_item_"+ id + " .printhead2 img")
            .attr('src', STUDIP.ASSETS_URL + "images/forumgraurunt2.png");
            jQuery("#"+cont+"_item_" + id +  " .printhead2")
            .removeClass("printhead2")
            .addClass("printhead3");
            jQuery("#"+cont+"_item_" + id +  " .printhead b").css("font-weight", "bold");
            jQuery("#"+cont+"_item_" + id +  " .printhead a.tree").css("font-weight", "bold");

            jQuery('.add_toolbar').addToolbar();
        });


        return false;
    },
    openclose: function (id,cont) {
        if (jQuery("#"+cont+"_item_" + id  + "_content").is(':visible')) {
            EVALUATIONSWIDGET.close(id,cont);
        } else {
            EVALUATIONSWIDGET.open(id,cont);
        }
    },
    open: function (id, cont) {
        var $news = jQuery("#"+cont+"_item_" + id );
        if (!$news.length) {
            return;
        }
        var urltmpl = $news.attr("data-url");
        jQuery("#"+cont+"_item_" + id + "_content").load(urltmpl,{
            "id":id
        },
        function () {

            jQuery("#"+cont+"_item_"+ id + "_content").slideDown(400);
            jQuery("#"+cont+"_item_"+ id + " .printhead2 img")
            .attr('src', STUDIP.ASSETS_URL + "images/forumgraurunt2.png");
            jQuery("#"+cont+"_item_" + id +  " .printhead2")
            .removeClass("printhead2")
            .addClass("printhead3");
            jQuery("#"+cont+"_item_" + id +  " .printhead b").css("font-weight", "bold");
            jQuery("#"+cont+"_item_" + id +  " .printhead a.tree").css("font-weight", "bold");
            jQuery('.add_toolbar').addToolbar();
        });
    },

    close: function (id,cont) {
        jQuery("#"+cont+"_item_" + id +  "_content").slideUp(400);
        jQuery("#"+cont+"_item_" + id +  " .printhead3 img")
        .attr('src', STUDIP.ASSETS_URL + "images/forumgrau2.png");
        jQuery("#"+cont+"_item_" + id +  " .printhead3")
        .removeClass("printhead3")
        .addClass("printhead2");
        jQuery("#"+cont+"_item_" + id +  " .printhead b").css("font-weight", "normal");
        jQuery("#"+cont+"_item_" + id +  " .printhead a.tree").css("font-weight", "normal");
    },
    openclosestopped: function () {
        if (jQuery("#stopped_contend").is(':visible')) {
            EVALUATIONSWIDGET.closestopped();
        } else {

            EVALUATIONSWIDGET.openstopped();
        }
    },
    openstopped: function () {

        var $news = jQuery("#stopped_contend" );
        if (!$news.length) {
            return;
        }

        var urltmpl = $news.attr("data-url");

        jQuery("#stopped_contend").load(urltmpl,
            function () {

                jQuery("#stopped_contend").slideDown(400);
                jQuery("#stopped_contend .printhead2 img")
                .attr('src', STUDIP.ASSETS_URL + "images/forumgraurunt2.png");
                jQuery("#stopped_contend .printhead2")
                .removeClass("printhead2")
                .addClass("printhead3");
                jQuery("#stopped_contend .printhead b").css("font-weight", "bold");
                jQuery("#stopped_contend .printhead a.tree").css("font-weight", "bold");

                jQuery('.add_toolbar').addToolbar();
            });
    },

    closestopped: function () {

        jQuery("#stopped_contend").slideUp(400);
        jQuery("#stopped_contend .printhead3 img")
        .attr('src', STUDIP.ASSETS_URL + "images/forumgrau2.png");
        jQuery("#stopped_contend .printhead3")
        .removeClass("printhead3")
        .addClass("printhead2");
        jQuery("#stopped_contend .printhead b").css("font-weight", "normal");
        jQuery("#stopped_contend .printhead a.tree").css("font-weight", "normal");
    }

}

function openEval( evalID ) {
    evalwin = window.open(STUDIP.URLHelper.getURL('show_evaluation.php?evalID=' + evalID + '&isPreview=".$isPreview."'),  evalID, 'width=790,height=500,scrollbars=yes,resizable=yes');
    evalwin.focus();
}


