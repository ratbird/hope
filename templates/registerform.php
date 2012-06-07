<?php
# Lifter005: TODO - form validation
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;
    $cfg = Config::GetInstance();
    $email_restriction = $cfg->getValue('EMAIL_DOMAIN_RESTRICTION');
?>
<script type="text/javascript" language="javaScript">
<!--
function checkusername(){
 var re_username = <?=$validator->username_regular_expression?>;
 var checked = true;
 if (document.login.username.value.length<4) {
    alert("<?=_("Der Benutzername ist zu kurz \\n- er sollte mindestens 4 Zeichen lang sein.")?>");
        document.login.username.focus();
    checked = false;
    }
 if (re_username.test(document.login.username.value)==false) {
    alert("<?=_("Der Benutzername enthält unzulässige Zeichen \\n- er darf keine Sonderzeichen oder Leerzeichen enthalten.")?>");
        document.login.username.focus();
    checked = false;
        }
 return checked;
}

function checkpassword(){
 var checked = true;
 if (document.login.password.value.length<4) {
    alert("<?=_("Das Passwort ist zu kurz \\n- es sollte mindestens 4 Zeichen lang sein.")?>");
        document.login.password.focus();
    checked = false;
    }
 return checked;
}

function checkpassword2(){
 var checked = true;
if (document.login.password.value != document.login.password2.value) {
    alert("<?=_("Das Passwort stimmt nicht mit dem Bestätigungspasswort überein!")?>");
            document.login.password2.focus();
    checked = false;
    }
 return checked;
}

function checkVorname(){
 var re_vorname = <?=$validator->name_regular_expression?>;
 var checked = true;
 if (re_vorname.test(document.login.Vorname.value)==false) {
    alert("<?=_("Bitte geben Sie Ihren tatsächlichen Vornamen an.")?>");
        document.login.Vorname.focus();
    checked = false;
        }
 return checked;
}

function checkNachname(){
 var re_nachname = <?=$validator->name_regular_expression?>;
 var checked = true;
 if (re_nachname.test(document.login.Nachname.value)==false) {
    alert("<?=_("Bitte geben Sie Ihren tatsächlichen Nachnamen an.")?>");
        document.login.Nachname.focus();
    checked = false;
        }
 return checked;
}

function checkEmail(){
 <? if (trim($email_restriction)) {
            echo 'var re_email = ' . $validator->email_regular_expression_restricted_part . '/;';
        } else {
            echo 'var re_email = ' . $validator->email_regular_expression . ';';
        }
?>

 var Email = document.login.Email.value;
 var checked = true;
 if ((re_email.test(Email))==false || Email.length==0) {
    alert("<?=_("Die E-Mail-Adresse ist nicht korrekt!")?>");
        document.login.Email.focus();
    checked = false;
    }
 return checked;
}

function checkdata(){
 // kompletter Check aller Felder vor dem Abschicken
 var checked = true;
 if (!checkusername())
  checked = false;
 if (!checkpassword())
  checked = false;
 if (!checkpassword2())
  checked = false;
 if (!checkVorname())
  checked = false;
 if (!checkNachname())
  checked = false;
 if (!checkEmail())
  checked = false;
 return checked;
}
// -->
</SCRIPT>
<div class="index_container" style="width: 750px;">

<?if (isset($username)): ?>
    <?= MessageBox::error(_("Bei der Registrierung ist ein Fehler aufgetreten!"), array($error_msg, _("Bitte korrigieren Sie Ihre Eingaben und versuchen Sie es erneut"))) ?>
<?endif;?>
<table class="index_box logintable">
<tr>
    <td class="topic"> <b><?=_("Stud.IP - Registrierung")?></b> </td>
</tr>
<tr>
    <td class="blank" style="padding: 1em;">
    <b><?=_("Herzlich willkommen!")?></b>
    <br>
    <?=_("Bitte f&uuml;llen Sie zur Anmeldung das Formular aus:")?>
    <br><br>
<form name=login action="<?= URLHelper::getLink() ?>" method="post" onsubmit="return checkdata()">
<?= CSRFProtection::tokenTag() ?>
<table border=0 bgcolor="#eeeeee" align="center" cellspacing=2 cellpadding=4>
 <tr valign=top align=left>
  <td colspan="2"><?=_("Benutzername:")?></td>
<td><input type="text" name="username" onchange="checkusername()" value="<?= isset($username) ? htmlReady($username) : "" ?>" size=32 maxlength=63 autocapitalize="off" autocorrect="off"></td>
</tr>

 <tr valign=top align=left>
  <td colspan="2"><?=_("Passwort:")?></td>
  <td><input type="password" name="password" onchange="checkpassword()" size=32 maxlength=31></td>
 </tr>

 <tr valign=top align=left>
  <td colspan="2"><?=_("Passwortbest&auml;tigung:")?></td>
  <td><input type="password" name="password2" onchange="checkpassword2()" size=32 maxlength=31></td>
 </tr>

 <tr valign=top align=left>
  <td><?=_("Titel:")?>&nbsp;</td>
  <td align="right">
  <select name="title_chooser_front" onChange="document.login.title_front.value=document.login.title_chooser_front.options[document.login.title_chooser_front.selectedIndex].text;">
  <?
  for($i = 0; $i < count($GLOBALS['TITLE_FRONT_TEMPLATE']); ++$i){
      echo "\n<option";
      if($GLOBALS['TITLE_FRONT_TEMPLATE'][$i] == $title_front)
        echo " selected ";
      echo ">" . $GLOBALS['TITLE_FRONT_TEMPLATE'][$i] . "</option>";
  }
  ?>
  </select>
  </td>
<td><input type="text" name="title_front" value="<?= isset($title_front) ? htmlReady($title_front) : "" ?>" size=32 maxlength=63></td>
 </tr>

  <tr valign=top align=left>
  <td><?=_("Titel nachgest.:")?>&nbsp;</td>
  <td align="right">
  <select name="title_chooser_rear" onChange="document.login.title_rear.value=document.login.title_chooser_rear.options[document.login.title_chooser_rear.selectedIndex].text;">
  <?
  for($i = 0; $i < count($GLOBALS['TITLE_REAR_TEMPLATE']); ++$i){
      echo "\n<option";
      if($GLOBALS['TITLE_REAR_TEMPLATE'][$i] == $title_rear)
        echo " selected ";
    echo ">" . $GLOBALS['TITLE_REAR_TEMPLATE'][$i] . "</option>";
  }
  ?>
  </select></td>
  <td><input type="text" name="title_rear" value="<?= !empty($title_rear) ? htmlReady($title_rear) : "" ?>" size=32 maxlength=63></td>
 </tr>
 <tr valign=top align=left>
  <td colspan="2"><?=_("Vorname:")?></td>
 <td><input type="text" name="Vorname" onchange="checkVorname()" value="<?= isset($Vorname) ? htmlReady($Vorname) : "" ?>"size=32 maxlength=63></td>
 </tr>

 <tr valign=top align=left>
  <td colspan="2"><?=_("Nachname:")?></td>
  <td><input type="text" name="Nachname" onchange="checkNachname()" value="<?= isset($Nachname) ? htmlReady($Nachname) : "" ?>"size=32 maxlength=63></td>
 </tr>

<tr valign=top align=left>
  <td colspan="2"><?=_("Geschlecht:")?></td>
 <td><input type="radio" <? if (!$geschlecht) echo "checked" ?> name="geschlecht" value="0"><?=_("unbekannt")?>&nbsp; <input type="radio" <? if ($geschlecht == 1) echo "checked" ?> name="geschlecht" value="1"><?=_("männlich")?>&nbsp; <input type="radio" name="geschlecht" <? if ($geschlecht == 2) echo "checked" ?> value="2"><?=_("weiblich")?></td>
</tr>

<tr valign=top align=left>
  <td colspan="2"><?=_("E-Mail:")?></td>
    <?
    echo '<td nowrap="nowrap"><input type="email" name="Email" onchange="checkEmail()"  value="';
    if (trim($email_restriction)) {
        echo (isset($Email) ? preg_replace('|@.*|', '', trim($Email)) : '' );
        echo "\" size=20 maxlength=63>\n";
        $email_restriction_parts = explode(',', $email_restriction);
        echo '&nbsp;<select name="emaildomain">';
        foreach ($email_restriction_parts as $email_restriction_part) {
            echo '<option value="' . trim($email_restriction_part) . '"';
            if (trim($email_restriction_part) == $_REQUEST['emaildomain']) {
                echo ' selected="selected"';
            }
            echo '>@' . trim($email_restriction_part) . "</option>\n";
        }
        echo '</select>';
    } else {
        echo (isset($Email) ? trim($Email) : '' ) ."\" size=32 maxlength=63>\n" ; 
    }
    ?>
    </td>
 </tr>

 <tr>
  <td colspan="3" align=right>
      <?= Button::create(_('Übernehmen'))?>
      <?= LinkButton::createCancel(_('Abbrechen'), 'index.php?cancel_login=1')?>
  </td>
 </tr>
</table>
<br><br>

<input type="hidden" name="login_ticket" value="<?=Seminar_Session::get_ticket();?>">
</form>

        </td>
    </tr>
</table>
</div>

<script language="JavaScript">
<!--
  // Activate the appropriate input form field.
  if (document.login.username.value == '') {
    document.login.username.focus();
  } else {
    document.login.password.focus();
  }
// -->
</script>
