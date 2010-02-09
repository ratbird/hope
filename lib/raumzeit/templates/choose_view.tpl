<TABLE cellspacing="0" cellpadding="0" border="0"> 
	<TR>
		<TD colspan="5" class="blank" height="10"></TD>
	</TR>
	<TR>
		<TD class="steelkante" valign="middle">
			<IMG src="<?=$GLOBALS['ASSETS_URL']?>images/blank.gif" height="22" width="5">
		</TD>
		<TD class="steelkante" valign="middle" nowrap>
			<FONT size="-1"> Ansicht:&nbsp;</FONT>
		</TD>
<? foreach ($tpl['view'] as $key => $val) {
	if ($tpl['selected'] == $key) { ?>
		<TD class="steelgraulight_shadow" nowrap="nowrap" valign="middle">
			&nbsp;
			<IMG src="<?=$GLOBALS['ASSETS_URL']?>images/forumrot_indikator.gif" align="middle">
			<FONT size="-1"><?=$val?></FONT> &nbsp;
		</TD>
<? } else { ?>
		<TD class="steelkante" nowrap="nowrap" valign="middle">
			&nbsp;
			<A href="<?= URLHelper::getLink('themen.php?cmd=changeViewMode&newFilter=' . $key) ?>">
				<?= Assets::img('forum_indikator_grau', array('align' => 'middle')) ?>
				<font color="#555555" size="-1"><?=$val?></font>
			</A>
			&nbsp;
		</TD>
<?
	}
}
?>
	</TR>
</TABLE>
<? unset($tpl); ?>
