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

STUDIP.HeaderIcons = {
    canvasRender: function () {
        var img = this;
        jQuery(img).closest("a").find("canvas.headericon").each(function (index, canvas) {
            jQuery(canvas).attr({
                'width': "42px",
                'height': "32px"
            });

            var icon   = jQuery(img)[0];
            var number = parseInt(jQuery(img).data("badge"), 10);
            canvas = jQuery(canvas)[0];
            var ctx = canvas.getContext("2d");
            if (window.devicePixelRatio) {
                var hidefCanvasWidth = parseInt(jQuery(canvas).attr('width'), 10);
                var hidefCanvasHeight = parseInt(jQuery(canvas).attr('height'), 10);
                var hidefCanvasCssWidth = hidefCanvasWidth;
                var hidefCanvasCssHeight = hidefCanvasHeight;
                jQuery(canvas).attr('width', hidefCanvasWidth * window.devicePixelRatio);
                jQuery(canvas).attr('height', hidefCanvasHeight * window.devicePixelRatio);
                jQuery(canvas).css('width', hidefCanvasCssWidth);
                jQuery(canvas).css('height', hidefCanvasCssHeight);
                ctx.scale(window.devicePixelRatio, window.devicePixelRatio);
            }

            ctx.clearRect(0,0,42,32);
            var image = new Image();
            image.src = icon.src;
            if (image.width === 128) {
                //old 32x128 header images
                ctx.drawImage(icon, 0, 5, 106, 28, 5, 5,  28 * (image.width / image.height), 28);
            } else {
                ctx.drawImage(icon, 7, 2,  28 * (image.width / image.height), 28);
            }
            var filterCanvas = function (filter) {
                if (canvas.width > 0 && canvas.height > 0) {
                    var imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                    filter(imageData);
                    ctx.clearRect(0,0,42,32);
                    ctx.putImageData(imageData, 0, 0);
                }
            }
            var brightness = function(pixels, args) {
                var d = pixels.data;
                for (var i = 0; i < d.length; i += 4) {
                    d[i] = 255;     // red
                    d[i + 1] = 255; // green
                    d[i + 2] = 255; // blue
                }
                return pixels;
            };
            if (jQuery(canvas).is(".highlighted")) {
                filterCanvas(brightness);
            }


            if (number > 0) {
                var x = 34;
                ctx.globalCompositeOperation = "destination-out";
                ctx.beginPath();
                ctx.arc(x, 8, 11, 0, 2*Math.PI);
                ctx.fill();

                ctx.globalCompositeOperation = "source-over";
                ctx.beginPath();
                ctx.arc(x, 8, 8, 0, 2*Math.PI);
                ctx.fillStyle="#d60000";
                ctx.fill();

                ctx.font = "10px " + jQuery("body").css("font-family");
                ctx.textAlign = "center";
                ctx.fillStyle="white";
                ctx.fillText("" + number ,x, 11);
            }
        });
        jQuery(img).closest("a").addClass("canvasready");
    },
    render: function (selector, hovered) {
        jQuery.each(jQuery(selector), function (index, img) {
            if (img.complete) {
                STUDIP.HeaderIcons.canvasRender.call(img);
            } else {
                jQuery(img).on('load', STUDIP.HeaderIcons.canvasRender);
            }
        });
    }
};

jQuery(function () {
    STUDIP.HeaderIcons.render("img.headericon");
    jQuery("img.headericon").on("badgechange", function () { STUDIP.HeaderIcons.render(this); });
});
