jQuery(function($) {
    $('.smiley-select select').change(function() {
        return $(this).closest('form').submit();
    });
    $('.smiley-toggle').live('click', function(event) {
        var element;
        element = $(this);
        element.attr({
            'disabled': true
        });
        element.addClass('ajax');
        $.getJSON(element.attr('href'), function(state) {
            element.removeClass('ajax');
            element.toggleClass('favorite', state);
            return element.attr({
                'disabled': false
            });
        });
        return event.preventDefault();
    });
    $('a[href*="admin/smileys/edit"], a[href*="admin/smileys/upload"]').live('click', function(event) {
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
                    return $(this).remove();
                }
            };
            return $(this).dialog(options);
        });
        return event.preventDefault();
    });
    return $('.smiley-modal .button.cancel').live('click', function(event) {
        $(this).closest('.smiley-modal').dialog('close');
        return event.preventDefault();
    });
});