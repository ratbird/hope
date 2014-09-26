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

(function ($) {

    // Render a version of the icon with a punched out area for the badge
    function renderCanvas(canvas, width, height) {
        var target       = canvas.getContext('2d'),
            aspect_ratio = height ? width / height : 1;

        target.clearRect(0, 0, canvas.width, canvas.height);

        if (width === 128 && height === 32) {
            target.drawImage(this, 0, 5, 106, 28, 5, 5, 28 * aspect_ratio, 28);
        } else {
            target.drawImage(this, 14, 8, 56 * aspect_ratio, 56);
        }

        target.globalCompositeOperation = 'destination-out';
        target.beginPath();
        target.arc(canvas.width - 16, 16, 22, 0, 2 * Math.PI);
        target.fill();

        $(canvas).closest('a').addClass('canvasready');
    }

    $(document).ready(function () {
        $('html.canvas img.headericon.original').each(function () {
            var canvas = $('<canvas width="84" height="64">').addClass('headericon punch').insertAfter(this),
                width  = $(this).width(),
                height = $(this).height(),
                img    = new Image();

            // The callback is bound back to the original image because
            // context.drawImage requires the image to be in the dom or
            // the result is quirky.
            // The onload is neccessary due to the various browsers handling
            // the load event differently.
            img.onload = renderCanvas.bind(this, canvas[0], width, height);
            img.src    = this.src;
        });
    });

}(jQuery));
