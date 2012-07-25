/* ------------------------------------------------------------------------
 * browse.php
 * ------------------------------------------------------------------------ */
STUDIP.Browse = {
    selectUser: function (username) {
        window.location.href = STUDIP.URLHelper.getURL("about.php", {"username": username});
    }
};
