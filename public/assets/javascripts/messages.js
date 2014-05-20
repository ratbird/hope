/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */

STUDIP.Messages = {


    /*********** AJAX-reload function for overview ***********/

    periodicalPushData: function () {
        if (jQuery("#messages").length && jQuery("#since").val()) {
            return {
                'since': jQuery("#since").val(),
                'received': jQuery("#received").val(),
                'tag': jQuery("#tag").val()
            };
        }
    },
    newMessages: function (response) {
        jQuery.each(response.messages, function (index, message) {
            jQuery("#messages > tbody").prepend(message);
        });
        jQuery("#since").val(Math.floor(new Date().getTime() / 1000));
    },

    /*********** helper for the overview site ***********/

    whenMessageIsShown: function (lightbox) {
        var message_id = jQuery("#message_metadata").data("message_id");
        jQuery("#message_" + message_id).removeClass("unread");
    },


    /*********** helper for the composer-site ***********/

    add_adressee: function (user_id, name) {
        var new_adressee = jQuery("#template_adressee").clone();
        new_adressee.find("input").val(user_id);
        new_adressee.find(".visual").html(name).find("b").replaceWith(function() { return jQuery(this).contents(); });
        new_adressee.removeAttr("id").appendTo("#adressees").fadeIn();
        return false;
    },

    add_adressees: function (form) {
        //var user_ids = jQuery(form).find("select#add_adressees_selectbox").val();
        jQuery(form).find(".ms-selection ul > li").each(function () {
            if (jQuery(this).is(":visible")) {
                var user_id = jQuery(this).attr("id").substr(0, 32);
                var name = jQuery(this).text();
                var image = jQuery(this).find("img").clone()
                        .attr("height", "25px")
                        .css({"float": "none",'margin':"0px"})
                    .addClass("avatar-small");
                var new_adressee = jQuery("#template_adressee").clone();
                new_adressee.find("input").val(user_id);
                new_adressee.find(".visual").html(image[0].outerHTML + name);
                new_adressee.removeAttr("id").appendTo("#adressees").fadeIn();
            }
        });
        jQuery(".ui-dialog-content").dialog("close");
        return false;
    },

    remove_adressee: function () {
        jQuery(this).closest("li").fadeOut(300, function() { jQuery(this).remove(); });
    },

    upload_from_input: function (input) {
        STUDIP.Messages.upload_files(input.files);
        jQuery(input).val("");
    },
    fileIDQueue: 1,
    upload_files: function (files) {
        for (var i = 0; i < files.length; i++) {
            var fd = new FormData();
            fd.append('file', files[i]);
            var statusbar = jQuery("#statusbar_container .statusbar").first().clone().show();
            statusbar.appendTo("#statusbar_container");
            fd.append('message_id', jQuery("#message_id").val());
            STUDIP.Messages.upload_file(fd, statusbar);
        }
    },
    upload_file: function (formdata, statusbar) {
        var extraData = {}; //Extra Data.
        var jqXHR = $.ajax({
            xhr: function() {
                var xhrobj = $.ajaxSettings.xhr();
                if (xhrobj.upload) {
                    xhrobj.upload.addEventListener('progress', function(event) {
                        var percent = 0;
                        var position = event.loaded || event.position;
                        var total = event.total;
                        if (event.lengthComputable) {
                            percent = Math.ceil(position / total * 100);
                        }
                        //Set progress
                        statusbar.find(".progress")
                            .css({"min-width": percent + "%", "max-width": percent + "%"});
                        statusbar.find(".progresstext")
                            .text(percent === 100 ? jQuery("#upload_finished").text() : percent + "%");
                    }, false);
                }
                return xhrobj;
            },
            url: STUDIP.ABSOLUTE_URI_STUDIP + "dispatch.php/messages/upload_attachment",
            type: "POST",
            contentType: false,
            processData: false,
            cache: false,
            data: formdata,
            dataType: "json",
            success: function(data) {
                statusbar.find(".progress").css({"min-width": "100%", "max-width": "100%"});
                var file = jQuery("#attachments .files > .file").first().clone();
                file.find(".name").text(data.name);
                if (data.size < 1024) {
                    file.find(".size").text(data.size + "B");
                }
                if (data.size > 1024 && data.size < 1024 * 1024) {
                    file.find(".size").text(Math.floor(data.size / 1024) + "KB");
                }
                if (data.size > 1024 * 1024 && data.size < 1024 * 1024 * 1024) {
                    file.find(".size").text(Math.floor(data.size / 1024 / 1024) + "MB");
                }
                if (data.size > 1024 * 1024 * 1024) {
                    file.find(".size").text(Math.floor(data.size / 1024 / 1024 / 1024) + "GB");
                }
                file.find(".icon").html(data.icon);

                file.appendTo("#attachments .files");
                file.fadeIn(300);
                statusbar.find(".progresstext").text(jQuery("#upload_received_data").text());
                statusbar.delay(1000).fadeOut(300, function () { jQuery(this).remove(); });
            },
            error: function(jqxhr, status, errorThrown) {
                statusbar.find(".progress").addClass("error").attr("title", errorThrown);
                statusbar.find(".progresstext").text(errorThrown);
                statusbar.bind("click", function() { jQuery(this).fadeOut(300, function () { jQuery(this).remove(); })});
            }
        });
    },
    send: function (form) {
        console.log(form);
        return false;
    }
};


/*********** for the single-message view ***********/

jQuery("#message_metadata .add_new_tag").live("click", function () {
    var tag = jQuery(this).parent().find("input").val();
    var message_id = jQuery("#message_metadata").data("message_id");
    if (tag.length) {
        jQuery.ajax({
            'url': STUDIP.ABSOLUTE_URI_STUDIP + "dispatch.php/messages/add_tag",
            'data': {
                'message_id': message_id,
                'tag': tag
            },
            'type': "post",
            'dataType': "json",
            'success': function (response) {
                if (jQuery(".ui-dialog-content").length) {
                    jQuery(".ui-dialog-content").html(response['full']);
                    jQuery("#message_" + message_id).replaceWith(response['row']);
                } else {
                    location.href = STUDIP.ABSOLUTE_URI_STUDIP + "dispatch/messages/read/" + message_id;
                }
            }
        });
    }
});
jQuery("#message_metadata .remove_tag").live("click", function () {
    var tag = jQuery(this).closest(".tag").data("tag");
    var message_id = jQuery("#message_metadata").data("message_id");
    jQuery.ajax({
        'url': STUDIP.ABSOLUTE_URI_STUDIP + "dispatch.php/messages/remove_tag",
        'data': {
            'message_id': message_id,
            'tag': tag
        },
        'type': "post",
        'dataType': "json",
        'success': function (response) {
            if (jQuery(".ui-dialog-content").length) {
                jQuery(".ui-dialog-content").html(response['full']);
                jQuery("#message_" + message_id).replaceWith(response['row']);
            } else {
                location.href = STUDIP.ABSOLUTE_URI_STUDIP + "dispatch/messages/read/" + message_id;
            }
        }
    });
});

jQuery(document).on('lightbox-open', '#messages .title a', function () {
    STUDIP.Messages.whenMessageIsShown();
})

jQuery(function () {

    /*********** infinity-scroll in the overview ***********/
    if (jQuery("#messages").length > 0) {
        jQuery(window.document).bind('scroll', _.throttle(function (event) {

            if ((jQuery(window).scrollTop() + jQuery(window).height() > jQuery(window.document).height() - 500)
                && (jQuery("#reloader").hasClass("more"))) {
                //nachladen
                jQuery("#reloader").removeClass("more").addClass("loading");
                jQuery.ajax({
                    url: STUDIP.ABSOLUTE_URI_STUDIP + "dispatch.php/messages/more",
                    data: {
                        'received': jQuery("#received").val(),
                        'offset': jQuery("#messages > tbody > tr").length - 1,
                        'tag': jQuery("#tag").val(),
                        'search': jQuery("#search").val(),
                        'search_autor': jQuery("#search_autor").val(),
                        'search_subject': jQuery("#search_subject").val(),
                        'search_content': jQuery("#search_content").val(),
                        'limit': 50
                    },
                    dataType: "json",
                    success: function (response) {
                        var more_indicator = jQuery("#reloader").detach();

                        jQuery("#loaded").val(parseInt(jQuery("#loaded").val(), 10) + 1);
                        jQuery.each(response.messages, function (index, message) {
                            jQuery("#messages > tbody").append(message);
                        });

                        if (response.more) {
                            jQuery("#messages > tbody").append(more_indicator.addClass("more").removeClass("loading"));
                        }
                    }
                });
            }
        }, 30));
    }

    /*********** dragging the messages to the tags ***********/

    jQuery("#messages > tbody > tr").draggable({
        //cursor: "move",
        helper: function () {
            var handle = jQuery("#move_handle").clone().show();
            handle.find(".title").text(jQuery(this).find(".title").text());
            return handle;
        },
        revert: true,
        revertDuration: "200"
    });
    jQuery(".widget-links .tag").droppable({
        'hoverClass': "drop",
        'drop': function (event, ui) {
            var message_id = ui.draggable.attr("id").substr(ui.draggable.attr("id").lastIndexOf("_") + 1);
            var tag = jQuery(this).text();
            jQuery.ajax({
                'url': STUDIP.ABSOLUTE_URI_STUDIP + "dispatch.php/messages/add_tag",
                'data': {
                    'message_id': message_id,
                    'tag': tag
                },
                'type': "post",
                'dataType': "json",
                'success': function (response) {
                    jQuery("#message_" + message_id).replaceWith(response.row);
                }
            });
        }
    });

    jQuery(".adressee .remove_adressee").live("click", STUDIP.Messages.remove_adressee);
});
