/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */

STUDIP.Messaging = {
    addToAdressees: function (username, name) {
        var form = jQuery("form[name=upload_form]");
        jQuery('<input type="hidden" name="add_receiver[]"/>').val(username).appendTo(form);
        jQuery('<input type="hidden" name="add_receiver_button_x" value="1"/>').appendTo(form);
        form.submit();

        return;
    }
};
