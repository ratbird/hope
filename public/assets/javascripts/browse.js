/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */

/* ------------------------------------------------------------------------
 * browse.php
 * ------------------------------------------------------------------------ */
STUDIP.Browse = {
    selectUser: function (username) {
        window.location.href = STUDIP.URLHelper.getURL("about.php", {"username": username});
    }
};
