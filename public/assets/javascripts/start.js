/*jslint browser: true, sloppy: true, unparam: true */
(function ($, STUDIP) {
    STUDIP.startpage = {
        /*
         THIS CODE ADDS DYNAMIC COLUMNING. WILL BE USED IN 3.2

         init: function() {
         $(".start-widgetcontainer ul").sortable({
         connectWith: "ul",
         start: function(event, ui) {
         $(this).parent().find('ul').addClass('ui-sortable').addClass('move');
         },
         stop: function(event, ui) {
         $.get(STUDIP.ABSOLUTE_URI_STUDIP + 'dispatch.php/start/storeNewOrder',
         {
         widget: $(ui.item).attr('id'),
         position: $(ui.item).index(),
         column: $(ui.item).parent().index()
         }
         );
         $(this).parent().find('ul').removeClass('move empty');
         $(this).parent().find('ul:empty').remove();
         $(this).parent().append($('<ul>').addClass('empty'));
         STUDIP.startpage.init();
         }

         });
         }*/


        init: function () {
            $('.start-widgetcontainer .portal-widget-list').sortable({
                handle: '.widget-header',
                connectWith: 'ul.portal-widget-list',
                start: function () {
                    $(this).closest('.start-widgetcontainer').find('.portal-widget-list').addClass('ui-sortable move');
                },
                stop: function (event, ui) {
                    $.get(STUDIP.ABSOLUTE_URI_STUDIP + 'dispatch.php/start/storeNewOrder', {
                        widget: $(ui.item).attr('id'),
                        position: $(ui.item).index(),
                        column: $(ui.item).parent().index()
                    });
                    $(this).closest('.start-widgetcontainer').find('.portal-widget-list').removeClass('move');
                }

            });
        },

        init_edit: function (perm) {
            $('.edit-widgetcontainer .portal-widget-list').sortable({
                handle: '.widget-header',
                connectWith: '.edit-widgetcontainer .portal-widget-list',
                start: function () {
                    $(this).closest('.edit-widgetcontainer').find('.portal-widget-list').addClass('ui-sortable move');
                },
                stop: function () {
                    // store the whole widget constellation
                    var widgets = {
                        left: {},
                        right: {}
                    };

                    $('.edit-widgetcontainer .start-widgetcontainer .portal-widget-list:first-child > li').each(function () {
                        widgets.left[$(this).attr('id')] = $(this).index();
                    });

                    $('.edit-widgetcontainer .start-widgetcontainer .portal-widget-list:last-child > li').each(function () {
                        widgets.right[$(this).attr('id')] = $(this).index();
                    });

                    $.post(STUDIP.ABSOLUTE_URI_STUDIP + 'dispatch.php/start/update_defaults/' +  perm, widgets);

                    $(this).closest('.edit-widgetcontainer').find('.portal-widget-list').removeClass('move');
                }

            });
        }
    };

    $(document).ready(STUDIP.startpage.init);

    // Add handler for "read all" on news widget
    $(document).on('click', '#start-index a[href*="newswidget/read_all"]', function (event) {
        var icon   = $(this),
            url    = icon.attr('href'),
            widget = icon.closest('.studip-widget');

        icon.attr('disabled', true).addClass('ajaxing');

        $.getJSON(url).then(function (response) {
            if (response) {
                $('article.new', widget).removeClass('new');
                $('.news-comments-unread', widget).removeClass('news-comments-unread').removeAttr('title');
                $('#nav_start [data-badge]').attr('data-badge', 0).trigger('badgechange');
                icon.remove();
            }
        });
        
        event.preventDefault();
    })
}(jQuery, STUDIP));
