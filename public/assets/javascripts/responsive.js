/*jslint nomen: true, browser: true, sloppy: true */
/*global STUDIP, jQuery, _ */

(function ($) {
    if (!window.matchMedia) {
        return;
    }

    var media_query = window.matchMedia('(max-width: 800px)');

    // Builds a dom element from a navigation object
    function buildMenu(navigation, path, id) {
        var list = $('<ul>');

        if (id) {
            list.attr('id', id);
        }

        // TODO: Templating?
        _.forEach(navigation, function (nav, node) {
            nav.url = STUDIP.URLHelper.getURL(nav.url);
            var subpath = path + '_' + node,
                li      = $('<li class="navigation-item">'),
                title   = $('<div class="nav-title">').appendTo(li),
                link    = $('<a>').text(nav.title).attr('href', nav.url).appendTo(title),
                icon    = nav.icon || false;

            if (icon) {
                if (!icon.match(/^https?:\/\//)) {
                    icon = STUDIP.ASSETS_URL + icon;
                }
                $('<img class="icon">').attr('src', icon).prependTo(link);
            }

            if (nav.children) {
                $('<input type="checkbox">').attr('id', subpath).prop('checked', nav.active).appendTo(li);
                $('<label class="nav-label">').attr('for', subpath).text(' ').appendTo(li);
                li.append(buildMenu(nav.children, subpath));
            }

            list.append(li);
        });

        return list;
    }

    // Adds the responsive menu to the dom
    function addMenu() {
        var wrapper = $('<div id="responsive-container">'),
            menu    = buildMenu(STUDIP.Navigation, 'resp', 'responsive-navigation');

        $('<label for="responsive-toggle">').appendTo(wrapper);
        $('<input type="checkbox" id="responsive-toggle">').appendTo(wrapper);
        wrapper.append(menu);

        wrapper.appendTo('#barBottomLeft');
    }

    // Responsifies the layout. Builds the responsive menu from existing
    // STUDIP.Navigation object
    function responsify() {
        media_query.removeListener(responsify);
        STUDIP.URLHelper.base_url = STUDIP.ABSOLUTE_URI_STUDIP;

        addMenu();

        if ($('#layout-sidebar > section > :not(#sidebar-navigation,.sidebar-image)').length > 0) {
            $('<li id="sidebar-menu">').on('click', function () {
                $('#responsive-toggle').prop('checked', false);
                $('#layout-sidebar').toggleClass('visible-sidebar');
            }).appendTo('#barBottomright ul');

            $('#responsive-toggle').on('change', function () {
                $('#layout-sidebar').removeClass('visible-sidebar');
            });
        }

        $('#responsive-navigation :checkbox').on('change', function () {
            var li = $(this).closest('li');
            if ($(this).is(':checked')) {
                li.siblings(':not(#responsive-navigation > li)').slideUp();
                if (li.is('#responsive-navigation > li')) {
                    li.siblings().find(':checkbox:checked').prop('checked', false);
                }
            } else {
                $(this).closest('li').siblings().slideDown();
            }

            // Force redraw of submenu (at least ios safari/chrome would
            // not show it without a forced redraw)
            $(this).siblings('ul').hide(0, function () {
                $(this).show();
            })
        }).trigger('change');
    }

    // Build responsife menu on domready or resize
    $(document).ready(function () {
        if (media_query.matches) {
            responsify();
        } else {
            media_query.addListener(responsify);
        }
    });
}(jQuery));