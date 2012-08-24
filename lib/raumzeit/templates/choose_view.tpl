<?
# Lifter010: TODO
?>
<TABLE cellspacing="0" cellpadding="0" border="0">
    <TR>
        <TD colspan="5" class="blank" height="10"></TD>
    </TR>
    <TR>
        <TD class="content_seperator" valign="middle">
            <IMG src="<?=$GLOBALS['ASSETS_URL']?>images/blank.gif" height="22" width="5">
        </TD>
        <TD class="content_seperator" valign="middle" nowrap>
            <FONT size="-1"> Ansicht:&nbsp;</FONT>
        </TD>
<? foreach ($tpl['view'] as $key => $val) {
    if ($tpl['selected'] == $key) { ?>
        <TD class="table_row_odd_shadow" nowrap="nowrap" valign="middle">
            &nbsp;
            <?= Assets::img('icons/16/red/arr_1right.png', array('class' => 'text-top')) ?>
            <FONT size="-1"><?=$val?></FONT> &nbsp;
        </TD>
<? } else { ?>
        <TD class="content_seperator" nowrap="nowrap" valign="middle">
            &nbsp;
            <A href="<?= URLHelper::getLink('themen.php?cmd=changeViewMode&newFilter=' . $key) ?>">
                <?= Assets::img('icons/16/blue/arr_1right.png', array('align' => 'text-top')) ?>
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
