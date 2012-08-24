<?
# Lifter010: TODO
?>
<TR>
    <TD height="3" class="table_row_even">
    </TD>
</TR>
<TR>
    <? if ($tpl['space']) { ?>
    <TD width="10%" class="table_row_even">
        &nbsp;
    </TD>
    <? } ?>
    <TD width="70%" nowrap class="<?=$tpl['class']?>"<?=!$tpl['space'] ? ' colspan="2"' : ''?>>
        <FONT size="-1" color="#000000">
            &nbsp;&nbsp;&nbsp;
            <?=mila($tpl['name'])?>
        </FONT>
    </TD>
    <TD class="<?=$tpl['class']?>" align="right">
        <A href="<?= URLHelper::getLink('?cmd=deleteIssueID&issue_id='. $tpl['issue_id'] .'&sd_id='. $tpl['sd_id'] .'&cycle_id='. $tpl['cycle_id']) ?>">
            <?=Assets::img('icons/16/blue/trash.png', array('class' => 'text-top')) ?>
        </A>
    </TD>
</TR>
<?
    unset($tpl)
?>
