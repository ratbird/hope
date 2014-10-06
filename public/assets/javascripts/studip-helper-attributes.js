/**
 * This file provides a set of global handlers.
 */
(function ($) {
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
        }).trigger('update.proxy');
    });

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

    // Enable the user to set the checked state on a subset of related
    // checkboxes by clicking the first checkbox of the subset and then
    // clicking the last checkbox of the subset while holding down the shift
    // key, thus toggling all the checkboxes in between.
    // This only works if the first and last checkbox of the subset are set
    // to the same state.
    var last_element = null;
    $(document).on('click', '[data-shiftcheck] :checkbox', function (event) {
        if (!event.originalEvent || last_element === event.target) {
            return;
        }

        if (last_element !== null && event.shiftKey) {
            var $this = $(event.target),
                $form = $this.closest('form'),
                name  = $this.attr('name'),
                state = $this.prop('checked'),
                $last = $(last_element),
                children, idx0, idx1;

            if ($form.is($last.closest('form')) && name == $last.attr('name') && state == $last.prop('checked')) {
                children = $form.find(':checkbox[name="' + name + '"]:not(:disabled)');
                idx0 = children.index(event.target);
                idx1 = children.index(last_element);
                if (idx0 > idx1) {
                    // Swap variables, see http://stackoverflow.com/a/20531819
                    idx0 = idx1 + (idx1=idx0, 0);
                }
                children.slice(idx0, idx1).prop('checked', state);
            }
        }

        last_element = event.target;
    });
    
    // Display a visible hint that indicates how many characters the user has
    // already input into a text field or how many remaining characters he may
    // input if the element has a maxlength restriction.
    // By providing a css selector in the "data-length-hint" attribute you
    // are able to specify a custom element for the character display. If none
    // is provided or the selector does not point to a valid element, the
    // display element is created next to the attributed element.
    $(document).on('focus propertychange keyup', '[data-length-hint]', function (event) {
        var selector = $(this).data().lengthHint,
            counter  = $(selector),
            count    = $(this).val().length,
            max      = parseInt($(this).attr('maxlength')),
            element,
            message;

        if (max) {
            count = max - count;
        }

        if (counter.length === 0) {
            counter =  $(this).next('.length-hint').find('.length-hint-counter');
        }

        if (counter.length === 0) {
            message = max
                    ? "Zeichen verbleibend: ".toLocaleString()
                    : "Eingegebene Zeichen: ".toLocaleString();
            element = $('<span class="length-hint">').text(message);
            counter = $('<span class="length-hint-counter">');
            element.append(counter).insertAfter(this);
        }

        counter.text(count);
    });

}(jQuery));
