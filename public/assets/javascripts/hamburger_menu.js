/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */

jQuery(function () {
    jQuery("#hamburgerNavigation input[type=checkbox]").on("change", function () {
        if (jQuery(this).is(":checked")) {
            jQuery(this).closest("li").siblings(":not(#hamburgerNavigation > li)").slideUp();
            if (jQuery(this).closest("li").is("#hamburgerNavigation > li")) {
                jQuery(this).closest("li").siblings().find("input[type=checkbox]:checked").removeAttr("checked");
            }
        } else {
            jQuery(this).closest("li").siblings().slideDown();
        }
    }).trigger("change");

    if (jQuery('#layout-sidebar').length == 0) {
        jQuery('#sidebar-menu').hide();
    }

    jQuery('.hamburger').click(function () {
        if (jQuery('#layout-sidebar').hasClass('visible-sidebar')) {
            jQuery('#layout-sidebar').removeClass('visible-sidebar');
        }
    });

    jQuery('#sidebar-menu').click(function () {
        jQuery('#layout-sidebar').toggleClass('visible-sidebar');
    });
});