/*jslint browser: true, white: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, newcap: true, immed: true, indent: 4, onevar: false */
/*global window, $, jQuery, _ */

STUDIP.register = {
    re_username : null,
    re_email: null,
    re_name: null,
            
    clearErrors: function (field) {
        jQuery('input[name=' + field + ']').parent().find('div.error').remove();
    },

    addError: function (field, error) {
        jQuery('input[name=' + field + ']').parent().append('<div class="error">' + error + '</div>');
        jQuery('div[class=error]').show();
    },
    
    checkusername: function () {
        STUDIP.register.clearErrors('username');

        if (jQuery('input[name=username]').val().length < 4) {
            STUDIP.register.addError('username', "Der Benutzername ist zu kurz, er sollte mindestens 4 Zeichen lang sein.".toLocaleString());
            document.login.username.focus();
            return false;
        }
        
        if (STUDIP.register.re_username.test(jQuery('input[name=username]').val()) === false) {
            STUDIP.register.addError('username', "Der Benutzername enthält unzulässige Zeichen, er darf keine Sonderzeichen oder Leerzeichen enthalten.".toLocaleString());
            document.login.username.focus();
            return false;
        }

        return true;
    },

    checkpassword: function () {
        STUDIP.register.clearErrors('password');

        var checked = true;
        if (jQuery('input[name=password]').val().length < 4) {
            STUDIP.register.addError('password', "Das Passwort ist zu kurz, es sollte mindestens 4 Zeichen lang sein.".toLocaleString());
            document.login.password.focus();
            checked = false;
        }
        return checked;
    },

    checkpassword2: function () {
        STUDIP.register.clearErrors('password2');

        var checked = true;
        if (jQuery('input[name=password]').val() !== jQuery('input[name=password2]').val()) {
            STUDIP.register.addError('password2', "Das Passwort stimmt nicht mit dem Bestätigungspasswort überein!".toLocaleString());
            document.login.password2.focus();
            checked = false;
        }
        return checked;
    },

    checkVorname: function () {
        STUDIP.register.clearErrors('Vorname');

        var checked = true;
        if (STUDIP.register.re_name.test(jQuery('input[name=Vorname]').val()) === false) {
            STUDIP.register.addError('Vorname', "Bitte geben Sie Ihren tatsächlichen Vornamen an.".toLocaleString());
            document.login.Vorname.focus();
            checked = false;
        }
        return checked;
    },

    checkNachname: function () {
        STUDIP.register.clearErrors('Nachname');
        
        var checked = true;
        if (STUDIP.register.re_name.test(jQuery('input[name=Nachname]').val()) === false) {
            STUDIP.register.addError('Nachname', "Bitte geben Sie Ihren tatsächlichen Nachnamen an.".toLocaleString());
            document.login.Nachname.focus();
            checked = false;
        }
        return checked;
    },

    checkEmail: function () {
        STUDIP.register.clearErrors('Email');

        var Email = jQuery('input[name=Email]').val();

        var checked = true;
        if ((STUDIP.register.re_email.test(Email)) === false || Email.length === 0) {
            STUDIP.register.addError('Email', "Die E-Mail-Adresse ist nicht korrekt!".toLocaleString());
            document.login.Email.focus();
            checked = false;
        }
        return checked;
    },

    checkdata: function () {
        return this.checkusername() && 
                this.checkpassword() && 
                this.checkpassword2() && 
                this.checkVorname() && 
                this.checkNachname() && 
                this.checkEmail();
    }
};