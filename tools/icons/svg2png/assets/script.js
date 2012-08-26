jQuery(function ($) {
    $('input[type=color]').miniColors();

    var automatic = true;
    $('input[name="color-trigger"]').change(function () {
        $('input[name="color"]').miniColors('disabled', this.checked);
        if (!automatic && !this.checked) {
            $('input[name="color"]').focus();
        }
    }).change();
    automatic = false;

    $('input[type=number]').keyup(function (event) {
        var value = $(this).val().replace(/\D/, '');
        $(this).val(value);
    });

    $('#all').click(function () {
        $('.files input:checkbox').attr('checked', this.checked);
    });
});