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

<table class="index_box logintable">
    <tr>
        <td class="table_header_bold"> <b><?= _("Stud.IP - Registrierung") ?></b> </td>
    </tr>
    
    <tr>
        <td class="blank" style="padding-top: 5px">
            <h2><?= _("Herzlich willkommen!") ?></h2>
            <?= _("Bitte füllen Sie zur Anmeldung das Formular aus:") ?>
            <br><br>
            
            <form name="login" action="<?= URLHelper::getLink() ?>" method="post" onsubmit="return STUDIP.register.checkdata();">
                <?= CSRFProtection::tokenTag() ?>
                <table border=0 cellspacing=2 cellpadding=4 class="default zebra">
                    <tr valign=top align=left>
                        <td colspan="2" width="20%"><?= _("Benutzername:") ?></td>
                        <td><input type="text" name="username" onchange="STUDIP.register.checkusername()" value="<?= isset($username) ? htmlReady($username) : "" ?>" size=32 maxlength=63 autocapitalize="off" autocorrect="off"></td>
                    </tr>

                    <tr valign=top align=left>
                        <td colspan="2"><?= _("Passwort:") ?></td>
                        <td><input type="password" name="password" onchange="STUDIP.register.checkpassword()" size=32 maxlength=31></td>
                    </tr>

                    <tr valign=top align=left>
                        <td colspan="2"><?= _("Passwortbestätigung:") ?></td>
                        <td><input type="password" name="password2" onchange="STUDIP.register.checkpassword2()" size=32 maxlength=31></td>
                    </tr>

                    <tr valign=top align=left>
                        <td><?= _("Titel:") ?>&nbsp;</td>
                        <td align="right">
                            <select name="title_chooser_front" onChange="document.login.title_front.value=document.login.title_chooser_front.options[document.login.title_chooser_front.selectedIndex].text;">
                                <?
                                for ($i = 0; $i < count($GLOBALS['TITLE_FRONT_TEMPLATE']); ++$i) {
                                    echo "\n<option";
                                    if ($GLOBALS['TITLE_FRONT_TEMPLATE'][$i] == $title_front)
                                        echo " selected ";
                                    echo ">" . $GLOBALS['TITLE_FRONT_TEMPLATE'][$i] . "</option>";
                                }
                                ?>
                            </select>
                        </td>
                        <td><input type="text" name="title_front" value="<?= isset($title_front) ? htmlReady($title_front) : "" ?>" size=32 maxlength=63></td>
                    </tr>

                    <tr valign=top align=left>
                        <td><?= _("Titel nachgest.:") ?>&nbsp;</td>
                        <td align="right">
                            <select name="title_chooser_rear" onChange="document.login.title_rear.value=document.login.title_chooser_rear.options[document.login.title_chooser_rear.selectedIndex].text;">
                                <?
                                for ($i = 0; $i < count($GLOBALS['TITLE_REAR_TEMPLATE']); ++$i) {
                                    echo "\n<option";
                                    if ($GLOBALS['TITLE_REAR_TEMPLATE'][$i] == $title_rear)
                                        echo " selected ";
                                    echo ">" . $GLOBALS['TITLE_REAR_TEMPLATE'][$i] . "</option>";
                                }
                                ?>
                            </select></td>
                        <td><input type="text" name="title_rear" value="<?= !empty($title_rear) ? htmlReady($title_rear) : "" ?>" size=32 maxlength=63></td>
                    </tr>
                    <tr valign=top align=left>
                        <td colspan="2"><?= _("Vorname:") ?></td>
                        <td><input type="text" name="Vorname" onchange="STUDIP.register.checkVorname()" value="<?= isset($Vorname) ? htmlReady($Vorname) : "" ?>"size=32 maxlength=63></td>
                    </tr>

                    <tr valign=top align=left>
                        <td colspan="2"><?= _("Nachname:") ?></td>
                        <td><input type="text" name="Nachname" onchange="STUDIP.register.checkNachname()" value="<?= isset($Nachname) ? htmlReady($Nachname) : "" ?>"size=32 maxlength=63></td>
                    </tr>

                    <tr valign=top align=left>
                        <td colspan="2"><?= _("Geschlecht:") ?></td>
                        <td><input type="radio" <? if (!$geschlecht) echo "checked" ?> name="geschlecht" value="0"><?= _("unbekannt") ?>&nbsp; <input type="radio" <? if ($geschlecht == 1) echo "checked" ?> name="geschlecht" value="1"><?= _("männlich") ?>&nbsp; <input type="radio" name="geschlecht" <? if ($geschlecht == 2) echo "checked" ?> value="2"><?= _("weiblich") ?></td>
                    </tr>

                    <tr valign=top align=left>
                        <td colspan="2"><?= _("E-Mail:") ?></td>
                        <?
                        echo '<td nowrap="nowrap">';
                        if (trim($email_restriction)) {
                            echo '<input name="Email" onchange="STUDIP.register.checkEmail()"  value="';
                            echo htmlReady(isset($Email) ? preg_replace('|@.*|', '', trim($Email)) : '' );
                            echo "\" size=20 maxlength=63>\n";
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
                            echo '<input type="email" name="Email" onchange="STUDIP.register.checkEmail()"  value="';
                            echo htmlReady(isset($Email) ? trim($Email) : '' ) . "\" size=32 maxlength=63>\n";
                        }
                        ?>
                        </td>
                    </tr>
                </table>

                <div style="text-align: center">    
                    <div class="button-group">
                        <?= Button::createAccept(_('Registrieren'))?>
                        <?= LinkButton::createCancel(_('Registrierung abbrechen'), 'index.php?cancel_login=1')?>
                    </div>
                </div>

                <input type="hidden" name="login_ticket" value="<?=Seminar_Session::get_ticket();?>">
            </form>
        </td>
    </tr>
</table>