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
            '*'          : 'Bitte �ndern Sie ihre Eingabe'.toLocaleString(),
            ':radio'     : 'Bitte w�hlen Sie einen Wert aus.'.toLocaleString(),
            ':email'     : 'Bitte geben Sie g�ltige E-Mail-Adresse ein'.toLocaleString(),
            ':number'    : 'Bitte geben Sie eine Zahl ein'.toLocaleString(),
            ':url'       : 'Bitte geben Sie eine g�ltige Web-Adresse ein'.toLocaleString(),
            '[max]'      : 'Der eingegebene Wert darf nicht gr��er als $1 sein'.toLocaleString(),
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
    }
};
