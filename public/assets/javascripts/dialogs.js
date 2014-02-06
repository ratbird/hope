/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */

/* ------------------------------------------------------------------------
 * Standard dialogs for confirmation or messages
 * ------------------------------------------------------------------------ */

STUDIP.Dialogs = {

    showConfirmDialog: function (question, confirm) {
        // compile template
        var getTemplate = _.memoize(function (name) {
            return _.template(jQuery("#" + name).html());
        });

        var confirmDialog = getTemplate('confirm_dialog');
        $('body').append(confirmDialog({
            question: question,
            confirm: confirm
        }));
        
        return false;
    },

    closeConfirmDialog: function () {
        $('div.modaloverlay').remove();
    }

};