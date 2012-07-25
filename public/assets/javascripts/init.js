/* ------------------------------------------------------------------------
 * Remove "no-js" class from <html> element, if it exists:
 * Add the new classes to the <html> element.
 *
 * Copied from https://github.com/Modernizr/Modernizr/blob/master/modernizr.js
 * ------------------------------------------------------------------------ */
(function (elem) {
    elem.className = elem.className.replace(/\bno-js\b/, '') + ' js';
}(document.documentElement));


/* ------------------------------------------------------------------------
 * jQuery plugin "metadata" configuration
 * ------------------------------------------------------------------------ */
if ("metadata" in jQuery) {
    jQuery.metadata.setType("html5");
}

/* ------------------------------------------------------------------------
 * the global STUDIP namespace
 * ------------------------------------------------------------------------ */
var STUDIP = STUDIP || {};
