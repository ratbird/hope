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
        jQuery.each(response.messages, function (message_id, message) {
            if (jQuery("#message_" + message_id).length === 0) {
                jQuery("#messages > tbody").prepend(message);
            }
        });
        jQuery("#since").val(Math.floor(new Date().getTime() / 1000));
    },

    /*********** helper for the overview site ***********/

    whenMessageIsShown: function (lightbox) {
        jQuery(lightbox).closest("tr").removeClass("unread");
    },


    /*********** helper for the composer-site ***********/

    add_adressee: function (user_id, name) {
        var new_adressee = jQuery("#template_adressee").clone();
        new_adressee.find("input").val(user_id);
        new_adressee.find(".visual").html(name).find("b").replaceWith(function() { return jQuery(this).contents(); });
        new_adressee.find('img.avatar-small').remove();
        new_adressee.removeAttr("id").appendTo("#adressees").fadeIn();
        return false;
    },

    add_adressees: function (form) {
        //var user_ids = jQuery(form).find("select#add_adressees_selectbox").val();
        jQuery(form).find(".ms-selection ul > li").each(function () {
            if (jQuery(this).is(":visible")) {
                var user_id = jQuery(this).attr("id").substr(0, 32);
                var name = jQuery(this).text();
                
                var new_adressee = jQuery("#template_adressee").clone();
                new_adressee.find("input").val(user_id);
                new_adressee.find(".visual").html(name);
                new_adressee.removeAttr("id").appendTo("#adressees").fadeIn();
            }
        });
        jQuery(form).closest(".ui-dialog-content").dialog("close");
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
    checkAdressee: function () {
        // Check if recipients added (one element is always there -> template)
        if (jQuery('li.adressee').children('input[name^="message_to"]').length <= 1) {
            jQuery('#user_id_1').attr('required', 'required')
            					.attr('value', '')
            					[0].setCustomValidity('Sie haben nicht angegeben, wer die Nachricht empfangen soll!'.toLocaleString());
            return true
        } else {
            jQuery('#user_id_1').removeAttr('required')
            					[0].setCustomValidity('');
            return true;
        }
    },
    setTags: function (message_id, tags) {
        var container = jQuery('#message_' + message_id).find('.tag-container').empty(),
            template  = _.template('<a href="<%= url %>" class="message-tag"><%= tag %></a>');

        jQuery.each(tags, function (index, tag) {
            var html = template({
                url: STUDIP.URLHelper.getURL('dispatch.php/messages/overview', {tag: tag}),
                tag: tag.charAt(0).toUpperCase() + tag.slice(1) // ucfirst
            });
            jQuery(container).append(html).append(' ');
        });
    },
    setAllTags: function (tags) {
        var container = jQuery('#messages-tags ul').empty(),
            template  = _.template('<li><a href="<%= url %>" class="tag"><%= tag %></a></li>');

        jQuery.each(tags, function (index, tag) {
            var html = template({
                url: STUDIP.URLHelper.getURL('dispatch.php/messages/overview', {tag: tag}),
                tag: tag.charAt(0).toUpperCase() + tag.slice(1) // ucfirst
            });
            jQuery(container).append(html);
        });
        jQuery('#messages-tags').toggle(tags.length !== 0).find('li:has(.tag)').each(STUDIP.Messages.createDroppable);
    },
    createDroppable: function (element) {
        jQuery(arguments.length === 1 ? element : this).droppable({
            hoverClass: 'dropping',
            drop: function (event, ui) {
                var message_id = ui.draggable.attr('id').substr(ui.draggable.attr("id").lastIndexOf("_") + 1),
                    tag = jQuery(this).text().trim();
                jQuery.post(STUDIP.URLHelper.getURL('dispatch.php/messages/tag/' + message_id), {
                    add_tag: tag
                }).then(function (response, status, xhr) {
                    var tags = jQuery.parseJSON(xhr.getResponseHeader('X-Tags'));
                    STUDIP.Messages.setTags(message_id, tags);
                });
            }
        });        
    },
    toggleSetting: function (name) {
        jQuery("#" + name).toggle("fade");
        if (jQuery("#" + name).is(":visible")) {
            jQuery("#" + name)[0].scrollIntoView(false);
        }
    },
    previewComposedMessage: function () {
        var old_written_text = "",
            written_text = jQuery("textarea[name=message_body]").val();
        var updatePreview = function () {
            written_text = jQuery("textarea[name=message_body]").val();
            if (old_written_text !== written_text) {
                jQuery.ajax({
                    url: STUDIP.ABSOLUTE_URI_STUDIP + "dispatch.php/messages/preview",
                    data: {
                        text: written_text
                    },
                    type: "POST",
                    success: function (html) {
                        jQuery("#preview .message_body").html(html);
                    }
                });
                old_written_text = written_text;
            }
            if (jQuery("#preview .message_body").is(":visible")) {
                window.setTimeout(updatePreview, 1000);
            }
        };
        updatePreview();
    }
};

jQuery(document).on('dialog-load', 'form#message-tags', function (event, data) {
    var tags          = jQuery.parseJSON(data.xhr.getResponseHeader('X-Tags')),
        all_tags      = jQuery.parseJSON(data.xhr.getResponseHeader('X-All-Tags')),
        message_id    = jQuery(this).closest('table').data().message_id;
    STUDIP.Messages.setTags(message_id, tags);
    STUDIP.Messages.setAllTags(all_tags);
});

jQuery(document).on('dialog-open', '#messages .title a', function () {
    STUDIP.Messages.whenMessageIsShown(this);
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
        cursorAt: {left: 28, top: 15},
        helper: function () {
            var title = jQuery(this).find('.title').text().trim();
            return jQuery('<div id="message-move-handle">').text(title);
        },
        revert: true,
        revertDuration: "200",
        appendTo: 'body',
        zIndex: 1000,
        start: function () {
            jQuery('#messages-tags').addClass('dragging');
        },
        stop: function () {
            jQuery('#messages-tags').removeClass('dragging');
        }
    });
    jQuery('.widget-links li:has(.tag)').each(STUDIP.Messages.createDroppable);

    jQuery(".adressee .remove_adressee").live("click", STUDIP.Messages.remove_adressee);
});
