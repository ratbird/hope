/*jslint browser: true, white: true, undef: true, nomen: true, plusplus: true, bitwise: true, newcap: true, indent: 4, unparam: true */
/*global jQuery, STUDIP */

/* ------------------------------------------------------------------------
 * Forms
 * ------------------------------------------------------------------------ */

(function ($, STUDIP) {
    'use strict';

    STUDIP.Forms = {
        initialize : function () {
            $("input[required],textarea[required]").attr('aria-required', true);
            $("input[pattern][title],textarea[pattern][title]").each(function () {
                $(this).data('message', $(this).attr('title'));
            });

            //localized messages
            $.tools.validator.localize('de', {
                '*'          : 'Bitte ändern Sie ihre Eingabe'.toLocaleString(),
                ':radio'     : 'Bitte wählen Sie einen Wert aus.'.toLocaleString(),
                ':email'     : 'Bitte geben Sie gültige E-Mail-Adresse ein'.toLocaleString(),
                ':number'    : 'Bitte geben Sie eine Zahl ein'.toLocaleString(),
                ':url'       : 'Bitte geben Sie eine gültige Web-Adresse ein'.toLocaleString(),
                '[max]'      : 'Der eingegebene Wert darf nicht größer als $1 sein'.toLocaleString(),
                '[min]'      : 'Der eingegebene Wert darf nicht kleiner als $1 sein'.toLocaleString(),
                '[required]' : 'Dies ist ein erforderliches Feld'.toLocaleString()
            });

            $('form').validator({
                position   : 'bottom left',
                offset     : [8, 0],
                message    : '<div><div class="arrow"/></div>',
                lang       : 'de',
                inputEvent : 'change'
            });

            $('form').bind("onBeforeValidate", function () {
                $("input").each(function () {
                    $(this).removeAttr('aria-invalid');
                });
            });

            $('form').bind("onFail", function (e, errors) {
                $.each(errors, function () {
                    this.input.attr('aria-invalid', 'true');
                });
            });

            $(document).on("change", "form.default label.file-upload input[type=file]", function (ev) {
                var selected_file = ev.target.files[0],
                    filename;
                if ($(this).closest("label").find(".filename").length) {
                    filename = $(this).closest("label").find(".filename");
                } else {
                    filename = $('<span class="filename"/>');
                    $(this).closest("label").append(filename);
                }
                filename.text(selected_file.name + " " + Math.ceil(selected_file.size / 1024) + "KB");
            });
            $(document).on("keyup dialog-open", "form.default input[maxlength]", function () {
                var maxlength = $(this).attr("maxlength"),
                    length = $(this).val().length,
                    indicator;
                if ($(this).next().is(".maxlength")) {
                    indicator = $(this).next();
                } else {
                    indicator = $('<div class="maxlength"/>');
                    $(this).after(indicator);
                }
                indicator.text(maxlength - length);
            }).find("form.default input[maxlength]").trigger("keyup");

            $(document).on('click', 'form.default fieldset.collapsable legend,form.default.collapsable fieldset legend', function () {
                $(this).closest('fieldset').toggleClass('collapsed');
            });
        }
    };
    
}(jQuery, STUDIP));

