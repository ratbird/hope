<TR>
    <TD class="steelgraulight" colspan="3">
        <A name="<?=$tpl['md_id']?>" />
        <A class="tree" href="<?= URLHelper::getLink('?cmd='. ($issue_open[$tpl['md_id']] ? 'close' : 'open') 
            . '&open_close_id=' . $tpl['md_id'] .'#'. $tpl['md_id']) ?>">
            <IMG src="<?=$GLOBALS['ASSETS_URL']?>images/forumgrau<?=($issue_open[$tpl['md_id']]) ? 'runt' : ''?>.gif" border="0" align="abstop">&nbsp;
            <FONT size="-1">
                <?=$tpl['date']?>
            </FONT>
        </A>
    </TD>
</TR>
<TR>
    <TD class="steel1" colspan="3">
        &nbsp;
        <FONT size="-1"><?=_("ausgewählte Themen freien Terminen")?></FONT>&nbsp;
        <INPUT type="image" <?=makebutton('zuordnen', 'src')?> align="absMiddle" border="0" name="autoAssign_<?=$tpl['md_id']?>">
    </TD>
</TR>
<?
unset($tpl)
?>
