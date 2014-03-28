(function ($) {
    // Global handler:
    // Use a checkbox as a proxy for a set of other checkboxes. Define
    // proxied elements by a css selector in attribute "data-proxyfor".
    $(document).on('change', ':checkbox[data-proxyfor]', function (event) {
        // Detect if event was triggered natively (triggered events have no
        // originalEvent)
        if (event.originalEvent !== undefined) {
            var proxied = $(this).data('proxyfor');
            $(proxied).filter(':not(:disabled)').attr('checked', this.checked);
        }
    }).on('update.proxy', ':checkbox[data-proxyfor]', function () {
        var proxied = $(this).data('proxyfor'),
            $proxied = $(proxied),
            $checked = $proxied.filter(':not(:disabled)').filter(':checked');
        $(this).prop('checked', $proxied.length > 0 && $proxied.length === $checked.length);
        $(this).prop('indeterminate', $checked.length > 0 && $checked.length < $proxied.length);
        $(this).trigger('change');
    }).on('change', ':checkbox[data-proxiedby]', function () {
        var proxy = $(this).data('proxiedby');
        $(proxy).trigger('update.proxy');
    }).ready(function () {
        $(':checkbox[data-proxyfor]').each(function () {
            var proxied = $(this).data('proxyfor');
            // The following seems like a hack but works perfectly fine.
            $(proxied).attr('data-proxiedby', true).data('proxiedby', this);
        }).trigger('update.studip');
    });

    // Global handler:
    // Use a checkbox as a toggle switch for the disabled attribute of another
    // element. Define element to disable if checkbox is neither :checked nor
    // :indeterminate by a css selector in attribute "data-activates".
    $(document).on('change', ':checkbox[data-activates]', function () {
        var activates = $(this).data('activates'),
            activated = $(this).prop('checked') || $(this).prop('indeterminate') || false;
        $(activates).attr('disabled', !activated).trigger('update.proxy');
    }).ready(function () {
        $(':checkbox[data-activates]').trigger('change');
    });

    // Global handler:
    // Use a select as a toggle switch for the disabled attribute of another
    // element. Define element to disable if select has a value different from
    // an empty string by a css selector in attribute "data-activates".
    $(document).on('change update.proxy', 'select[data-activates]', function () {
        var activates = $(this).data('activates'),
            disabled = $(this).is(':disabled') || $(this).val().length === 0;
        $(activates).attr('disabled', disabled);
    }).ready(function () {
        $('select[data-activates]').trigger('change');
    });
}(jQuery));
