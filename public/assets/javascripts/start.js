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


    init: function() {
        $(".start-widgetcontainer ul").sortable({
            connectWith: "ul.widget-list",
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
                $(this).parent().find('ul').removeClass('move');
            }

        });
    }
};
$(document).ready(function() {
    STUDIP.startpage.init();
});