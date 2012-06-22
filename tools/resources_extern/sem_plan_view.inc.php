<a href="<?=URLHelper::getLink('?semester_id='.$_semester_id.'&timespan='.$_timespan)?>" style="font-size:90%">
<img src="pictures/pfeil.gif" border="0" align="absmiddle">
zur&uuml;ck zur Startseite
</a>
<?php
show_sem_chooser($_semester_id, $_timespan);
show_sem_plan($_REQUEST['rid'], $_semester_id, $_timespan);
?>
<br>
<a href="<?=URLHelper::getLink('?semester_id='.$_semester_id.'&timespan='.$_timespan)?>" style="font-size:90%">
<img src="pictures/pfeil.gif" border="0" align="absmiddle">
zur&uuml;ck zur Startseite
</a>
