CKEDITOR.dialog.add('settingsDialog', function (editor) {
    // Time span after which the UI will display an abort option
    // to the user if the HTTP request hasn't yet finished.
    var uiTimeout = 3000;

    var settings = {
        url: STUDIP.URLHelper.resolveURL(
            'dispatch.php/wysiwyg/settings/users/current'
        ),
        save: function (data) {
            return $.ajax({
                url: this.url,
                type: 'PUT',
                contentType: 'application/json',
                data: JSON.stringify(data)
            });
        }
    };

    var
    dialog = null,
    status = $('<span>').attr('class', 'cke_disabled'),
    saveEvents = 0; // remember how many save events are currently active

    function save(data) {
        status.html('...speichere &Auml;nderungen.');
        saveEvents++;

        dialog.disableButton('ok');

        var request = settings.save(data);

        var timeoutId = setTimeout(function () {
            status
            .append(' (')
            .append(
                $('<a>')
                .text('Abbrechen')
                .css('text-decoration', 'underline') // TODO use default styles
                .click(function (event) {
                    event.preventDefault();
                    request.abort();
                })
            )
            .append(')');
        }, uiTimeout);
        
        request
        .done(function () {
            status.html('&Auml;nderungen wurden gespeichert.');
        })
        .fail(function (xhr) {
            var $error = $('<a>')
            .text('Info')
            .css('text-decoration', 'underline') // TODO use default styles
            .click(function (event) {
                event.preventDefault();
                alert(
                    settings.url +
                    '\n\nStatus: ' + xhr.status +
                    ' ' + xhr.statusText +
                    '\nResponse: ' + xhr.responseText
                );
            });

            status
            .html('Speichern fehlgeschlagen. (')
            .append($error)
            .append(')');
        })
        .always(function () {
            clearTimeout(timeoutId);
            saveEvents--;
            if (saveEvents <= 0) {
                dialog.enableButton('ok');
            }
        });

        return request;
    }

    return {
        title: 'Einstellungen',
        width: 400,
        height: 200,
        resizable: CKEDITOR.DIALOG_RESIZE_NONE,
        buttons: [CKEDITOR.dialog.okButton],
        contents: [{
            elements: [{
                type: 'checkbox',
                id: 'disable',
                label: 'WYSIWYG Editor ausschalten',
                onClick: function() {
                    var checkbox = this;
                    checkbox.disable(); // prevent multiple save events

                    save({ disabled: checkbox.getValue() })
                    .done(function (settings) {
                        checkbox.setValue(settings.disabled);
                    })
                    .fail(function () {
                        checkbox.setValue(!checkbox.getValue());
                    })
                    .always(function () {
                        checkbox.enable();
                    });
                }
            }, {
                type: 'html',
                style: 'white-space: normal',
                html: 'Mit dieser Einstellung k&ouml;nnen Sie den'
                    + ' WYSIWYG Editor ausschalten. Dadurch m&uuml;ssen'
                    + ' Sie gegebenenfalls Texte in HTML schreiben.'
                    + ' Der Editor wird erst vollst&auml;ndig entfernt'
                    + ' wenn man die Seite neu l&auml;dt.'
            }]
        }],
        onLoad: function (event) {
            $(this.parts.footer.$).append(status);
            this.parts.close.remove();
            dialog = this;
        },
        onShow: function (event) {
            status.text('');
        }
    };
});

