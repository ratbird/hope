STUDIP.HeaderMagic = {
    top: null,
    headerHeight: null,
    lastScrollEvent: Date.now(),

    scroll: function () {
        // throttle scroll handler
        var now = Date.now();
        if (now - STUDIP.HeaderMagic.lastScrollEvent < 30) {
            return;
        }
        STUDIP.HeaderMagic.lastScrollEvent = now;

        if (STUDIP.HeaderMagic.top === null) {
            STUDIP.HeaderMagic.top = jQuery("#barBottomContainer").offset().top;
        }
        if (STUDIP.HeaderMagic.headerHeight === null) {
            STUDIP.HeaderMagic.headerHeight = jQuery("#header").height();
        }
        if (STUDIP.HeaderMagic.top < jQuery(window.document).scrollTop()) {
            //static
            jQuery("#barBottomContainer").addClass("fixed");
            jQuery("#header").css("height", 
                (jQuery("#barBottomContainer").height() 
                    + parseInt(jQuery("#barBottomContainer").css("border-top-width"), 10)
                    + parseInt(jQuery("#barBottomContainer").css("border-bottom-width"), 10)
                    + STUDIP.HeaderMagic.headerHeight
                ) + "px");
        } else {
            jQuery("#barBottomContainer").removeClass("fixed");
            jQuery("#header").css("height", STUDIP.HeaderMagic.headerHeight + "px");
        }
    }
}

// obere Leiste
jQuery(function () {
    jQuery(window.document).bind("scroll", STUDIP.HeaderMagic.scroll).trigger('scroll');
});