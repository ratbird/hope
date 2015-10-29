<?php
# Lifter005: TODO - form validation
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

$email_restriction = Config::get()->EMAIL_DOMAIN_RESTRICTION;
?>
<script type="text/javascript" language="javaScript">
jQuery(document).ready(function() {
    STUDIP.register = {
        re_username: <?= $validator->username_regular_expression ?>,
        re_name: <?= $validator->name_regular_expression ?>,
        re_email: <?= trim($email_restriction)
                      ? $validator->email_regular_expression_restricted_part
                      : $validator->email_regular_expression ?>
    };
});
</script>

<?if (isset($username)): ?>
    <?= MessageBox::error(_("Bei der Registrierung ist ein Fehler aufgetreten!"), array($error_msg, _("Bitte korrigieren Sie Ihre Eingaben und versuchen Sie es erneut"))) ?>
<?endif;?>

<h1><?= _('Stud.IP - Registrierung') ?></h1>

<form name="login" action="<?= URLHelper::getLink() ?>" method="post" onsubmit="return STUDIP.register.checkdata();" class="default">
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
                   required size="32" maxlength="63"
                   autocapitalize="off" autocorrect="off">
        </label>

        <label for="password">
            <?= _('Passwort') ?>
            <em class="required"></em>
            <input type="password" name="password" id="password"
                   onchange="STUDIP.register.checkpassword()"
                   required size="32" maxlength="31">
        </label>

        <label for="password2">
            <?= _('Passwortbestätigung') ?>
            <em class="required"></em>
            <input type="password" name="password2" id="password2"
                   onchange="STUDIP.register.checkpassword2()"
                   required size="32" maxlength="31">
        </label>

        <label for="title_front">
            <?= _('Titel') ?>
            <select name="title_chooser_front" onchange="document.login.title_front.value=document.login.title_chooser_front.options[document.login.title_chooser_front.selectedIndex].text;" class="size-s">
            <? foreach ($GLOBALS['TITLE_FRONT_TEMPLATE'] as $template): ?>
                <option <? if ($template === $title_front) echo 'selected'; ?>>
                    <?= htmlReady($template) ?>
                </option>
            <? endforeach; ?>
            </select>

            <input type="text" name="title_front" id="title_front"
                   value="<?= htmlReady($title_front) ?>"
                   size="32" maxlength="63">
        </label>

        <label for="title_rear">
            <?= _('Titel nachgestellt') ?>

            <select name="title_chooser_rear" onChange="document.login.title_rear.value=document.login.title_chooser_rear.options[document.login.title_chooser_rear.selectedIndex].text;" class="size-s">
            <? foreach ($GLOBALS['TITLE_REAR_TEMPLATE'] as $template): ?>
                <option <? if ($template === $title_rear) echo 'selected'; ?>>
                    <?= htmlReady($template) ?>
                </option>
            <? endforeach; ?>
            </select>

            <input type="text" name="title_rear" id="title_rear"
                   value="<?= htmlReady($title_rear) ?>"
                   size="32" maxlength="63">
        </label>

        <label for="first_name">
            <?= _('Vorname') ?>

            <input type="text" name="Vorname" id="first_name"
                   onchange="STUDIP.register.checkVorname()"
                   value="<?= htmlReady($Vorname) ?>"
                   required size="32" maxlength="63">
        </label>

        <label for="last_name">
            <?= _('Nachname') ?>

            <input type="text" name="Nachname" id="last_name"
                   onchange="STUDIP.register.checkNachname()"
                   value="<?= htmlReady($Nachname) ?>"
                   required size="32" maxlength="63">
        </label>

        <section class="display-row">
            <label for="gender">
                <?= _('Geschlecht') ?>
            </label>

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

        <? if (trim($email_restriction)): ?>
            <input name="Email" id="email"
                   onchange="STUDIP.register.checkEmail()"
                   value="<?= htmlReady(isset($Email) ? preg_replace('|@.*|', '', trim($Email)) : '') ?>"
                   required size="20" maxlength="63" class="size-m">
            <select name="emaildomain" class="size-m">
            <? foreach (explode(',', $email_restriction) as $domain): ?>
                <option value="<?= trim($email_restriction_part) ?>"
                        <? if (trim($email_restriction_part) == Request::get('emaildomain')) echo 'selected'; ?>>
                    @<?= trim($domain) ?>
                </option>
            <? endforeach; ?>
            </select>
        <? else: ?>
            <input type="email" name="Email" id="email"
                   onchange="STUDIP.register.checkEmail()"
                   value="<?= htmlReady(trim($Email)) ?>"
                   required size="32" maxlength="63">
        <? endif; ?>
        </label>
    </fieldset>
    
    <footer>
        <?= Button::createAccept(_('Registrieren'))?>
        <?= LinkButton::createCancel(_('Registrierung abbrechen'), 'index.php?cancel_login=1')?>
    </footer>
</form>
