jQuery(document).ready(function ($) {
    var sidebar     = $('#layout-sidebar'),
        container   = $('#layout_container'),
        fold_top    = $('#barBottomContainer').outerHeight(true),
        handler,
        last_scroll = null,
        pos, boundaries = {},
        size;
    
    if (sidebar.length === 0) {
        return;
    }

    pos  = sidebar.offset();
    size = sidebar.outerHeight(true);
    boundaries.top = pos.top - fold_top;

    handler = _.throttle(function () {
        var scroll_y       = $(document).scrollTop(),
            distance       = Math.abs(last_scroll - scroll_y),
            size_y         = $(window).height(),
            current        = sidebar.offset(),
            leaving_top    = scroll_y > Math.max(boundaries.top, current.top - fold_top),
            margin = false;
        if (last_scroll > scroll_y && (scroll_y > boundaries.top || pos.top != current.top)) {
            // Scrolling up
            margin = Math.max(0, scroll_y - boundaries.top);
        } else if (last_scroll < scroll_y) {
            margin = Math.max(0, scroll_y - boundaries.top);
        }
        if (margin !== false) {
            sidebar.css('margin-top', margin);
        }
        last_scroll = scroll_y;
    }, 10);
    $(document).on('scroll.studip', handler);
});