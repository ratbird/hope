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
        var img = jQuery("<img>")
                .css({width:"56px", height:"56px"})
                .attr({"src": this.src, 'data-badge': jQuery(this).attr("data-badge")});
        console.log(img);
        var canvas_normal = jQuery(this).parent().find(".normal");
        var canvas_highlighted = jQuery(this).parent().find(".highlighted");
        var drawCanvas = function (img, canvas, highlight) {
            var icon   = jQuery(img)[0];
            var number = parseInt(jQuery(img).data("badge"), 10);
            canvas = jQuery(canvas)[0];
            var ctx = canvas.getContext("2d");

            ctx.clearRect(0,0,84,64);
            var image = new Image();
            image.src = icon.src;
            if (image.width === 128 && image.height === 32) {
                //old 32x128 header images
                ctx.drawImage(icon, 0, 5, 106, 28, 5, 5,  28 * (image.width / image.height), 28);
            } else {
                if (!image.width) {
                    image.width = image.height = 56;
                }
                ctx.drawImage(image, 14, 8,  56 * (image.width / image.height), 56);
            }
            var filterCanvas = function (filter) {
                if (canvas.width > 0 && canvas.height > 0) {
                    var imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                    filter(imageData);
                    ctx.clearRect(0,0,84,64);
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
            if (highlight) {
                filterCanvas(brightness);
            }

            if (number > 0) {
                var x = 68;
                ctx.globalCompositeOperation = "destination-out";
                ctx.beginPath();
                ctx.arc(x, 16, 22, 0, 2*Math.PI);
                ctx.fill();

                ctx.globalCompositeOperation = "source-over";
                ctx.beginPath();
                ctx.arc(x, 16, 16, 0, 2*Math.PI);
                ctx.fillStyle="#d60000";
                ctx.fill();

                ctx.font = "20px " + jQuery("body").css("font-family");
                ctx.textAlign = "center";
                ctx.fillStyle="white";
                ctx.fillText("" + number ,x, 22);
            }
        };
        drawCanvas(img, canvas_normal, false);
        drawCanvas(img, canvas_highlighted, true);
        jQuery(this).closest("a").addClass("canvasready");
    },
    render: function (selector) {
        jQuery.each(jQuery(selector), function (index, img) {
            if (img.complete) {
                STUDIP.HeaderIcons.canvasRender.call(img);
            } else {
                jQuery(img).bind('load', STUDIP.HeaderIcons.canvasRender);
            }
        });
    }
};

jQuery(function () {
    window.setTimeout(function () {
        STUDIP.HeaderIcons.render("img.headericon.original");
        jQuery("img.headericon.original").on("badgechange", function () { STUDIP.HeaderIcons.render(this); });
    }, 300);
});