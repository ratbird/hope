<?php use Studip\Button, Studip\LinkButton; ?>
<form method="POST" action="<?=URLHelper::getLink()?>#anker">
<?=CSRFProtection::tokenTag()?>
<table border="0" cellspacing=0 cellpadding=0 width = "99%">
<tr><td class="table_row_even" align="center" valign="middle" ><font size="-1">
<?=ELearningUtils::getHeader(_("Angebundenes System"));?>
<br>
<?=htmlReady($message)?>
<br>
<br>
<input type="HIDDEN" name="anker_target" value="choose">
<input type="HIDDEN" name="view" value="<?=htmlReady($view)?>">
<input type="HIDDEN" name="search_key" value="<?=htmlReady($search_key)?>">
<select name="cms_select" style="vertical-align:middle">
<option value=""><?=_("Bitte auswählen")?></option>
<? foreach($options as $key => $name) : ?>
    <option value="<?=$key?>" <?=($cms_select == $key) ? ' selected' : ''?>>
        <?=htmlReady($name)?>
    </option>
<? endforeach ?>
</select>
&nbsp;
<?=Button::create(_('Auswählen'))?>
<br>
<br>
</font>
</td></tr></table>
</form>