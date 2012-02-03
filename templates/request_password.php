<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;
?>
<div class="index_container" style="width: 750px;">
<?if (sizeof($messages)):?>
<table width="100%">
    <?=parse_msg_array($messages, '', 1, false)?>
</table>
<?endif;?>
<table class="index_box logintable">
<tr>
    <td class="topic">
    <img src="<?=$GLOBALS['ASSETS_URL']?>images/icons/16/white/door-enter.png" border="0">
    <b>&nbsp;<?=sprintf(_("Stud.IP - Neues Passwort anfordern (Schritt %s von 5)"), $step)?></b>
    </td>
</tr>
<tr>
    <td>
    <div style="margin-left:40px;margin-top:15px;">
        <div style="width: 400px; margin-bottom: 1em;">
            <?if ($step == 2 || $step == 4):?>
            <br><br><?=$link_startpage?>
            <?endif;?>
        <?if ($step == 1):?>
            <?if (!sizeof($messages)):?>
            <?=_("Bitte geben Sie Ihre E-Mail-Adresse an, die Sie in Stud.IP benutzen. An diese Adresse wird ihnen eine E-Mail geschickt, die einen Bestätigungslink enthält, mit dem Sie ein neues Passwort anfordern können.<br>Bitte beachten Sie die Hinweise in dieser E-Mail.")?>
            <br><br>
            <?endif;?>
            <?=_("Geben Sie Ihre E-Mail-Adresse ein:")?><br>
        </div>
        <form name="newpwd" method="post" action="<?=$_SERVER['REQUEST_URI']?>">
            <?= CSRFProtection::tokenTag() ?>
            <input type="hidden" name="step" value="1">
            <table border="0" cellspacing="0" cellpadding="4">
                <tr valign=top align=left>
                    <td><?=_("E-Mail:")?> </td>
                    <td>
                        <input type="text" name="email" value="<?=htmlReady($email)?>" size="20" maxlength="63">
                    </td>
                </tr>
                <tr>
                    <td align="center" colspan="2">
                        <?= Button::createAccept(_('Abschicken'))?>
                        <?= LinkButton::createCancel(_('Abbrechen'), 'index.php?cancel_login=1')?>
                        <br>
                    </td>
                </tr>
            </table>
        </form>
        <?else:?>
        </div>
        <?endif;?>
    </div>
    </td>
</tr>
</table>
</div>
<?if ($step == 1):?>
<script type="text/javascript" language="javascript">
<!--
  // Activate the appropriate input form field.
    document.newpwd.email.focus();
// -->
</script>
<?endif;?>
