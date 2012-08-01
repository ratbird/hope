/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */

/* ------------------------------------------------------------------------
 * Multiselect
 * ------------------------------------------------------------------------ */

/**
 * Turns a select-box into an easy to use multiple select-box
 */
STUDIP.MultiSelect = {
    /**
     * @param id string:
     */
    create: function (id, itemName) {
        if (!jQuery(id).attr('multiple')) {
            jQuery(id).attr('multiple', 'multiple').css('height', '120px');
        }
        jQuery(id).multiselect({
            'sortable': false,
            'draggable': true,
            'dividerLocation': 0.5,
            'itemName': itemName
        });
    }
};
jQuery(function () {
    jQuery.extend(jQuery.ui.multiselect, {
        locale: {
            addAll: "Alle hinzufügen".toLocaleString(),
            removeAll: "Alle entfernen".toLocaleString(),
            itemsCount: "ausgewählt".toLocaleString()
        }
    });
});
