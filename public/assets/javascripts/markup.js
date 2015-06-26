/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _, MathJax */

/* ------------------------------------------------------------------------
 * Javascript-spezifisches Markup
 * ------------------------------------------------------------------------ */

STUDIP.Markup = {
    element: function (selector) {
        var elements;
        if (document.getElementById(selector)) {
            elements = jQuery("#" + selector);
        } else {
            elements = jQuery(selector);
        }
        elements.each(function (index, element) {
            jQuery.each(STUDIP.Markup.callbacks, function (index, func) {
                if ((index !== "element") || typeof func === "function") {
                    func(element);
                }
            });
        });
    },
    callbacks: {
        math_jax: function (element) {
            MathJax.Hub.Queue(["Typeset", MathJax.Hub, element]);
        },
        codehighlight: function (element) {
            jQuery(document).find("pre, code").each(function (index, block) {
                hljs.highlightBlock(block);
            });
        }
    }
};


jQuery(function () {
    STUDIP.Markup.callbacks.codehighlight(document);
});
