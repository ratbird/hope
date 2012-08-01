/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */

STUDIP.OldUpload = {
    upload: false,
    msg_window: null,
    upload_end: function () {
        if (STUDIP.OldUpload.upload) {
            STUDIP.OldUpload.msg_window.close();
        }
        return;
    },
    upload_start: function (form_name) {
        var file_name = jQuery(form_name).find("input[type=file]").val();
        var ende, file_only;
        if (!file_name) {
            alert(jQuery("#upload_select_file_message").text());
            jQuery(form_name).find("input[type=file]").focus();
            return false;
        }

        if (file_name.charAt(file_name.length - 1) === "\"") {
            ende = file_name.length - 1;
        } else {
            ende = file_name.length;
        }
        var ext = file_name.substring(file_name.lastIndexOf(".") + 1, ende).toLowerCase();
        file_only = file_name;
        if (file_name.lastIndexOf("/") > 0) {
            file_only = file_name.substring(file_name.lastIndexOf("/") + 1, ende);
        }
        if (file_name.lastIndexOf("\\") > 0) {
            file_only = file_name.substring(file_name.lastIndexOf("\\") + 1, ende);
        }

        var permission = jQuery.parseJSON(jQuery("#upload_file_types").html());
        if ((permission.allow && jQuery.inArray(ext, permission.types) !== -1) || (!permission.allow && jQuery.inArray(ext, permission.types) === -1)) {
            alert(jQuery("#upload_error_message_wrong_type").text());
            jQuery(form_name).find("input[type=file]").focus();
            return false;
        }

        STUDIP.OldUpload.msg_window = window.open("", "messagewindow", "height=250,width=200,left=20,top=20,scrollbars=no,resizable=no,toolbar=no");
        STUDIP.OldUpload.msg_window.document.write(jQuery("#upload_window_template").text().replace(/\:file_only/, file_only));

        STUDIP.OldUpload.upload = true;
        return true;
    }
};
