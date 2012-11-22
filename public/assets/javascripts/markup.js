/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */

/* ------------------------------------------------------------------------
 * Javascript-spezifisches Markup
 * ------------------------------------------------------------------------ */

STUDIP.Markup = {
    element: function (selector) {
        if (document.getElementById(selector)) {
            var elements = jQuery("#" + selector);
        } else {
            var elements = jQuery(selector);
        }
        jQuery.each(elements, function (index, element) {
            STUDIP.Markup.math_jax(element[0]);
        });
    },
    math_jax: function (element) {
        MathJax.Hub.Queue(["Typeset", MathJax.Hub, element]);
    }
};