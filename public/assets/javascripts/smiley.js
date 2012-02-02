// created from coffeescript located at https://gist.github.com/1719669
jQuery(function($) {
    $('.smiley-select select').change(function() {
        $(this).closest('form').submit();
    });

    $('.smiley-toggle').on('click', function(event) {
        var element;
        element = $(this);
        element.attr({
            'disabled': true
        });
        element.addClass('ajax');
        $.getJSON(element.attr('href'), function(json) {
            $('#layout_container .messagebox').remove();
            $('#layout_container').prepend(json.message);

            element.toggleClass('favorite', json.state);
            element.removeClass('ajax');
            element.attr({
                'disabled': false
            });
        });
        event.preventDefault();
    });

    $('a[href*="admin/smileys/edit"], a[href*="admin/smileys/upload"]').on('click', function(event) {
        var href;
        href = $(this).attr('href');
        $('<div class="smiley-modal"/>').load(href, function() {
            var options;
            $(this).hide().appendTo('body');
            options = {
                modal: true,
                width: $(this).outerWidth() + 50,
                height: $(this).outerHeight() + 50,
                title: $('thead', this).remove().text(),
                close: function() {
                    $(this).remove();
                }
            };
            $(this).dialog(options);
        });
        event.preventDefault();
    });

    $('.smiley-modal .button.cancel').on('click', function(event) {
        $(this).closest('.smiley-modal').dialog('close');
        event.preventDefault();
    });
});