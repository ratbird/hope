jQuery(function ($) {
    $('input[type=color]').miniColors();

    $('input[type=number]').keyup(function (event) {
        var value = $(this).val().replace(/\D/, '');
        $(this).val(value);
    });

    $('#all').click(function () {
        $('.files input:checkbox').attr('checked', this.checked);
    });
});