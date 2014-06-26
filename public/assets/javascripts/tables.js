/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */

jQuery(function () {
    if (typeof MutationObserver !== "undefined") {
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === "class") {
                    if (jQuery(mutation.target).attr("class").indexOf("open") !== -1) {
                        jQuery(mutation.target).next().find("td").slideDown().find(".detailscontainer").hide().slideDown();
                    } else {
                        jQuery(mutation.target).next().show().find("td").slideUp().find(".detailscontainer").slideUp();;
                    }
                }

            });
        });
        jQuery("table.withdetails > tbody > tr:not(.details)").each(function (index, element) {
            observer.observe(element, { attributes: true });
        });
    }
});