/*jslint nomen: true, browser: true, sloppy: true */
/*global STUDIP, jQuery, _ */

(function ($) {
    if (!window.matchMedia) {
        return;
    }

    var media_query = window.matchMedia('(max-width: 768px)');

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
                li      = $('<li>'),
                item    = $('<div class="navigation_item">').appendTo(li),
                title   = $('<div class="nav_title">').appendTo(item),
                label   = nav.children
                            ? $('<label>').attr('for', subpath).html(nav.title).appendTo(title)
                            : $('<a>').text(nav.title).attr('href', nav.url).appendTo(title);

            if (nav.image) {
                $('<img class="icon">').attr('src', STUDIP.ASSETS_URL + nav.image).prependTo(label);
            }

            $('<a class="nav_link">').attr('href', nav.url).appendTo(item);

            if (nav.children) {
                $('<input type="checkbox">').attr('id', subpath).prop('checked', nav.active).appendTo(li);
                li.append(buildMenu(nav.children, subpath));
            }

            list.append(li);
        });

        return list;
    }

    // Adds the responsive menu to the dom
    function addMenu() {
        var wrapper = $('<div id="responsive-navigation">'),
            menu    = buildMenu(STUDIP.Navigation, 'resp', 'hamburgerNavigation');

        $('<label for="hamburgerChecker" class="hamburger">').appendTo(wrapper);
        $('<input type="checkbox" id="hamburgerChecker">').appendTo(wrapper);
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
                $('#hamburgerChecker').prop('checked', false);
                $('#layout-sidebar').toggleClass('visible-sidebar');
            }).appendTo('#barBottomright ul');

            $('#hamburgerChecker').on('change', function () {
                $('#layout-sidebar').removeClass('visible-sidebar');
            });
        }

        $('#hamburgerNavigation :checkbox').on('change', function () {
            var li = $(this).closest('li');
            if ($(this).is(':checked')) {
                li.siblings(':not(#hamburgerNavigation > li)').slideUp();
                if (li.is('#hamburgerNavigation > li')) {
                    li.siblings().find(':checkbox:checked').prop('checked', false);
                }
            } else {
                $(this).closest('li').siblings().slideDown();
            }
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