function type_is_supported(type) {
    var probe = document.createElement('input');
    probe.setAttribute('type', type);
    return probe.type !== 'text';
}

jQuery(function ($) {
    if (!type_is_supported('color')) {
        $('input[type=color]').miniColors();
    }

    if (!type_is_supported('number')) {
        $('input[type=number]').keyup(function (event) {
            var value = $(this).val().replace(/[^-\d]/, '');
            $(this).val(value);
        });
    }

    // Toggle all
    $('#all').click(function () {
        $('.files input:checkbox').attr('checked', this.checked);
    });
    
    $('.remove-color').live('click', function () {
        var name  = $(this).closest('div').find('[name="color[name][]"]').val(),
            color = $(this).closest('div').find('[name="color[color][]"]').val();
        $(this).closest('div').remove();

        $('<option/>').val(name + '-' + color).text(name + ' [' + color + ']')
                      .appendTo('select[name="new-color"]');
        
        return false;
    });
    
    $('[name="add-color"]').live('click', function () {
        var values = $('select[name="new-color"]').val().split('-'),
            container = $('<div/>'),
            temp;
        
        $('<input type="text" name="color[name][]"/> ').val(values[0]).appendTo(container);
        container.append(' ');
        temp = $('<input type="color" name="color[color][]"/>').val(values[1]).appendTo(container);
        container.append(' ');
        $('<a class="remove-color" href="#">entfernen</a>').appendTo(container);

        $(this).closest('fieldset').prev().append(container);

        if (!type_is_supported('color')) {
            temp.miniColors();
        }
        
        $('select[name="new-color"]').find(':selected').remove();

        return false;
    });
});