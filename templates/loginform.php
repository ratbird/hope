<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>
<script type="text/javascript" language="javascript">
//<![CDATA[
$(function () {
  $('form[name=login]').submit(function () {
    $('input[name=resolution]', this).val( screen.width + 'x' + screen.height );
  });
});
// -->
</script>
<div class="index_container" style="width: 750px; margin: 0 auto !important;">
<? if ($loginerror): ?>
<!-- failed login code -->
  <?= MessageBox::error(_("Bei der Anmeldung trat ein Fehler auf!"), array(
        $error_msg, sprintf(_("Bitte wenden Sie sich bei Problemen an: %s"),
        "<a href=\"mailto:".$GLOBALS['UNI_CONTACT']."\">".$GLOBALS['UNI_CONTACT']."</a>"))) ?>
<? endif; ?>
<table class="index_box logintable">
  <tbody>
    <tr>
      <td class="topic">
        <?= Assets::img('icons/16/white/door-enter.png', array('alt' => _('Anmelden'))) ?>
        <strong><?=_("Stud.IP - Login")?></strong>
      </td>
    </tr>
    <tr>
      <td style="padding: 5px 0 20px 40px;">
        <p><?=_("Bitte identifizieren Sie sich mit Benutzername und Passwort:")?></p>

        <form name="login" method="post" action="<?=$_SERVER['REQUEST_URI']?>">
          <?= CSRFProtection::tokenTag() ?>
          <input type="hidden" name="login_ticket" value="<?=Seminar_Session::get_ticket();?>">
          <input type="hidden" name="resolution"  value="">
          <table border="0" cellspacing="0" cellpadding="4">
            <tr valign=top align=left>
              <td><label for="loginname"><?=_("Benutzername:")?></label></td>
              <td><input type="text" <?= strlen($uname) ? '' : 'autofocus' ?> id="loginname" name="loginname" value="<?=htmlReady($uname)?>" size="20" maxlength="63"></td>
            </tr>

            <tr valign=top align=left>
              <td><label for="password"><?=_("Passwort:")?></label></td>
              <td><input type="password" <?= strlen($uname) ? 'autofocus' : '' ?> id="password" name="password" size="20"></td>
            </tr>

            <tr>
              <td align="center" colspan="2">
                <?= Button::createAccept(_('Anmelden'), _("Login")); ?>
                <?= LinkButton::createCancel(_('Abbrechen'),UrlHelper::getLink('index.php?cancel_login=1')); ?>
              </td>
            </tr>
          </table>
        </form>

        <div style="margin-top:20px;">
        <? if  (!$loginerror): ?>
          <h1 style="margin: 0;"><?=_("Herzlich willkommen!")?></h1>
        <?endif;?>
          <? if ($GLOBALS['ENABLE_REQUEST_NEW_PASSWORD_BY_USER'] && in_array('Standard', $GLOBALS['STUDIP_AUTH_PLUGIN'])): ?>
            <a href="<?=UrlHelper::getLink('request_new_password.php?cancel_login=1')?>">
          <? else: ?>
            <a href="mailto:<?=$GLOBALS['UNI_CONTACT']?>?<?="subject=".rawurlencode("Stud.IP Passwort vergessen - ".$GLOBALS['UNI_NAME_CLEAN'])."&amp;body=".rawurlencode("Ich habe mein Passwort vergessen. Bitte senden Sie mir ein Neues.\nMein Nutzername: ".htmlReady($uname)."\n")?>">
          <? endif; ?>
            <?=_("Passwort vergessen")?></a>
          <? if ($self_registration_activated): ?>
            &nbsp;/&nbsp;
            <a href="<?=UrlHelper::getLink('register1.php?cancel_login=1')?>"><?=_("Registrieren")?></a>
          <? endif; ?>
        </div>
      </td>
    </tr>
  </tbody>
</table>
</div>
