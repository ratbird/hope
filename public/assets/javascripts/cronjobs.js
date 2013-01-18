(function ($, STUDIP) {

$('.cron-task input').live('change', function () {
    $(this).closest('tbody').addClass('selected')
           .siblings().removeClass('selected');
});

$(':checkbox[data-proxyfor]').live('change', function () {
    var proxied = $(this).data().proxyfor,
        state   = !!$(this).attr('checked');
    $(proxied).attr('checked', state);
});

$('a[rel~="lightbox"]').live('click', function (event) {
    var $that     = $(this),
        href      = $that.attr('href'),
        container = $('<div/>');
    
    container.load(href, function (response, status, xhr) {
        var width   = $('body').width() * 2 / 3,
            height  = $('body').height() * 2 / 3,
            buttons = {},
            title   = xhr.getResponseHeader('X-Title') || '';

        $('a[rel~="option"]', this).remove().each(function () {
            var label = $(this).text(),
                href  = $(this).attr('href');
            buttons[label] = function () { location.href = href; };
        });
        buttons["Schliessen".toLocaleString()] = function () { $(this).dialog('close'); };

        $(this).dialog({
            width :  width,
            height:  height,
            buttons: buttons,
            title:   title,
            modal:   true
        });
    });
    
    event.preventDefault();
});

$('a[data-behaviour~="ajax-toggle"]').live('click', function (event) {
    var $that = $(this),
        href  = $that.attr('href'),
        id    = $that.closest('tr').attr('id');

    $that.attr('disabled', true).addClass('ajaxing');
    $.get(href, function (response) {
        var row = $('#' + id, response);
        $that.closest('tr').replaceWith(row);
    });

    event.preventDefault();
});

$('.cron-item select').live('change', function () {
    var state = $(this).val().length > 0,
        $next = $(this).next();

    if (state) {
        $next.show().find('input').focus();
    } else {
        $next.hide();
    }
});

$(document).ready(function () {
    $('.cronjobs-edit input[type=date]').datepicker();
    $('.cronjobs-edit input[type=time]').timepicker();
    
    $('.cron-item select').change();
})


}(jQuery, STUDIP));