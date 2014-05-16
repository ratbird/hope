<?php use Studip\Button, Studip\LinkButton; ?>
<form method="POST" action="<?=URLHelper::getLink()?>#anker">
<?=CSRFProtection::tokenTag()?>
<table border="0" cellspacing=0 cellpadding=6 width = "100%">
    <tr>
        <td align><font size="-1">
            <? if ($message) : ?>
                <?=$message?>
            <? else : ?>
                <font size="-1">
                <b><?=_('Loginname:')?></b> <?=$login?>
                </font>
            <? endif ?>
        </font></td>
        <td align="right">
            <input type="HIDDEN" name="new_account_step" value="1">
            <input type="HIDDEN" name="new_account_cms" value="<?=htmlReady($my_account_cms)?>">
        <? if ($is_connected) : ?>
            <?=Button::create(_('Bearbeiten'), 'change')?>
        <? else : ?>
            <?=Button::create(_('Erstellen'), 'create')?>
        <? endif?>
        </td>
    </tr>
</table>
</form>