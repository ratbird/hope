<?php use Studip\Button, Studip\LinkButton; ?>
<form method="POST" action="<?=URLHelper::getLink()?>#anker">
<?=CSRFProtection::tokenTag()?>
<table border="0" cellspacing=0 cellpadding=0 width = "99%">
<tr><td class="table_row_even" align="center" valign="middle" ><font size="-1">
<br>
<?=htmlReady($message)?>
<br>
<br>
<input type="HIDDEN" name="anker_target" value="search">
<input type="HIDDEN" name="view" value="<?=htmlReady($view)?>">
<input type="HIDDEN" name="cms_select" value="<?=htmlReady($cms_select)?>">
<input name="search_key" size="30" style="vertical-align:middle;font-size:9pt;" value="<?=htmlReady($search_key)?>">
&nbsp;
<?=Button::create(_('Suchen'))?>
<br>
<br>
</font>
</td></tr></table>
</form>