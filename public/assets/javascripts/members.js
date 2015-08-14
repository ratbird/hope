/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */

jQuery(document).on('click', 'a[rel~="comment_dialog"]', function (event) {
    var href      = jQuery(this).attr('href'),
        container = jQuery('<div/>');

    // Load response into a helper container, open dialog after loading
    // has finished.
    container.load(href, function (response, status, xhr) {
        jQuery(this).dialog({
            title:      xhr.getResponseHeader('X-Title') || '',
            width:      '40em',
            modal:      true,
            resizable:  false
        });
    });

    event.preventDefault();
});
