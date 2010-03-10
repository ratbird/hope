<? # Lifter005: TODO - challenge response ?>
<script type="text/javascript" language="javascript" src="<?=$GLOBALS['ASSETS_URL']?>javascripts/md5.js"></script>
<script type="text/javascript" language="javascript">
  <!--
  function doChallengeResponse() {
    <?if ($challenge){?>
    str = document.login.username.value + ":" +
      MD5(document.login.password.value) + ":" +
      document.login.challenge.value;
    document.login.response.value = MD5(str);
    document.login.password.value = '';
    <?}?>
    document.login.resolution.value = screen.width+"x"+screen.height;
    document.login.submit();
    return false;
  }
// -->
</script>
<? if ($loginerror) : ?>
<!-- failed login code -->
<div style="width: 800px; margin: auto;">
    <?= MessageBox::error(_("Bei der Anmeldung trat ein Fehler auf:") . "<br>"
        . $error_msg . " "
        . sprintf(_("Bitte wenden Sie sich bei Problemen an: %s"),
        "<a href=\"mailto:".$GLOBALS['UNI_CONTACT']."\">".$GLOBALS['UNI_CONTACT']."</a></font>")) ?>
</div>
<? endif; ?>
<table class="logintable" width="800" align="center" border="0" cellpadding="0" cellspacing="0">
<tr>
    <td class="topic">
    <img src="<?=$GLOBALS['ASSETS_URL']?>images/login.gif" border="0" alt="Login-Icon">
    <b>&nbsp;<?=_("Stud.IP - Login")?></b>
    </td>
</tr>
<tr>
    <td>
    <div style="margin-left:40px;margin-top:15px;">

        <?=_("Bitte identifizieren Sie sich mit Benutzername und Passwort:")?><br>&nbsp;

        <form name="login" method="post" action="<?=$_SERVER['REQUEST_URI']?>" onSubmit="return doChallengeResponse();">
        <!-- Set up the form with the challenge value and an empty reply value -->
        <input type="hidden" name="challenge" value="<?=$challenge?>">
        <input type="hidden" name="login_ticket" value="<?=Seminar_Session::get_ticket();?>">
        <input type="hidden" name="response"  value="">
        <input type="hidden" name="resolution"  value="">
        <table border="0" cellspacing="0" cellpadding="4">

            <tr valign=top align=left>
                <td><?=_("Benutzername:")?> </td>
                <td><input type="text" name="username" value="<?=htmlReady($uname)?>" size="20" maxlength="63">
                </td>
            </tr>

            <tr valign=top align=left>
                <td><?=_("Passwort:")?> </td>
                <td><input type="password" name="password" size="20"></td>
            </tr>

            <tr>
                <td align="center" colspan="2">
                    <?=makeButton("login", "input", _("Login"), "login")?>
                    &nbsp;
                    <a href="<?=UrlHelper::getLink('index.php?cancel_login=1')?>">
                    <?=makeButton("abbrechen", "img", _("Abbrechen"))?>
                    </a>
                    <br>
                </td>
            </tr>
        </table>
        </form>
    </div>
    <div style="margin-left:40px;margin-top:20px;margin-bottom:20px;">
    <?if  (!$loginerror):?>
    <font size="6"><b><?=_("Herzlich Willkommen!")?></b></font><br>
    <?endif;?>
    <font size="-1">
    <?if ($GLOBALS['ENABLE_REQUEST_NEW_PASSWORD_BY_USER'] && in_array('Standard', $GLOBALS['STUDIP_AUTH_PLUGIN'])):?>
    <a href="<?=UrlHelper::getLink('request_new_password.php?cancel_login=1')?>">
    <?else:?>
        <font size="-1"><a href="mailto:<?=$GLOBALS['UNI_CONTACT']?>?<?="subject=".rawurlencode("Stud.IP Passwort vergessen - ".$GLOBALS['UNI_NAME_CLEAN'])."&amp;body=".rawurlencode("Ich habe mein Passwort vergessen. Bitte senden sie mir ein Neues.\nMein Nutzername: ".htmlReady($uname)."\n")?>">
    <?endif;?>
    <?=_("Passwort vergessen")?></a>
    <?if($self_registration_activated){?>
        &nbsp;/&nbsp;
        <a href="<?=UrlHelper::getLink('register1.php?cancel_login=1')?>"><?=_("Registrieren")?></a>
    <?}?>
    </font>
    </div>
    </td>
    </tr>
    </table>
<script type="text/javascript" language="javascript">
<!--
  // Activate the appropriate input form field.
  if (document.login.username.value == '') {
    document.login.username.focus();
  } else {
    document.login.password.focus();
  }
// -->
</script>
