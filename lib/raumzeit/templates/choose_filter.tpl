<?
# Lifter010: TODO
?>
<TABLE cellspacing="0" cellpadding="0" border="0">
    <TR>
        <TD colspan="5" class="blank" height="10"></TD>
    </TR>
    <TR>
        <TD class="table_header" valign="middle">
            <IMG src="<?=$GLOBALS['ASSETS_URL']?>images/blank.gif" height="22" width="5">
        </TD>
        <TD class="table_header" valign="middle" nowrap>
            <FONT size="-1"> Semester:&nbsp;</FONT>
        </TD>
<?
if ( (!$tpl['forceShowAll']) && (sizeof($tpl['semester']) <= 2)) {
?>
    <TD class="content_seperator" nowrap="nowrap" valign="middle">
        &nbsp;
        <FONT size="-1"><?=array_shift($tpl['semester'])?>&nbsp;</FONT>&nbsp;
    </TD>
<?
} else {
    $sem_index = 0;
    foreach ($tpl['semester'] as $key => $val) {
        if ( (($sem_index % 5) == 0)  && ($sem_index != 0)) { echo '</TR><TR><TD></TD><TD></TD>'; }
        if ($tpl['selected'] == $key) { ?>
            <TD class="table_header_bold" nowrap="nowrap" valign="middle" width="117" height="20">
                &nbsp;
                <?= Assets::img('icons/16/red/arr_1right.png', array('class' => 'text-top')) ?>
				<FONT size="-1"><?=$val?></FONT>
            </TD>
    <? } else { ?>
            <TD class="table_header" nowrap="nowrap" valign="middle" width="117" height="20">
                &nbsp;
                <a href="<?= URLHelper::getLink('?cmd=applyFilter&newFilter=' . $key) ?>">
                    <?= Assets::img('icons/16/blue/arr_1right.png', array('align' => 'text-top')) ?>
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
