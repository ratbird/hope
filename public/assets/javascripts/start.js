INDEX = {},
(function ($, STUDIP) {

    // document ready / page load.
    $(document).ready(function () {
        $("#sort0").sortable({
            connectWith: "ul",
            tolerance:"pointer",
            handle: 'div.ui-widget_head',
            update: function (event, ui) {
                var ids =  $("#sort0").sortable("toArray").toString();
                $.post(STUDIP.ABSOLUTE_URI_STUDIP + 'dispatch.php/start/storeNewOrder' ,
                    {ids: ids});
            },
            start: function (event, ui) {
                $(ui.item).width($('#sort0').width());

           }
        });

        $("ul.start-admin").sortable({
            connectWith: "ul",
            tolerance:"pointer",
            handle: "div.ui-widget_head",
            stop: function (event, ui) {
                var ids =  $("#sort0").sortable("toArray").toString();
                $.post(STUDIP.ABSOLUTE_URI_STUDIP + 'dispatch.php/admin/start/storeSettings' ,
                    {ids: ids, perm: $('#selected_perm option:selected').val()});
            },
            receive: function ( event, ui ) {

                if( $(this).parent().attr('id') != 'choices' ) {
                    $(this).children().attr('style','');
                    $(this).children().width('95%');

                } else {
                    ui.item.remove();
                }
            },
            remove: function(event, ui) {
                if( $(this).parent().attr('id') == 'choices' ) {
                    // increase the instance ...
                    ui.item.clone().prependTo('#sort3');
                }
            }
        });

    });
}(jQuery, STUDIP));
