/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */

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
    },

    get_dialog: function (id, route, from_x, from_y) {
	    // initialize dialog
    	jQuery('body').append('<div id="'+id+'"></div>');
    	jQuery('#'+id).dialog({ 
            modal: true, 
            resizable: false,
            width: 100,
            height: 40,
            title: 'Dialog wird geladen...'.toLocaleString(),
            hide: 'fadeOut',
            // define close animation
            beforeClose: function( event, ui ) {
    			jQuery('#'+id).dialog('widget').stop(true, true);
    			jQuery('#'+id).dialog('widget').animate({
    				width: 100,
    				height: 40,
    				left: from_x - 50,
    				top: jQuery(document).scrollTop() + from_y - 20,
    				opacity: 0
    			}, {
    				duration: 400,
    				easing: 'swing'
    			});
    		}
        });
	    // show pre-loading dialog animation
    	jQuery('#'+id).html('<div class="ajax_notification" style="text-align: center; padding-right: 24px; padding-top: 55px"><div class="notification"></div></div>');
    	jQuery('#'+id).dialog('option', 'position', [from_x - 50, from_y - 20]);
        jQuery('#'+id).dialog('widget').css('opacity', 0);
        jQuery('#'+id).dialog('widget').animate({
            width: dialog_width,
            height: dialog_height,
            left: (window.innerWidth / 2) - (dialog_width / 2),
            top: jQuery(document).scrollTop() + (window.innerHeight / 2) - (dialog_height / 2),
			opacity: 1
        }, {
            duration: 400,
            easing: 'swing'
        });
        // load actual dialog content
    	jQuery.ajax({
            'url': route,
            'dataType': 'HTML',
            'success': function (html, status, xhr) {
                jQuery('#'+id).dialog('option', 'title', xhr.getResponseHeader('X-Title'));
                jQuery('#'+id).dialog('widget').stop(true, true);
                // set to full size (even if dialog.close was triggered before)
                jQuery('#'+id).dialog('widget').animate({
                    left: (window.innerWidth / 2) - (dialog_width / 2),
                    top: jQuery(document).scrollTop() + (window.innerHeight / 2) - (dialog_height / 2),
        			opacity: 1
                }, 0);
                jQuery('#'+id).dialog({
                    height: dialog_height,
                    width: dialog_width
                });
                jQuery('#'+id).html(html);
                jQuery('#'+id+'_content').css({
                	'height' : dialog_height-110+"px", 
                    'maxHeight': dialog_height-110+"px"
                });
                jQuery('.ui-dialog-content').css({'padding-right' : '1px'});

                // prevent forms within dialog from reloading whole page, and reload dialog instead
                jQuery('#'+id+' form').live('click', function(event) {
                	jQuery(this).data('clicked', $(event.target));
                });
                jQuery('#'+id+' form').live('submit', function (event) {
                	event.preventDefault();
            		button = jQuery(this).data('clicked').attr('name');
            		route = jQuery(this).attr('action');
            		form_data = jQuery(this).serialize()+'&'+button+'=1';
            		jQuery(this).find('input[name='+button+']').showAjaxNotification('left');
            		if ((current_time.getTime() - ajax_request_active) > 1000) {
            			ajax_request_active = current_time.getTime();
            			STUDIP.News.update_dialog(id, route, form_data);
            		}
                });

                // fix added elements (as in application.js)
                if (!("autofocus" in document.createElement("input"))) {
                    jQuery('[autofocus]').first().focus();
                }
                jQuery('.add_toolbar').addToolbar();
                jQuery('textarea.resizable').resizable({
                    handles: 's',
                    minHeight: 50,
                    zIndex: 1
                });
            },
            'fail': function () {
                alert("Fehler beim Aufruf des News-Controllers");
            }
        });
    },

    update_dialog: function (id, route, form_data) {
        jQuery.ajax({
            'url': route,
            'type': 'POST',
            'data': form_data,
            'dataType': 'HTML',
            'success': function (html) {
                if (html.length > 0) {
                	jQuery('#'+id).html(html);
                	jQuery('#'+id+'_content').css({
                		'height' : dialog_height-110+"px", 
                		'maxHeight': dialog_height-110+"px"
                	});
                    // scroll to anker
                	var obj = jQuery('a[name=anker]');
                    if (obj.length > 0) {
                        jQuery('#'+id+'_content').scrollTop(obj.position().top);
                    }                
                } else {
                	jQuery('#'+id).dialog('close');
                	url = location.href.split('?');
                	location.replace(url[0]+'?nsave=1');
                }
                // fix added elements (as in application.js)
                if (!("autofocus" in document.createElement("input"))) {
                    jQuery('[autofocus]').first().focus();
                }
                jQuery('.add_toolbar').addToolbar();
                jQuery('textarea.resizable').resizable({
                    handles: 's',
                    minHeight: 50,
                    zIndex: 1
                });
            },
            'fail': function () {
                alert("Fehler beim Aufruf des News-Controllers");
            }
        });
    },
    
    toggle_category_view: function (id) {
		if (jQuery("input[name=" + id + "_js]").val() == "toggle") {
			jQuery("input[name=" + id + "_js]").val("")
		} else {
			jQuery("input[name=" + id + "_js]").val("toggle");
		}
    	if (jQuery("#" + id + "_content").is(':visible')) {
            jQuery("#" + id + "_content").slideUp(400);
            jQuery("#" + id + " input[type=image]:first")
                .attr('src', STUDIP.ASSETS_URL + "images/icons/16/blue/arr_1right.png");
        } else {
            jQuery("#" + id + "_content").slideDown(400);
            jQuery("#" + id + " input[type=image]:first")
                .attr('src', STUDIP.ASSETS_URL + "images/icons/16/blue/arr_1down.png");
        }
    }
};

jQuery(function () {
	ajax_request_active = 0;
	current_time = new Date();
	dialog_height = window.innerHeight - 60;
    dialog_width = 550;
    if (dialog_height < 400)
        dialog_height = 400;

    jQuery('a[rel~="get_dialog"]').live('click', function (event) {
	    event.preventDefault();
        var from_x = jQuery(this).position().left + (jQuery(this).outerWidth() / 2);
        var from_y = jQuery(this).position().top + (jQuery(this).outerHeight() / 2) - jQuery(document).scrollTop();
		STUDIP.News.get_dialog('news_dialog', jQuery(this).attr('href'), from_x, from_y);
    });

    jQuery('a[rel~="close_dialog"]').live('click', function (event) {
	    event.preventDefault();
	    jQuery('#news_dialog').dialog('close');
    });

    // open/close categories without ajax-request
    jQuery('.news_category_header').live('click', function (event) {
    	event.preventDefault();
    	STUDIP.News.toggle_category_view(jQuery(this).parent('div').attr('id'));
    });
    jQuery('.news_category_header input[type=image]').live('click', function (event) {
    	event.preventDefault();
    });
});
