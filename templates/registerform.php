<?php
# Lifter005: TODO - form validation
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

$email_restriction = Config::get()->EMAIL_DOMAIN_RESTRICTION;
?>
<script type="text/javascript" language="javaScript">
jQuery(document).ready(function() {
    STUDIP.register.re_username = <?= $validator->username_regular_expression ?>;
    STUDIP.register.re_name = <?= $validator->name_regular_expression ?>;
    STUDIP.register.re_email = <?= trim($email_restriction)
        ? $validator->email_regular_expression_restricted_part
        : $validator->email_regular_expression ?>;

    $('form[name=login]').submit(function () {
        return STUDIP.register.checkdata();
    }).data('validator').destroy();
});
</script>

<? if (isset($username)): ?>
    <?= MessageBox::error(_("Bei der Registrierung ist ein Fehler aufgetreten!"), array($error_msg, _("Bitte korrigieren Sie Ihre Eingaben und versuchen Sie es erneut"))) ?>
<? endif; ?>

<h1><?= _('Stud.IP - Registrierung') ?></h1>

<form name="login" action="<?= URLHelper::getLink() ?>" method="post" class="default">
    <?= CSRFProtection::tokenTag() ?>
    <input type="hidden" name="login_ticket" value="<?= Seminar_Session::get_ticket() ?>">

    <fieldset>
        <legend><?= _('Herzlich willkommen!') ?></legend>

        <p><?= _('Bitte füllen Sie zur Anmeldung das Formular aus:') ?></p>

        <label for="username">
            <?= _('Benutzername') ?>
            <em class="required"></em>
            <input type="text" name="username" id="username"
                   onchange="STUDIP.register.checkusername()"
                   value="<?= htmlReady($username) ?>"
                   autofocus
                   required maxlength="63"
                   autocapitalize="off" autocorrect="off">
        </label>

        <label for="password">
            <?= _('Passwort') ?>
            <em class="required"></em>
            <input type="password" name="password" id="password"
                   onchange="STUDIP.register.checkpassword()"
                   required maxlength="31">
        </label>

        <label for="password2">
            <?= _('Passwortbestätigung') ?>
            <em class="required"></em>
            <input type="password" name="password2" id="password2"
                   onchange="STUDIP.register.checkpassword2()"
                   required maxlength="31">
        </label>

        <label for="title_front">
            <?= _('Titel') ?>
        </label>
        <section class="hgroup size-m">
            <select name="title_chooser_front" data-copy-to="#title_front" class="size-s">
            <? foreach ($GLOBALS['TITLE_FRONT_TEMPLATE'] as $template): ?>
                <option <? if ($template === $title_front) echo 'selected'; ?>>
                    <?= htmlReady($template) ?>
                </option>
            <? endforeach; ?>
            </select>

            <input type="text" name="title_front" id="title_front"
                   value="<?= htmlReady($title_front) ?>"
                   maxlength="63">
        </section>

        <label for="title_rear">
            <?= _('Titel nachgestellt') ?>
        </label>
        <section class="hgroup size-m">
            <select name="title_chooser_rear" data-copy-to="#title_rear" class="size-s">
            <? foreach ($GLOBALS['TITLE_REAR_TEMPLATE'] as $template): ?>
                <option <? if ($template === $title_rear) echo 'selected'; ?>>
                    <?= htmlReady($template) ?>
                </option>
            <? endforeach; ?>
            </select>

            <input type="text" name="title_rear" id="title_rear"
                   value="<?= htmlReady($title_rear) ?>"
                   maxlength="63">
        </section>

        <label for="first_name">
            <?= _('Vorname') ?>

            <input type="text" name="Vorname" id="first_name"
                   onchange="STUDIP.register.checkVorname()"
                   value="<?= htmlReady($Vorname) ?>"
                   required maxlength="63">
        </label>

        <label for="last_name">
            <?= _('Nachname') ?>

            <input type="text" name="Nachname" id="last_name"
                   onchange="STUDIP.register.checkNachname()"
                   value="<?= htmlReady($Nachname) ?>"
                   required maxlength="63">
        </label>

        <label for="gender">
            <?= _('Geschlecht') ?>
        </label>

        <section class="hgroup">
            <label>
                <input type="radio" <? if (!$geschlecht) echo 'checked' ?> name="geschlecht" value="0">
                <?= _("unbekannt") ?>
            </label>

            <label>
                <input type="radio" <? if ($geschlecht == 1) echo "checked" ?> name="geschlecht" value="1">
                <?= _("männlich") ?>
            </label>

            <label>
                <input type="radio" name="geschlecht" <? if ($geschlecht == 2) echo "checked" ?> value="2">
                <?= _("weiblich") ?>
            </label>
        </section>

        <label for="email">
            <?= _('E-Mail') ?>
        <? if (!trim($email_restriction)): ?>
            <input type="email" name="Email" id="email"
                   onchange="STUDIP.register.checkEmail()"
                   value="<?= htmlReady(trim($Email)) ?>"
                   required maxlength="63">
        <? endif; ?>
        </label>

    <? if (trim($email_restriction)): ?>
        <section class="hgroup size-m">
            <input type="text" name="Email" id="email"
                   onchange="STUDIP.register.checkEmail()"
                   value="<?= htmlReady(preg_replace('/@.*$/', '', trim($Email ?: ''))) ?>"
                   required maxlength="63">
            <select name="emaildomain">
            <? foreach (explode(',', $email_restriction) as $domain): ?>
                <option value="<?= trim($email_restriction_part) ?>"
                        <? if (trim($domain) == Request::get('emaildomain')) echo 'selected'; ?>>
                    @<?= trim($domain) ?>
                </option>
            <? endforeach; ?>
            </select>
        </section>
    <? endif; ?>
    </fieldset>

    <footer>
        <?= Button::createAccept(_('Registrieren'))?>
        <?= LinkButton::createCancel(_('Registrierung abbrechen'),
                                     URLHelper::getLink('index.php?cancel_login=1')) ?>
    </footer>
</form>
