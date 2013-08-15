/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */

STUDIP.Messaging = {
    addToAdressees: function (username, name) {
        if (!jQuery("select#del_receiver").length) {
            var form = jQuery("form[name=upload_form]");
            jQuery('<input type="hidden" name="add_receiver[]"/>').val(username).appendTo(form);
            jQuery('<input type="hidden" name="add_receiver_button_x" value="1"/>').appendTo(form);
            form.submit();

            return;
        }
        if (!jQuery('select#del_receiver [value="' + username + '"]').length) {
            jQuery("select#del_receiver")
                .append(jQuery('<option value="' + username + '">' + name + '</option>'))
                .attr("size", jQuery(this).attr("size") + 1);
            jQuery.ajax({
                url: "?",
                data: {
                    "add_receiver_button_x": true,
                    "add_receiver": [username]
                }
            });
            window.setTimeout(function () {
                jQuery('input[name=adressee_parameter]').val('');
            }, 10);
        }
    }
};
