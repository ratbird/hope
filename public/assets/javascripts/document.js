jQuery(function ($) {
    function helper_creator () {
        var width = $(this).closest('table').width(),
            helper = $('<table class="default nohover">').width(width);
        $(this).closest('table').find('colgroup').clone().appendTo(helper);
        $(this).clone().addClass('document-draggable-helper').appendTo(helper);
        helper.find('td:first-child,td:last-child').empty();
        return helper;
    };
    
    $('[data-file]').draggable({
        axis: 'y',
        containment: 'parent',
        helper: helper_creator,
        start: function () {
            $(this).closest('table').addClass('dragging');
        },
        stop: function () {
            $(this).closest('table').removeClass('dragging');
        }
    });
    
    $('[data-folder]').droppable({
        over: function (event, ui) {
            $(this).addClass('dropping');
        },
        out: function (event, ui) {
            $(this).removeClass('dropping');
        },
        drop: function (event, ui) {
            var file_id   = $(ui.helper).find('[data-file]').data().file,
                folder_id = $(this).data().folder,
                url       = STUDIP.URLHelper.getURL('dispatch.php/document/files/move/' + file_id);
            
            $.post(url, {
                folder_id: folder_id
            }, function () {
                location.reload();
            });
            $(this).removeClass('dropping');
        }
    });
});
