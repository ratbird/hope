/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */

(function ($, document) {

    var fold,
        $header,
        $wrapper,
        was_below_the_fold = false,
        scroll = function (scrolltop) {
            var is_below_the_fold = scrolltop > fold;
            if (is_below_the_fold !== was_below_the_fold) {
                $header.toggleClass('fixed', is_below_the_fold);
                was_below_the_fold = is_below_the_fold;
            }
        };

    STUDIP.HeaderMagic = {
        enable: function () {
            $header = $('#barBottomContainer');
            $wrapper = $header.wrap('<div class="sticky-wrapper" />').parent().height($header.outerHeight(true));
            fold = $header.offset().top;
            STUDIP.Scroll.addHandler('header', scroll);
        },
        disable : function () {
            STUDIP.Scroll.removeHandler('header');
            $header.removeClass('fixed');
        }
    };

    $(document).ready(function () {
        // Test if the header is actually present
        if ($('#barBottomContainer').length > 0) {
            STUDIP.HeaderMagic.enable();
        }
    });

}(jQuery, window.document));
