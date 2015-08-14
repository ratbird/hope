STUDIP.jsupdate_enable = true;
STUDIP.Blubber = {
    /**
     * Hands data of the current stream to the JSUpdater so that we can get
     * the right new postings, if there are any.
     */
    periodicalPushData: function () {
        return {
            'context_id': jQuery("#context_id").val(),
            'extern': jQuery("#extern").val(),
            'stream': jQuery("#stream").val(),
            'last_check': jQuery('#last_check').val(),
            'search': jQuery("#search").val()
        };
    },
    /**
     * Once the JSUpdater receives data from Stud.IP they will be handled here
     * in order to display new postings.
     */
    getNewPosts: function (data) {
        if (data.postings) {
            jQuery.each(data.postings, function (index, posting) {
                if (posting.root_id !== posting.posting_id) {
                    //comment
                    STUDIP.Blubber.insertComment(posting.root_id, posting.posting_id, posting.mkdate, posting.content);
                } else {
                    //thread
                    STUDIP.Blubber.insertThread(
                        posting.posting_id,
                        jQuery("#orderby").val() === "discussion_time" ? posting.discussion_time : posting.mkdate,
                        posting.content
                    );
                }
            });
            jQuery('#last_check').val(Math.floor(new Date().getTime() / 1000));
        }
        STUDIP.Blubber.updateTimestamps();
    },
    /**
     * Once the JSUpdater receives data from Stud.IP they will be handled here
     * in order to delete deleted postings.
     */
    blubberEvents: function (events) {
        jQuery.each(events, function (index, event) {
            if (event.event_type === "delete") {
                jQuery("#posting_" + event.item_id).fadeOut(function () {jQuery("#posting_" + event.item_id).remove();});
            }
        });
    },
    //variable to prevent multiple clicks
    alreadyThreadWriting: false,
    /**
     * writes a new posting to the database and displays it on success
     */
    newPosting: function () {
        if (STUDIP.Blubber.alreadyThreadWriting) {
            return;
        }
        if (jQuery.trim(jQuery("#new_posting").val())) {
            STUDIP.Blubber.alreadyThreadWriting = true;
            var content = jQuery("#new_posting").val();
            var context_type = jQuery("#context_type").val();
            var contact_groups = [];
            jQuery("input[type=checkbox].contact_group:checked").each(function (index, input) {
                contact_groups.push(jQuery(input).val());
            });
            if (!context_type) {
                context_type = jQuery("#context_selector input[name=context_type]").val();
            }
            jQuery.ajax({
                url: STUDIP.ABSOLUTE_URI_STUDIP + jQuery("#base_url").val() + "/new_posting",
                data: {
                    'context_type': context_type,
                    'context': jQuery("#context_selector [name=context]").val(),
                    'content': content,
                    'contact_groups': contact_groups,
                    'anonymous_name': jQuery("#anonymous_name").val(),        //nobody only
                    'anonymous_email': jQuery("#anonymous_email").val(),      //nobody only
                    'anonymous_security': jQuery("#anonymous_security").val() //nobody only
                },
                dataType: "json",
                type: "POST",
                success: function (reply) {
                    jQuery("#new_posting").val("").trigger("keydown");
                    STUDIP.Blubber.insertThread(
                        reply.posting_id,
                        jQuery("#orderby").val() === "discussion_time" ? reply.discussion_time : reply.mkdate,
                        reply.content
                    );
                    jQuery("#submit_button").hide();
                },
                complete: function () {
                    STUDIP.Blubber.alreadyThreadWriting = false;
                }
            });
        }
    },
    //variable to prevent multiple clicks
    alreadyWriting: false,
    /**
     * writes a new comment to database and displays it on success
     */
    write: function (textarea) {
        var content = jQuery(textarea).val();
        var thread = jQuery(textarea).closest("li").attr("id");
        thread = thread.substr(thread.lastIndexOf("_") + 1);

        if (!content || STUDIP.Blubber.alreadyWriting) {
            return;
        }
        STUDIP.Blubber.alreadyWriting = true;
        jQuery.ajax({
            url: STUDIP.ABSOLUTE_URI_STUDIP + jQuery("#base_url").val() + "/comment",
            data: {
                'context': jQuery(textarea).closest("li").find(".hiddeninfo > input[name=context]").val(),
                'context_type': jQuery(textarea).closest("li").find(".hiddeninfo > input[name=context_type]").val(),
                'thread': thread,
                'content': content,
                'anonymous_name': jQuery("#anonymous_name").val(),        //nobody only
                'anonymous_email': jQuery("#anonymous_email").val(),      //nobody only
                'anonymous_security': jQuery("#anonymous_security").val() //nobody only
            },
            dataType: "json",
            type: "POST",
            success: function (reply) {
                jQuery(textarea).val("").trigger("keydown");
                STUDIP.Blubber.insertComment(thread, reply.posting_id, reply.mkdate, reply.content);
            },
            complete: function () {
                STUDIP.Blubber.alreadyWriting = false;
                jQuery("#identity_window").dialog("close");
            }
        });
    },
    /**
     * Inserts any new comments if the posting to the comment in the correct order
     * if the original thread is visible.
     */
    insertComment: function (thread, posting_id, mkdate, comment) {
        if (jQuery("#posting_" + posting_id).length) {
            if (jQuery("#posting_" + posting_id + " textarea.corrector").length === 0) {
                if (jQuery("#posting_" + posting_id + " .content").html() !== jQuery(comment).find(".content").html()) {
                    //nur wenn es Unterschiede gibt
                    jQuery("#posting_" + posting_id).replaceWith(comment);
                }
            }
        } else {
            if (jQuery("#posting_" + thread + " ul.comments > li").length === 0) {
                jQuery(comment).appendTo("#posting_" + thread + " ul.comments").hide().fadeIn();
            } else {
                var already_inserted = false;
                jQuery("#posting_" + thread + " ul.comments > li").each(function (index, li) {
                    if (!already_inserted && jQuery(li).attr("mkdate") > mkdate) {
                        jQuery(comment).insertBefore(li).hide().fadeIn();
                        already_inserted = true;
                    }
                });
                if (!already_inserted) {
                    var top = jQuery(document).scrollTop();
                    jQuery(comment).appendTo("#posting_" + thread + " ul.comments").hide().fadeIn();
                    var comment_top = jQuery("#posting_" + posting_id).offset().top;
                    var height = jQuery("#posting_" + posting_id).height() +
                        + 15; //2 * padding + 1 wegen des Border
                    if (comment_top < top) {
                        jQuery(document).scrollTop(top + height);
                    }
                }
            }
        }
        STUDIP.Markup.element("#posting_" + posting_id);
        STUDIP.Blubber.updateTimestamps();
    },
    /**
     * Inserts any new thread-postings in the correct order
     */
    insertThread: function (posting_id, discussion_time, content) {
        if (jQuery("#posting_" + posting_id).length) {
            if (jQuery("#posting_" + posting_id + " > .content_column textarea.corrector").length === 0) {
                var new_version = jQuery(content);
                jQuery("#posting_" + posting_id + " > .content_column .content").html(new_version.find(".content").html());
                jQuery("#posting_" + posting_id + " > .content_column .additional_tags").html(new_version.find(".additional_tags").html());
                jQuery("#posting_" + posting_id + " > .content_column .opengraph_area").html(new_version.find(".opengraph_area").html());
                if (jQuery("#posting_" + posting_id + " > .reshares").length > 0) {
                    jQuery("#posting_" + posting_id + " > .reshares").html(new_version.find(".reshares").html());
                }
                new_version.remove();
            }
        } else {
            if (jQuery("#blubber_threads > li").length === 0) {
                jQuery(content).appendTo("#blubber_threads").hide().fadeIn();
            } else {
                var already_inserted = false;
                jQuery("#blubber_threads > li[id]").each(function (index, li) {
                    var li_time = jQuery("#orderby").val() === "discussion_time"
                        ?  jQuery(li).data("discussion_time")
                        : jQuery(li).data("mkdate");
                    if (!already_inserted && (li_time < discussion_time)) {
                        var top = jQuery(document).scrollTop();
                        jQuery(content).insertBefore(li).hide().fadeIn();
                        var comment_top = jQuery("#posting_" + posting_id).offset().top;
                        var height = jQuery("#posting_" + posting_id).height() +
                            + 15; //2 * padding + 1 für Border
                        if (comment_top < top) {
                            jQuery(document).scrollTop(top + height);
                        }
                        STUDIP.Blubber.updateTimestamps();
                        already_inserted = true;
                    }
                });
                if (!already_inserted) {
                    jQuery(content).appendTo("#blubber_threads").hide().fadeIn();
                }
            }
        }
        STUDIP.Markup.element("#posting_" + posting_id);
        STUDIP.Blubber.makeTextareasAutoresizable();
        STUDIP.Blubber.updateTimestamps();
    },
    /**
     * Fetches the original (non-formatted) text of a posting from database and
     * displays the textarea so the user can start editing it.
     */
    startEditingComment: function () {
        var id = jQuery(this).closest("li").attr("id");
        id = id.substr(id.lastIndexOf("_") + 1);
        jQuery.ajax({
            'url': STUDIP.ABSOLUTE_URI_STUDIP + jQuery("#base_url").val() + "/get_source",
            'data': {
                'topic_id': id,
                'cid': jQuery("#seminar_id").val()
            },
            'success': function (source) {
                jQuery("#posting_" + id).find(".content_column .content").first().html(
                    jQuery('<textarea class="corrector"/>').val(source).focus()
                );
                jQuery("#posting_" + id).find(".corrector").focus();
                STUDIP.Blubber.makeTextareasAutoresizable();
                jQuery("#posting_" + id).find(".corrector").trigger("keydown");
            }
        });

    },
    //variable to prevent multiple clicks
    submittingEditedPostingStarted: false,
    /**
     * Submits an edited posting and displays its new content on success
     */
    submitEditedPosting: function (textarea) {
        var id = jQuery(textarea).closest("li").attr("id");
        id = id.substr(id.lastIndexOf("_") + 1);
        if (STUDIP.Blubber.submittingEditedPostingStarted) {
            return;
        }
        STUDIP.Blubber.submittingEditedPostingStarted = true;
        if (jQuery("#posting_" + id).attr("data-autor") === jQuery("#user_id").val()
                || window.confirm(jQuery("#editing_question").text())) {
            STUDIP.Blubber.submittingEditedPostingStarted = false;
            jQuery.ajax({
                'url': STUDIP.ABSOLUTE_URI_STUDIP + jQuery("#base_url").val() + "/edit_posting",
                'data': {
                    'topic_id': id,
                    'content': jQuery(textarea).val(),
                    'cid': jQuery("#seminar_id").val()
                },
                'type': "post",
                'success': function (new_content) {
                    if (new_content) {
                        jQuery("#posting_" + id + " > .content_column .content").html(new_content);
                        STUDIP.Markup.element("#posting_" + id);
                    } else {
                        jQuery("#posting_" + id).fadeOut(function () {jQuery("#posting_" + id).remove();});
                    }
                }
            });
        } else {
            STUDIP.Blubber.submittingEditedPostingStarted = false;
            jQuery.ajax({
                'url': STUDIP.ABSOLUTE_URI_STUDIP + jQuery("#base_url").val() + "/refresh_posting",
                'data': {
                    'topic_id': id,
                    'cid': jQuery("#seminar_id").val()
                },
                'success': function (new_content) {
                    jQuery("#posting_" + id + " > .content_column .content").html(new_content);
                    STUDIP.Markup.element("#posting_" + id);
                }
            });
        }
    },
    /**
     * All textareas in blubber are autoresizable and able to receive dropped
     * files. This function initializes the autoresizer and file-dropper functions.
     */
    makeTextareasAutoresizable: function () {
        jQuery("#blubber_threads textarea:not(.autoresize), #new_posting:not(.autoresize)")
        .addClass("autoresize")
            .bind('dragover dragleave', function (event) {
            jQuery(this).toggleClass('hovered', event.type === 'dragover');
            return false;
        }).autoResize({
            // On resize:
            onResize : function() {
                $(this).css({opacity: 0.8});
            },
            // After resize:
            animateCallback : function() {
                $(this).css({opacity:1});
            },
            // Quite slow animation:
            animateDuration: 300,
            // More extra space:
            extraSpace: 0
        }).each(function (index, textarea) {
            //and here the file-dropping function:
            jQuery(textarea).on("drop", function (event) {
                event.preventDefault();
                var files = 0;
                var file_info = event.originalEvent.dataTransfer.files || {};
                var data = new FormData();

                var thread = jQuery(textarea).closest("li.thread");
                if (thread && thread.find(".hiddeninfo input[name=context_type]").val() === "course") {
                    var context_id = thread.find(".hiddeninfo input[name=context]").val();
                    var context_type = "course";
                } else {
                    var context_type = jQuery("#context_selector input[name=context_type]:checked").val();
                    if ((jQuery("#stream").val() === "course") || jQuery("#context_selector input[name=context_type]:checked").val()) {
                        var context_id = jQuery("#context_selector input[name=context]").val();
                        context_type = context_type ? context_type : "course";
                    }
                    if (!context_id) {
                        var context_id = jQuery("#user_id").val();
                        context_type = "public";
                    }
                }
                jQuery.each(file_info, function (index, file) {
                    if (file.size > 0) {
                        data.append(index, file);
                        files += 1;
                    }
                });
                if (files > 0) {
                    jQuery(textarea).addClass("uploading");
                    jQuery.ajax({
                        'url': STUDIP.ABSOLUTE_URI_STUDIP + jQuery("#base_url").val()
                            + "/post_files?context=" + context_id
                            + "&context_type=" + context_type
                            + (context_type === "course" ? "&cid=" + context_id : ""),
                        'data': data,
                        'cache': false,
                        'contentType': false,
                        'processData': false,
                        'type': 'POST',
                        'xhr': function () {
                            var xhr = jQuery.ajaxSettings.xhr();
                            //workaround for FF<4 https://github.com/francois2metz/html5-formdata
                            if (data.fake) {
                                xhr.setRequestHeader("Content-Type", "multipart/form-data; boundary=" + data.boundary);
                                xhr.send = xhr.sendAsBinary;
                            }
                            return xhr;
                        },
                        'success': function (json) {
                            if (typeof json.inserts === "object") {
                                jQuery.each(json.inserts, function (index, text) {
                                    jQuery(textarea).val(jQuery(textarea).val() + " " + text);
                                });
                            }
                            if (typeof json.errors === "object") {
                                alert(json.errors.join("\n"));
                            } else if (typeof json.inserts !== "object") {
                                alert("Fehler beim Dateiupload.");
                            }
                            jQuery(textarea).trigger("keydown");
                        },
                        'complete': function () {
                            jQuery(textarea).removeClass("hovered").removeClass("uploading");
                        }
                    });
                }
            });
        });
    },
    /**
     * Every few seconds this function updates all timestamps of all postings
     * on the page, so that they always display the correct relative time since
     * mkdate of that posting.
     */
    updateTimestamps: function () {
        var now_seconds = Math.floor(new Date().getTime() / 1000);
        now_seconds = now_seconds - parseInt(jQuery("#browser_start_time").val(), 10)
            + parseInt(jQuery("#stream_time").val(), 10);
        jQuery("#blubber_threads .posting .time").each(function () {
            var new_text = "";
            var posting_time = parseInt(jQuery(this).attr("data-timestamp"), 10);
            var diff = now_seconds - posting_time;
            if (diff < 86400) {
                if (diff < 2 * 60 * 60) {
                    if (Math.floor(diff / 60) === 0) {
                        new_text = "Vor wenigen Sekunden".toLocaleString();
                    }
                    if (Math.floor(diff / 60) === 1) {
                        new_text = "Vor einer Minute".toLocaleString();
                    }
                    if (Math.floor(diff / 60) > 1) {
                        new_text = _.template("Vor <%= distance %> Minuten".toLocaleString(), {distance: Math.floor(diff / 60)});
                    }
                } else {
                    new_text = _.template("Vor <%= distance %> Stunden".toLocaleString(), {distance: Math.floor(diff / (60 * 60))});
                }
            } else {
                if (Math.floor(diff / 86400) < 8) {
                    if (Math.floor(diff / 86400) === 1) {
                        new_text = "Vor einem Tag".toLocaleString();
                    } else {
                        new_text = _.template("Vor <%= distance %> Tagen".toLocaleString(), {distance: Math.floor(diff / 86400)});
                    }
                } else {
                    date = new Date(posting_time * 1000);
                    new_text = date.getDate() + "." + (date.getMonth() + 1) + "." + date.getFullYear();
                }
            }
            if (jQuery(this).text() !== new_text) {
                jQuery(this).text(new_text);
            }
        });
        if (window.Touch || jQuery.support.touch) {
            //Touch support for devices with no hover-capability
            jQuery("#blubber_threads .posting .time").css({
                "visibility": "visible"
            });
        }
    },
    /**
     * In global stream display the context-selector window
     */
    showContextWindow: function () {
        jQuery("#context_selector").dialog({
            'title': jQuery("#context_selector_title").text(),
            'modal': true,
            'hide': "fade",
            'show': "fade",
            'width': "60%"
        });
    },
    /**
     * In global stream: if context is set, submit posting, else show context-selector.
     */
    prepareSubmitGlobalPosting: function () {
        if (jQuery('#context_type').val()) {
            STUDIP.Blubber.newPosting();
            jQuery("#context_type").val("");
            jQuery("#context_selector table > tbody > tr").removeClass("selected");
            jQuery('#threadwriter .context_selector').removeAttr('class').addClass('select context_selector');
            jQuery("#context_selector").dialog("close");
        } else {
            jQuery("#submit_button").show();
            STUDIP.Blubber.showContextWindow();
        }
    },
    /**
     * Submits a posting or comment by an anonymous user.
     */
    submitAnonymousPosting: function () {
        if (jQuery('#identity_window_textarea_id').val() === "new_posting") {
            STUDIP.Blubber.newPosting();
        } else {
            STUDIP.Blubber.write('#' + jQuery('#identity_window_textarea_id').val());
        }
    },
    /**
     * Adds the current user as a buddy - works for internal and external contacts
     */
    followUser: function () {
        jQuery.ajax({
            'url': STUDIP.ABSOLUTE_URI_STUDIP + jQuery("#base_url").val() + "/follow_user",
            'data': {
                'user_id': jQuery("#context_id").val(),
                'external_contact': jQuery("#extern").val()
            },
            'dataType': "json",
            'success': function (data) {
                if (data.success) {
                    jQuery("#messageboxes").html(data.message);
                    jQuery("#blubber_add_buddy")
                        .closest("tr").fadeOut(function () { jQuery(this).remove(); })
                        .prev("tr").fadeOut(function () { jQuery(this).remove(); });
                }
            }
        });
    },
    update_streams_threadnumber: function () {
        window.setTimeout(function () {
            jQuery("#number_of_threads").load(
                STUDIP.ABSOLUTE_URI_STUDIP + jQuery("#base_url").val() + "/get_streams_threadnumber",
                STUDIP.QuickSearch.formToJSON("#additional_settings")
            );
        }, 50);
    },
    reshareBlubber: function () {
        var thread_id = jQuery(this).closest(".thread").attr("id");
        var panel_open = false;
        if (thread_id) {
            thread_id = thread_id.substr(thread_id.lastIndexOf("_") + 1);
        } else {
            thread_id = jQuery(this).attr("data-thread_id");
            panel_open = true;
        }
        jQuery.ajax({
            'url': STUDIP.ABSOLUTE_URI_STUDIP + jQuery("#base_url").val() + "/reshare/" + thread_id,
            'type': "POST",
            'success': function (output) {
                jQuery("#posting_" + thread_id + " .reshares").addClass("reshared").html(jQuery(output).find(".reshares").html());
                if (panel_open) {
                    jQuery("#blubber_public_panel").dialog("close");
                }
            }
        });
    },
    showPublicPanel: function () {
        var thread_id = jQuery(this).closest(".thread").attr("id");
        thread_id = thread_id.substr(thread_id.lastIndexOf("_") + 1);
        jQuery.ajax({
            'url': STUDIP.ABSOLUTE_URI_STUDIP + jQuery("#base_url").val() + "/public_panel",
            'data': {
                'thread_id': thread_id
            },
            'type': "GET",
            'success': function (html) {
                jQuery('<div id="blubber_public_panel"/>').html(html).dialog({
                    'modal': true,
                    'title': "Sichtbarkeit".toLocaleString(),
                    'width': "80%",
                    'show': "fade",
                    'hide': "fade",
                    'close': function () {
                        jQuery(this).remove();
                    }
                });
            }
        });
        return false;
    },
    showPrivatePanel: function () {
        var thread_id = jQuery(this).closest(".thread").attr("id");
        thread_id = thread_id.substr(thread_id.lastIndexOf("_") + 1);
        jQuery.ajax({
            'url': STUDIP.ABSOLUTE_URI_STUDIP + jQuery("#base_url").val() + "/private_panel",
            'data': {
                'thread_id': thread_id
            },
            'type': "GET",
            'success': function (html) {
                jQuery('<div id="blubber_private_panel"/>').html(html).dialog({
                    'modal': true,
                    'title': "Sichtbarkeit".toLocaleString(),
                    'width': "80%",
                    'show': "fade",
                    'hide': "fade",
                    'close': function () {
                        jQuery(this).remove();
                    }
                });
            }
        });
        return false;
    }
};

jQuery(STUDIP.Blubber.updateTimestamps);

//initialize submit by pressing enter
jQuery("#threadwriter > textarea").live("keydown", function (event) {
    if (event.keyCode === 13 && !event.altKey && !event.ctrlKey && !event.shiftKey) {
        if (jQuery('#user_id').val() !== "nobody") {
            STUDIP.Blubber.newPosting();
        } else {
            jQuery("#identity_window_textarea_id").val(jQuery(this).attr("id"));
            jQuery("#identity_window").dialog({
                modal: true,
                title: jQuery("#identity_window_title").text(),
                width: "50%"
            });
        }
        event.preventDefault();
    }
});
//initialize submit by pressing enter
jQuery("#threadwriter.globalstream textarea").live("keydown", function (event) {
    if (event.keyCode === 13 && !event.altKey && !event.ctrlKey && !event.shiftKey) {
        STUDIP.Blubber.prepareSubmitGlobalPosting();
        event.preventDefault();
    }
});
//initialize submit by pressing enter
jQuery("#blubber_threads textarea.corrector").live("keydown", function (event) {
    if (event.keyCode === 13 && !event.altKey && !event.ctrlKey && !event.shiftKey) {
        STUDIP.Blubber.submitEditedPosting(this);
        event.preventDefault();
    }
});
//initialize submit by pressing enter
jQuery(".writer > textarea").live("keydown", function (event) {
    if (event.keyCode === 13 && !event.altKey && !event.ctrlKey && !event.shiftKey) {
        if (jQuery('#user_id').val() !== "nobody") {
            STUDIP.Blubber.write(this);
        } else {
            jQuery("#identity_window_textarea_id").val(jQuery(this).attr("id"));
            jQuery("#identity_window").dialog({
                modal: true,
                title: jQuery("#identity_window_title").text(),
                width: "50%"
            });
        }
        event.preventDefault();
    }
});
//initialize click-events on "show more" links to show more comments
jQuery("#blubber_threads > li > ul.comments > li.more").live("click", function () {
    var thread_id = jQuery(this).closest("li[id]").attr("id").split("_").pop(),
        li_more = this;
    jQuery(this).wrapInner('<span/>').find('span').showAjaxNotification()
    jQuery.getJSON(STUDIP.ABSOLUTE_URI_STUDIP + jQuery("#base_url").val() + "/more_comments", {
        thread_id: thread_id,
        already_there: jQuery(this).closest("ul").children("li[id]").length
    }, function (json) {
        if (!json.more) {
            jQuery(li_more).remove();
        } else {
            jQuery(li_more).text(json.more);
        }
        if (json.comments) {
            jQuery.each(json.comments, function () {
                STUDIP.Blubber.insertComment(thread_id, this.posting_id, this.mkdate, this.content);
            });
        }
    });
});
jQuery("#blubber_threads a.edit").live("click", STUDIP.Blubber.startEditingComment);
jQuery("#blubber_threads textarea.corrector").live("blur", function () {STUDIP.Blubber.submitEditedPosting(this);});
jQuery("#blubber_threads .reshare_blubber, .blubber_contacts .want_to_share").live("click", STUDIP.Blubber.reshareBlubber);
jQuery("#blubber_threads .thread.public .contextinfo, #blubber_threads .thread.public .open_reshare_context").live("click", STUDIP.Blubber.showPublicPanel);
jQuery("#blubber_threads .thread.private .contextinfo").live("click", STUDIP.Blubber.showPrivatePanel);

//initialize autoresizer, file-dropper and events
jQuery(function () {
    jQuery("#browser_start_time").val(Math.floor(new Date().getTime() / 1000));
    STUDIP.Blubber.makeTextareasAutoresizable();
    jQuery("#new_title").focus(function () {
        jQuery("#new_posting").fadeIn(function () {
            STUDIP.Blubber.makeTextareasAutoresizable();
        });
    });
    jQuery("#threadwriter .context_selector .click").bind("click", STUDIP.Blubber.showContextWindow);

    //for editing custom streams:
    jQuery("#edit_stream select, #edit_stream input").bind("change", STUDIP.Blubber.update_streams_threadnumber);
    jQuery("#edit_stream td .checkicons").bind("click", function () {
        if (jQuery(this).closest("td").is(".selected")) {
            jQuery(this).closest("td").removeClass("selected").find("input[type=checkbox]").removeAttr("checked");
        } else {
            jQuery(this).closest("td").addClass("selected").find("input[type=checkbox]").attr("checked", "checked");
        }
    });
    jQuery("#edit_stream td .label").bind("click", function () {
        if (!jQuery(this).closest("td").is(".selected")) {
            jQuery(this).closest("td").addClass("selected").find("input[type=checkbox]").attr("checked", "checked");
        } else {
            jQuery(this).closest("td").removeClass("selected").find("input[type=checkbox]").removeAttr("checked");
        }
    });
    jQuery("#edit_stream .selector").bind("click", function () {
        if (!jQuery(this).closest("td").is(".selected")) {
            jQuery(this).closest("td").addClass("selected").find("input[type=checkbox]").attr("checked", "checked");
        }
    });
    
});


//Infinity-scroll:
jQuery(window.document).bind('scroll', _.throttle(function (event) {
    if ((jQuery(window).scrollTop() + jQuery(window).height() > jQuery(window.document).height() - 500)
            && (jQuery("#blubber_threads > li.more").length > 0)) {
        //nachladen
        jQuery("#blubber_threads > li.more").removeClass("more").addClass("loading");
        jQuery.ajax({
            url: STUDIP.ABSOLUTE_URI_STUDIP + jQuery("#base_url").val() + "/more_postings",
            data: {
                'context_id': jQuery("#context_id").val(),
                'stream_time': jQuery("#stream_time").val(),
                'stream': jQuery("#stream").val(),
                'offset': jQuery("#loaded").val()
            },
            dataType: "json",
            success: function (response) {
                var more_indicator = jQuery("#blubber_threads > li.loading").detach();
                jQuery("#loaded").val(parseInt(jQuery("#loaded").val(), 10) + 1);
                jQuery.each(response.threads, function (index, thread) {
                    STUDIP.Blubber.insertThread(
                        thread.posting_id,
                        jQuery("#orderby").val() === "discussion_time" ? thread.discussion_time : thread.mkdate,
                        thread.content
                    );
                });
                if (response.more) {
                    jQuery("#blubber_threads").append(more_indicator.addClass("more").removeClass("loading"));
                }
            }
        });
    }
}, 30));

