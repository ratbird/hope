<?
# Lifter010: TODO

use Studip\Button, Studip\LinkButton;
?>
<? if (!$tpl['deleted']) : ?>
<TR style="height: 1.8em">
    <TD width="1%" align="right" valign="bottom" class="<?=$tpl['class']?>" nowrap="nowrap">
        <A href="<?= URLHelper::getLink('?cmd='. ($_SESSION['issue_open'][$tpl['sd_id']] ? 'close' : 'open') .'&open_close_id='. $tpl['sd_id'] .'#'. $tpl['sd_id'])?>">
            <?= Assets::img($_SESSION['issue_open'][$tpl['sd_id']]
                            ? 'images/icons/16/blue/arr_1down.png'
                            : 'images/icons/16/blue/arr_1right.png',
                            array('class' => 'middle')) ?>
        </A>
    </TD>
    <TD width="1%" align="right" valign="bottom" class="<?=$tpl['class']?>" nowrap="nowrap">
        <A name="<?=$tpl['sd_id']?>" />
        <?= Assets::img('icons/16/blue/date.png', array('class' => 'middle')) ?>
    </TD>
    <TD nowrap="nowrap" class="<?=$tpl['class']?>" valign="bottom">
        <A class="tree" href="<?= URLHelper::getLink('?cmd='. ($_SESSION['issue_open'][$tpl['sd_id']] ? 'close' : 'open') .'&open_close_id='. $tpl['sd_id'] .'#'. $tpl['sd_id'])?>">
                <i><?= htmlReady($tpl['art']) ?>:&nbsp;</i>
                <?=$tpl['date']?>&nbsp;
        </A>
    </TD>

    <td width="80%" nowrap="nowrap" class="<?=$tpl['class']?>" valign="bottom" align="left">
        <?=htmlReady(mila($tpl['theme_title']))?>
    </td>

    <td width="10%" nowrap="nowrap" class="<?=$tpl['class']?>" valign="bottom">
        <?=$tpl['room']?>
    </td>

    <td width="1%" class="<?=$tpl['class']?>" nowrap="nowrap" valign="bottom" align="right">
        <?=$tpl['calendar']?>
    </td>
</tr>
<? if ($_SESSION['issue_open'][$tpl['sd_id']] || $openAll) { ?>
<TR>
    <TD colspan="6" class="table_row_even" align="center">
        <? if (!$openAll) { ?>
        <FORM action="<?= URLHelper::getLink() ?>" method="post">
        <?= CSRFProtection::tokenTag() ?>
        <? } ?>
        <TABLE border="0" cellspacing="0" cellpadding="1" width="99%">
            <TR>
                <TD width="70%" class="table_row_even">
                    <FONT size="-1">
                        <B><?=("Thema:")?></B><br>
                        <INPUT type="text" name="theme_title<?=$openAll ? '§'.$tpl['sd_id']: ''?>" maxlength="255" size="50" value="<?= htmlReady($tpl['theme_title']) ?>" style="width: 98%"><br>
                        <B><?=_("Beschreibung:")?></B><br>
                        <textarea class="add_toolbar" name="theme_description<?=$openAll ? '§'.$tpl['sd_id']: ''?>" rows="5" cols="50" style="width: 98%"><?= htmlReady($tpl['theme_description']) ?></textarea><br>
                    </FONT>
                </TD>
                <TD class="table_row_even" valign="top" nowrap="nowrap">
                    <font size="-1">
                        <? if ($modules['forum'] || $modules['documents']) : ?>
                        <b><?=_("Verknüpfungen mit diesem Termin:")?></b><br>
                        <? foreach (PluginEngine::getPlugins('ForumModule') as $plugin) : ?>
                            <? if (get_class($plugin) == $forum_slot) : ?>
                                <? if ($tpl['issue_id'] && $plugin->getLinkToThread($tpl['issue_id'])) : ?>
                                    <?= Assets::img('icons/16/green/accept.png') ?>
                                    <?= _("Forenthema vorhanden") ?><br>
                                    <input type="hidden" name="forumFolder" value="on">
                                <? else : ?>
                                    <input type="checkbox" name="forumFolder<?= ($openAll ? '§'.$tpl['sd_id']: '') ?>">
                                    <?= _("Thema im Forum anlegen") ?><br>
                                <? endif ?>
                            <? endif ?>
                        <? endforeach ?>

                        <?
                        if ($modules['documents']) :
                            if ($tpl['fileEntry']) : ?>
                                <?= Assets::img('icons/16/green/accept.png') ?>
                                <?= _("Dateiordner vorhanden"); ?>
                                <input type="hidden" name="fileFolder" value="on">
                            <? else :
                                echo '<input type="checkbox" name="fileFolder'.($openAll ? '§'.$tpl['sd_id']: '').'"'.$tpl['fileEntry'].'> ';
                                echo _("Dateiordner anlegen");
                            endif;
                        endif;

                        echo '<br><br>';
                    endif; ?>
                        <b><?=_("Art des Termins")?>:</b> <?= htmlReady($tpl['art']) ?>
                    </font>
                <? if (!$cancelled_dates_locked): ?>
                    <p>
                        <a href="javascript:STUDIP.CancelDatesDialog.initialize('<?= UrlHelper::getScriptURL('dispatch.php/course/cancel_dates', array('termin_id' =>  $tpl['sd_id'])) ?>');">
                            <?= Assets::img('icons/16/blue/decline/date', array('class' => 'text-top')) ?>
                            <?= _('Termin ausfallen lassen') ?>
                        </a>
                    </p>
                <? endif; ?>
                </TD>
            </TR>
            <TR>
                <TD class="table_row_even" align="center" colspan="2">
                    <? if (!$openAll) { ?>
                    <? if ($tpl['issue_id']) { ?>
                    <INPUT type="hidden" name="issue_id" value="<?=$tpl['issue_id']?>">
                    <? } ?>
                    <INPUT type="hidden" name="singledate_id" value="<?=$tpl['sd_id']?>">
                    <?= Button::create(_('Übernehmen'), $tpl['submit_name']) ?>
                    <?= LinkButton::createCancel(_('Abbrechen'), URLHelper::getURL('', array('cmd' => 'close', 'open_close_id' => $tpl['sd_id']))) ?>
                    <? } ?>
                </TD>
            </TR>
        </TABLE>
        <? if (!$openAll) { ?></FORM> <? } ?>
    </TD>
</TR>
<?
}
?>
<? elseif ($tpl['comment']) : ?>
<tr style="height: 1.8em">
    <TD width="1%" align="right" valign="bottom" class="content_title_red" nowrap="nowrap">
        <?= Assets::img('icons/16/blue/arr_1right.png', array('class' => 'middle')) ?>
    </TD>
    <TD width="1%" align="right" valign="bottom" class="content_title_red" nowrap="nowrap">
        <A name="<?=$tpl['sd_id']?>" />
        <?= Assets::img('icons/16/blue/date.png', array('class' => 'middle')) ?>
    </TD>
    <TD nowrap="nowrap" class="content_title_red" valign="bottom">
                <i><?= htmlReady($tpl['art']) ?>:&nbsp;</i>
                <?=$tpl['date']?>&nbsp;
    </TD>

    <td width="80%" nowrap="nowrap" colspan="3" class="content_title_red" valign="bottom" align="left">
        <b><?=_("fällt aus")?></b>
        (<?=_("Kommentar")?>: <?=htmlready($tpl['comment'])?>)
    </td>
</tr>
<? endif ?>
<?
unset($tpl);
