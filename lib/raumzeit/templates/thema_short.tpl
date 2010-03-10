<TR>
    <TD height="3" class="steel1">
    </TD>
</TR>
<TR>
    <? if ($tpl['space']) { ?>
    <TD width="10%" class="steel1">
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
            <IMG src="<?=$GLOBALS['ASSETS_URL']?>images/trash.gif" border="0" align="abstop">
        </A>
    </TD>
</TR>
<? 
    unset($tpl)
?>
