<TABLE cellspacing="0" cellpadding="0" border="0"> 
	<TR>
		<TD colspan="5" class="blank" height="10"></TD>
	</TR>
	<TR>
		<TD class="steelkante" valign="middle">
			<IMG src="<?=$GLOBALS['ASSETS_URL']?>images/blank.gif" height="22" width="5">
		</TD>
		<TD class="steelkante" valign="middle" nowrap>
			<FONT size="-1"> Semester:&nbsp;</FONT>
		</TD>
<?
if ( (!$tpl['forceShowAll']) && (sizeof($tpl['semester']) <= 2)) {
?>
	<TD class="steelkante" nowrap="nowrap" valign="middle">
		&nbsp;
		<FONT size="-1"><?=array_shift($tpl['semester'])?>&nbsp;</FONT>&nbsp;
	</TD>
<?
} else {
	$sem_index = 0;
	foreach ($tpl['semester'] as $key => $val) {
		if ( (($sem_index % 5) == 0)  && ($sem_index != 0)) { echo '</TR><TR><TD></TD><TD></TD>'; }
		if ($tpl['selected'] == $key) { ?>
			<TD class="steelgraulight_shadow" nowrap="nowrap" valign="middle" width="117" height="20">
				&nbsp;
				<IMG src="<?=$GLOBALS['ASSETS_URL']?>images/forumrot_indikator.gif" align="middle">
				<FONT size="-1"><?=$val?></FONT>
			</TD>
	<? } else { ?>
			<TD class="steelkante" nowrap="nowrap" valign="middle" width="117" height="20">
				&nbsp;
				<a href="<?= URLHelper::getLink('?cmd=applyFilter&newFilter=' . $key) ?>">
					<?= Assets::img('forum_indikator_grau', array('align' => 'middle')) ?>
					<font color="#555555" size="-1"><?=$val?></font>
				</A>
				&nbsp;
			</TD>
<?
		}
		$sem_index++;
	}
}
?>
	</TR>
</TABLE>
<? unset($tpl); ?>
