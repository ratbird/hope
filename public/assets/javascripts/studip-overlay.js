/*jslint browser: true, devel: true, nomen: true, regexp: true, unparam: true, sloppy: true, todo: true */
/*global jQuery, STUDIP */
(function ($, STUDIP) {

    if (jQuery.ui === undefined) {
        throw 'Overlays require jQuery UI';
    }

    var element = null,
        overlay = {};

    overlay.show = function (ajax, containment) {
        if (element === null) {
            containment = containment || 'body';

            element = $('<div class="ui-front modal-overlay">');
            if (ajax) {
                element.addClass('modal-overlay-ajax');
                if (ajax === 'dark') {
                    element.addClass('modal-overlay-dark');
                }
            }
            if (containment !== 'body') {
                element.addClass('modal-overlay-local');
            }
            element.appendTo(containment);
        }
    };
    overlay.hide = function () {
        if (element !== null) {
            element.remove();
            element = null;
        }
    };

    STUDIP.Overlay = overlay;

}(jQuery, STUDIP));