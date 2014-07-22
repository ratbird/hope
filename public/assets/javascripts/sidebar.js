jQuery(document).ready(function ($) {
    var $sidebar    = $('#layout-sidebar .sidebar'),
        container   = $('#layout_container'),
        fold_top    = $('#barBottomContainer').outerHeight(true),
        handler,
        last_scroll = null,
        offset = 0,
        boundaries = {},
        size;

    STUDIP.Sidebar = STUDIP.Sidebar || {};

    if ($sidebar.length === 0) {
        STUDIP.Sidebar.scroll = function () {};
        return;
    }

    var update = function (offset) {
        $sidebar.css('margin-top', offset + 'px');
    };
    if (Modernizr.csstransforms) {
        update = function (offset) {
            $sidebar.css('transform', 'translateY(' + offset + 'px)');
        };
    }

    size = $sidebar.find('.sidebar').outerHeight(true);
    $sidebar.find('img').on('load', function () {
        size = $sidebar.outerHeight(true);
        handler(last_scroll);
    })

    boundaries.top = $sidebar.offset().top - fold_top;

    handler = function (scrolltop) {
        // We need to keep this dynamic in order to cope with endless scrolls
        boundaries.bottom = container.height() - size;

        var move = false,
            direction = scrolltop > last_scroll ? 'down' : 'up',
            boundary = boundaries.bottom;

        if (direction === 'up' && (scrolltop > boundaries.top || offset > 0)) {
            boundary = Math.min(offset, boundary);
            move = true;
        } else if (direction === 'down') {
            boundary = Math.max(offset, Math.min(boundary, scrolltop - boundaries.top + $(window).height() - size - fold_top));
            move = true;
        }

        if (move !== false) {
            offset = Math.max(0, Math.min(scrolltop - boundaries.top, boundary));
            update(offset);
        }
        last_scroll = scrolltop;
    };

    // Expose enable/disable functionality
    STUDIP.Sidebar.scroll = function (state) {
        if (arguments.length === 0) {
            state = true;
        }

        if (state) {
            STUDIP.Scroll.addHandler('sidebar', handler);
        } else {
            STUDIP.Scroll.removeHandler('sidebar');
            update(0);
        }
    };

    // Enable the scrolling sidebar
    STUDIP.Sidebar.scroll();
});