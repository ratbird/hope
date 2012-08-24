<?
# Lifter010: TODO

use Studip\Button, Studip\LinkButton;
?>
<TR>
    <TD class="table_row_odd" colspan="3">
        <A name="<?=$tpl['md_id']?>" />
        <A class="tree" href="<?= URLHelper::getLink('?cmd='. ($_SESSION['issue_open'][$tpl['md_id']] ? 'close' : 'open')
            . '&open_close_id=' . $tpl['md_id'] .'#'. $tpl['md_id']) ?>">
            <IMG src="<?=$GLOBALS['ASSETS_URL']?>images/forumgrau<?=($_SESSION['issue_open'][$tpl['md_id']]) ? 'runt' : ''?>.gif">
            <?=$tpl['date']?>
        </A>
    </TD>
</TR>
<TR>
    <TD class="table_row_even" colspan="3">
        <?=_("ausgewählte Themen freien Terminen")?>
        <?= Button::create(_('Zuordnen'), 'autoAssign_' . $tpl['md_id']) ?>
    </TD>
</TR>
<?
unset($tpl)
?>
