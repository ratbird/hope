/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */

/* ------------------------------------------------------------------------
 * Forms
 * ------------------------------------------------------------------------ */

STUDIP.Forms = {
    initialize : function () {
        jQuery("input[required],textarea[required]").attr('aria-required', true);
        jQuery("input[pattern][title],textarea[pattern][title]").each(function () {
            jQuery(this).data('message', jQuery(this).attr('title'));
        });

        //localized messages
        jQuery.tools.validator.localize('de', {
            '*'          : 'Bitte ändern Sie ihre Eingabe'.toLocaleString(),
            ':radio'     : 'Bitte wählen Sie einen Wert aus.'.toLocaleString(),
            ':email'     : 'Bitte geben Sie gültige E-Mail-Adresse ein'.toLocaleString(),
            ':number'    : 'Bitte geben Sie eine Zahl ein'.toLocaleString(),
            ':url'       : 'Bitte geben Sie eine gültige Web-Adresse ein'.toLocaleString(),
            '[max]'      : 'Der eingegebene Wert darf nicht größer als $1 sein'.toLocaleString(),
            '[min]'      : 'Der eingegebene Wert darf nicht kleiner als $1 sein'.toLocaleString(),
            '[required]' : 'Dies ist ein erforderliches Feld'.toLocaleString()
        });

        jQuery('form').validator({
            position   : 'bottom left',
            offset     : [8, 0],
            message    : '<div><div class="arrow"/></div>',
            lang       : 'de',
            inputEvent : 'change'
        });

        jQuery('form').bind("onBeforeValidate", function () {
            jQuery("input").each(function () {
                jQuery(this).removeAttr('aria-invalid');
            });
        });

        jQuery('form').bind("onFail", function (e, errors) {
            jQuery.each(errors, function () {
                this.input.attr('aria-invalid', 'true');
            });
        });

        jQuery(document).on("change", "form.default label.file-upload input[type=file]", function (ev) {
            var selected_file = ev.target.files[0];
            if (jQuery(this).closest("label").find(".filename").length) {
                var filename = jQuery(this).closest("label").find(".filename");
            } else {
                var filename = jQuery('<span class="filename"/>');
                jQuery(this).closest("label").append(filename);
            }
            filename.text(selected_file.name + " " + Math.ceil(selected_file.size / 1024) + "KB");
        });
        jQuery(document).on("keyup dialog-open", "form.default input[maxlength]", function () {
            var maxlength = jQuery(this).attr("maxlength");
            var length = jQuery(this).val().length;
            if (jQuery(this).next().is(".maxlength")) {
                var indicator = jQuery(this).next();
            } else {
                var indicator = jQuery('<div class="maxlength"/>');
                jQuery(this).after(indicator);
            }
            indicator.text(maxlength - length);
        }).find("form.default input[maxlength]").trigger("keyup");
    }
};
