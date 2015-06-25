<?
# Lifter010: TODO

use Studip\Button, Studip\LinkButton;
?>
<TR>
    <TD width="10%" class="table_header" nowrap>
        <A name="<?=$tpl['issue_id']?>" />
        <INPUT type="checkbox" name="themen[]" value="<?=$tpl['issue_id']?>"<?=$tpl['selected']?>>
        <? if ($_SESSION['issue_open'][$tpl['issue_id']]) { ?>
            <A href="<?= URLHelper::getLink('?cmd=close&open_close_id='. $tpl['issue_id'] .'#'. $tpl['issue_id']) ?>">
                <?= Assets::img('icons/16/blue/arr_1down.png', array('class' => 'text-bottom')) ?>
        <? } else { ?>
            <A href="<?= URLHelper::getLink('?cmd=open&open_close_id='. $tpl['issue_id'] .'#'. $tpl['issue_id']) ?>">
                <?= Assets::img('icons/16/blue/arr_1right.png', array('class' => 'text-bottom')) ?>
        <? } ?>
        </A>
    </TD>
    <TD width="70%" nowrap class="table_header">
        <FONT size="-1">
        <? if ($_SESSION['issue_open'][$tpl['issue_id']]) { ?>
            <A class="tree" href="<?= URLHelper::getLink('?cmd=close&open_close_id='. $tpl['issue_id'] .'#'. $tpl['issue_id']) ?>">
        <? } else { ?>
            <A class="tree" href="<?= URLHelper::getLink('?cmd=open&open_close_id='. $tpl['issue_id'] .'#'. $tpl['issue_id']) ?>">
        <? } ?>
                <?=mila($tpl['theme_title'])?>
            </A>
        </FONT>
    </TD>
    <TD width="20%" align="right" class="table_header" nowrap>
        <? if (!$tpl['first']) { ?>
        <A href="<?= URLHelper::getLink('?newPriority='. ($tpl['priority'] - 1) .'&issueID='. $tpl['issue_id'] .'&cmd=changePriority') ?>">
            <?= Assets::img('icons/16/yellow/arr_2up.png', array('class' => 'text-bottom')) ?>
        </A>
        <? } ?>
        <? if (!$tpl['last']) { ?>
        <A href="<?= URLHelper::getLink('?newPriority='. ($tpl['priority'] + 1) .'&issueID='. $tpl['issue_id'] .'&cmd=changePriority') ?>">
            <?= Assets::img('icons/16/yellow/arr_2down.png', array('class' => 'text-bottom')) ?>
        </A>
        <? } ?>
        <A href="<?= URLHelper::getLink('?cmd=deleteIssue&issue_id='. $tpl['issue_id']) ?>">
            <?= Assets::img('icons/16/blue/trash.png', array('class' => 'text-bottom')) ?>
        </A>
    </TD>
</TR>
<? if ($_SESSION['issue_open'][$tpl['issue_id']]) { ?>
<TR>
    <TD width="10%" class="blank">
        &nbsp;
    </TD>
    <TD class="blank" colspan="2">
        <FONT size="-1">
            <B><?=("Titel:")?></B><BR/>
            <INPUT type="text" name="theme_title<?=$tpl['openAll'] ? '�'.$tpl['issue_id']: ''?>" maxlength="255" size="50" value="<?=$tpl['theme_title']?>" style="width: 98%"><BR/>
            <B><?=_("Beschreibung:")?></B><BR/>
            <textarea class="add_toolbar wysiwyg" style="width: 100%" name="theme_description<?=$tpl['openAll'] ? '�'.$tpl['issue_id']: ''?>" rows="5" cols="50"><?=wysiwygReady($tpl['theme_description'])?></textarea><BR/>
            <B><?=_("Verkn�fpungen mit diesem Termin:")?></B>
            <BR/>
            <? if ($tpl['forumEntry']) {
                echo _("Forenthema vorhanden").'<BR/>';
                echo '<INPUT type="hidden" name="forumFolder" value="on">';
            } else { ?>
            <INPUT type="checkbox" name="forumFolder<?=$tpl['openAll'] ? '�'.$tpl['issue_id']: ''?>"> <?=_("Thema im Forum anlegen")?><BR/>
            <? } ?>
            <? if ($tpl['fileEntry']) {
                echo _("Dateiordner vorhanden");
                echo '<INPUT type="hidden" name="fileFolder" value="on">';
            } else { ?>
                <INPUT type="checkbox" name="fileFolder<?=$tpl['openAll'] ? '�'.$tpl['issue_id']: ''?>"<?=$tpl['fileEntry']?>> <?=_("Dateiordner anlegen")?>
            <? } ?>
        </FONT>
        <BR/>
        <CENTER>
            <? if (!$tpl['openAll']) { ?>
            <? if ($tpl['issue_id']) { ?>
            <INPUT type="hidden" name="issue_id" value="<?=$tpl['issue_id']?>">
            <? } ?>
            <?= Button::create(_('�bernehmen'), $tpl['submit_name']) ?>
            <?= LinkButton::createCancel(_('Abbrechen'), URLHelper::getURL()) ?>
            <? } ?>
        </CENTER>
    </TD>
</TR>
<? } ?>
<?
    unset($tpl)
?>
