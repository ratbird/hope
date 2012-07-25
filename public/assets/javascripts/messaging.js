STUDIP.Messaging = {
    addToAdressees: function (username, name) {
        if (!jQuery("select#del_receiver").length) {
            jQuery("form[name=upload_form]")
                .attr("action", STUDIP.URLHelper.getURL("?", {
                    "add_receiver[]": username,
                    "add_receiver_button_x": true
                }))
            [0].submit();
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
