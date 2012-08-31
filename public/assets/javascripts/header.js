(function ($, document) {

    var fold = null,
        was_below_the_fold = false,
        scroll = _.throttle(function () {
            var is_below_the_fold = $(document).scrollTop() > fold;
            if (is_below_the_fold !== was_below_the_fold) {
                $('body').toggleClass('fixed', is_below_the_fold);
                was_below_the_fold = is_below_the_fold;
            }
        }, 30);

    STUDIP.HeaderMagic = {
        enable: function () {
            $(document).bind('scroll.studip', scroll).trigger('scroll.studip');
        },
        disable : function () {
            $(document).unbind('scroll.studip');
            $('body').removeClass('fixed');
        }
    };

    $(document).ready(function () {
        fold = $('#barBottomContainer').offset().top;
        STUDIP.HeaderMagic.enable();
    });

}(jQuery, window.document));
