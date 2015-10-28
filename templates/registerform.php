<?php
# Lifter005: TODO - form validation
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;
    $cfg = Config::GetInstance();
    $email_restriction = $cfg->getValue('EMAIL_DOMAIN_RESTRICTION');
?>
<script type="text/javascript" language="javaScript">
jQuery(document).ready(function() {
    STUDIP.register.re_username = <?= $validator->username_regular_expression ?>;
    STUDIP.register.re_name = <?= $validator->name_regular_expression ?>;

    <? if (trim($email_restriction)) {
        echo 'STUDIP.register.re_email = ' . $validator->email_regular_expression_restricted_part . '/;';
    } else {
        echo 'STUDIP.register.re_email = ' . $validator->email_regular_expression . ';';
    }
    ?>

    // Activate the appropriate input form field.
    jQuery('input[name=username]').focus();
});
</script>

<?if (isset($username)): ?>
    <?= MessageBox::error(_("Bei der Registrierung ist ein Fehler aufgetreten!"), array($error_msg, _("Bitte korrigieren Sie Ihre Eingaben und versuchen Sie es erneut"))) ?>
<?endif;?>

<h1><?= _('Stud.IP - Registrierung') ?></h1>

<h2><?= _('Herzlich willkommen!') ?></h2>
<p><?= _('Bitte füllen Sie zur Anmeldung das Formular aus:') ?></p>

<br>

<form name="login" action="<?= URLHelper::getLink() ?>" method="post" onsubmit="return STUDIP.register.checkdata();">
    <?= CSRFProtection::tokenTag() ?>
    <input type="hidden" name="login_ticket" value="<?= Seminar_Session::get_ticket() ?>">

    <table class="default">
        <colgroup>
            <col width="10%">
            <col width="10%">
            <col width="80%">
        </colgroup>
        <tbody>
            <tr>
                <td colspan="2">
                    <label for="username">
                        <?= _("Benutzername:") ?>
                    </label>
                </td>
                <td>
                    <input type="text" name="username" id="username"
                           onchange="STUDIP.register.checkusername()"
                           value="<?= htmlReady($username) ?>"
                           size="32" maxlength="63"
                           autocapitalize="off" autocorrect="off">
                </td>
            </tr>

            <tr>
                <td colspan="2">
                    <label for="password">
                        <?= _('Passwort:') ?>
                    </label>
                </td>
                <td>
                    <input type="password" name="password" id="password"
                           onchange="STUDIP.register.checkpassword()"
                           size="32" maxlength="31">
                </td>
            </tr>

            <tr>
                <td colspan="2">
                    <label for="password2">
                        <?= _('Passwortbestätigung:') ?>
                    </label>
                </td>
                <td>
                    <input type="password" name="password2" id="password2"
                           onchange="STUDIP.register.checkpassword2()"
                           size="32" maxlength="31">
                </td>
            </tr>

            <tr>
                <td>
                    <label for="title_front">
                        <?= _('Titel:') ?>
                    </label>
                </td>
                <td style="text-align: right">
                    <select name="title_chooser_front" onchange="document.login.title_front.value=document.login.title_chooser_front.options[document.login.title_chooser_front.selectedIndex].text;">
                    <? foreach ($GLOBALS['TITLE_FRONT_TEMPLATE'] as $template): ?>
                        <option <? if ($template === $title_front) echo 'selected'; ?>>
                            <?= htmlReady($template) ?>
                        </option>
                    <? endforeach; ?>
                    </select>
                </td>
                <td>
                    <input type="text" name="title_front" id="title_front"
                           value="<?= htmlReady($title_front) ?>"
                           size="32" maxlength="63">
                </td>
            </tr>

            <tr>
                <td>
                    <label for="title_rear">
                        <?= _('Titel nachgest.:') ?>
                    </label>
                </td>
                <td style="text-align: right;">
                    <select name="title_chooser_rear" onChange="document.login.title_rear.value=document.login.title_chooser_rear.options[document.login.title_chooser_rear.selectedIndex].text;">
                    <? foreach ($GLOBALS['TITLE_REAR_TEMPLATE'] as $template): ?>
                        <option <? if ($template === $title_rear) echo 'selected'; ?>>
                            <?= htmlReady($template) ?>
                        </option>
                    <? endforeach; ?>
                    </select>
                </td>
                <td>
                    <input type="text" name="title_rear" id="title_rear"
                           value="<?= htmlReady($title_rear) ?>"
                           size="32" maxlength="63">
                </td>
            </tr>

            <tr>
                <td colspan="2">
                    <label for="first_name">
                        <?= _('Vorname:') ?>
                    </label>
                </td>
                <td>
                    <input type="text" name="Vorname" id="first_name"
                           onchange="STUDIP.register.checkVorname()"
                           value="<?= htmlReady($Vorname) ?>"
                           size="32" maxlength="63">
                </td>
            </tr>

            <tr>
                <td colspan="2">
                    <label for="last_name">
                        <?= _('Nachname:') ?>
                    </label>
                </td>
                <td>
                    <input type="text" name="Nachname" id="last_name"
                           onchange="STUDIP.register.checkNachname()"
                           value="<?= htmlReady($Nachname) ?>"
                           size="32" maxlength="63">
                </td>
            </tr>

            <tr>
                <td colspan="2">
                    <label for="gender">
                        <?= _('Geschlecht:') ?>
                    </label>
                </td>
                <td>
                    <label>
                        <input type="radio" <? if (!$geschlecht) echo 'checked' ?> name="geschlecht" value="0">
                        <?= _("unbekannt") ?>
                    </label>
                    &nbsp;
                    <label>
                        <input type="radio" <? if ($geschlecht == 1) echo "checked" ?> name="geschlecht" value="1">
                        <?= _("männlich") ?>
                    </label>
                    &nbsp;
                    <label>
                        <input type="radio" name="geschlecht" <? if ($geschlecht == 2) echo "checked" ?> value="2">
                        <?= _("weiblich") ?>
                    </label>
                </td>
            </tr>

            <tr>
                <td colspan="2">
                    <label for="email">
                        <?= _("E-Mail:") ?>
                    </label>
                </td>
                <?
                echo '<td nowrap="nowrap">';
                if (trim($email_restriction)) {
                    echo '<input name="Email" onchange="STUDIP.register.checkEmail()"  value="';
                    echo htmlReady(isset($Email) ? preg_replace('|@.*|', '', trim($Email)) : '' );
                    echo "\" size=20 maxlength=63 id='email'>\n";
                    $email_restriction_parts = explode(',', $email_restriction);
                    echo '&nbsp;<select name="emaildomain">';
                    foreach ($email_restriction_parts as $email_restriction_part) {
                        echo '<option value="' . trim($email_restriction_part) . '"';
                        if (trim($email_restriction_part) == Request::get('emaildomain')) {
                            echo ' selected="selected"';
                        }
                        echo '>@' . trim($email_restriction_part) . "</option>\n";
                    }
                    echo '</select>';
                } else {
                    echo '<input type="email" name="Email" id="email" onchange="STUDIP.register.checkEmail()"  value="';
                    echo htmlReady(trim($Email)) . "\" size=32 maxlength=63>\n";
                }
                ?>
                </td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align: center;">
                    <?= Button::createAccept(_('Registrieren'))?>
                    <?= LinkButton::createCancel(_('Registrierung abbrechen'), 'index.php?cancel_login=1')?>
                </td>
            </tr>
        </tfoot>
    </table>
</form>
