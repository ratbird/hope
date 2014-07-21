$(window).load(function () {
    var sidebar     = $('#layout-sidebar'),
        container   = $('#layout_container'),
        fold_top    = $('#barBottomContainer').outerHeight(true),
        handler,
        last_scroll = null,
        offset = 0,
        boundaries = {},
        size,
        prefixes = _.filter(Modernizr._prefixes, function (prefix) { return !!prefix; });

    if (sidebar.length === 0) {
        return;
    }

    var update = function (offset) {
        STUDIP.CSS.removeRule('#layout-sidebar');
        STUDIP.CSS.addRule('#layout-sidebar', {'margin-top': offset + 'px'});
    };
    if (Modernizr.csstransforms) {
        update = function (offset) {
            STUDIP.CSS.removeRule('#layout-sidebar');
            STUDIP.CSS.addRule('#layout-sidebar .sidebar', {
                transform: 'translateY(' + offset + 'px)'
            }, prefixes);
        };
    }

    size = sidebar.find('section').outerHeight(true);
    boundaries.top = sidebar.offset().top - fold_top;
    boundaries.bottom = container.height() - size;

    handler = function (scroll_y) {
        var distance       = Math.abs(last_scroll - scroll_y),
            size_y         = $(window).height(),
            leaving_top    = scroll_y > Math.max(boundaries.top, offset + distance - fold_top),
            move = false;
            
        if (last_scroll > scroll_y && (scroll_y > boundaries.top || offset > 0)) {
            // Scrolling up
            move = true;
        } else if (last_scroll < scroll_y) {
            move = true;
        }
        if (move !== false) {
            offset = Math.max(0, Math.min(scroll_y - boundaries.top, boundaries.bottom));
            update(offset);
        }
        last_scroll = scroll_y;
    };

    STUDIP.Sidebar = STUDIP.Sidebar || {};
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

    STUDIP.Sidebar.scroll();
});