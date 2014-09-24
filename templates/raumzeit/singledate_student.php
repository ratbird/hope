<?
# Lifter010: TODO
$groups = Statusgruppen::findBySeminar_id($_SESSION['SessionSeminar']);
?>
<? if (!$tpl['deleted']) : ?>
<tr class="dates_headline<?= ($issue_open[$tpl['sd_id']] || $tpl['openall'])? ' dates_opened' : ''?>">
    <? if (isset($last)) : ?>
    <td width="1%" <?= !$last ? 'style="background-image: url(\'assets/images/forumstrich.gif\'); background-repeat: repeat-y; border: 0;"' : '' ?>>
        <? if ($last) : ?>
        <?= Assets::img('forumstrich2.gif') ?>
        <? else : ?>
        <?= Assets::img('forumstrich3.gif') ?>
        <? endif ?>
    </td>
    <? endif ?>

    <td width="1%" align="left" valign="top" bgcolor="<?=$tpl['aging_color']?>" class="<?=$tpl['class']?><?=($issue_open[$tpl['sd_id']] || $tpl['openall']) ? '3' : '2'?>" nowrap>
        <a href="<?=URLHelper::getLink("?cmd=".($issue_open[$tpl['sd_id']] ? 'close' : 'open')."&open_close_id=".$tpl['sd_id']."#".$tpl['sd_id'])?>">
            <?=($issue_open[$tpl['sd_id']] || $tpl['openall']) ? Assets::img('forumgraurunt2.png') : Assets::img('forumgrau2.png') ?>
        </a>
    </td>

    <td id="<?=$tpl['sd_id']?>" width="1%" align="right" class="<?=$tpl['class']?>" nowrap>
        <?= Assets::img('icons/16/grey/date.png', array('class' => 'middle')) ?>
    </td>

    <td nowrap class="<?=$tpl['class']?>">
        <a class="tree" href="<?=URLHelper::getLink("?cmd=".($issue_open[$tpl['sd_id']] ? 'close' : 'open')."&open_close_id=".$tpl['sd_id']."#".$tpl['sd_id'])?>">
            <i><?= htmlReady($tpl['art']) ?>:</i>
            <?=$tpl['date']?>&nbsp;
            <? if (count($groups) > count($tpl['related_groups'])) : ?>
            (<? foreach ($tpl['related_groups'] as $key => $statusgruppe_id) {
                $key < 1 || print ", ";
                print htmlReady(Statusgruppen::find($statusgruppe_id)->name);
            }?>)
            <? endif ?>
        </a>
    </td>

    <td width="80%" nowrap class="<?=$tpl['class']?>" align="left">
        <?=htmlReady(mila($tpl['theme_title']));?>
    </td>

    <td width="10%" nowrap class="<?=$tpl['class']?>" align="right">
        <?=$tpl['room']?>
    </td>

    <td width="1%" class="<?=$tpl['class']?>" nowrap="nowrap" style="padding-left: 5px;">
    <? if ($tpl['issue_id']) :
        $forum_slot = $GLOBALS['SEM_CLASS'][$GLOBALS['SEM_TYPE'][$sem->status]['class']]->getSlotModule('forum');

        foreach (PluginEngine::getPlugins('ForumModule') as $plugin) :
            if (get_class($plugin) == $forum_slot) :
                if ($count = $plugin->getNumberOfPostingsForIssue($tpl['issue_id'])) : ?>
                <a href="<?= $plugin->getLinkToThread($tpl['issue_id']) ?>">
                    <?= Assets::img('icons/16/blue/forum.png', tooltip2(sprintf(_('%u Foreneinträge vorhanden'), $count))) ?>
                </a>
            <? endif;
            endif;
        endforeach;
    endif;
    ?>
    </td>

    <td width="1%" class="<?=$tpl['class']?>" nowrap="nowrap">
    <? if ($tpl['fileCountAll'] > 0) : ?>
        <a href="<?=URLHelper::getLink("folder.php?open=".$tpl['folder_id']."&cmd=tree#anker")?>">
            <?= Assets::img('icons/16/blue/download.png', tooltip2(sprintf(_('%u Dokument(e) vorhanden'), $tpl['fileCountAll']))) ?>
        </a>
    <? endif; ?>
    </td>

    <? if ($tpl['calendar']) : ?>
    <td width="1%" class="<?=$tpl['class']?>" nowrap="nowrap" align="right">
        <?=$tpl['calendar']?>
    </td>
    <? endif ?>

</tr>
<? if ($issue_open[$tpl['sd_id']] || $tpl['openall']) { ?>
<tr class="dates_content">
    <td colspan="8" class="table_row_even">

        <b><?=($tpl['theme_title']) ? htmlReady($tpl['theme_title']) : _("Keine Titel vorhanden.")?></b><BR/>
        <?=($tpl['theme_description']) ? formatReady($tpl['theme_description']) : _("Keine Beschreibung vorhanden.")?><BR/>
        <BR/>
        <B><?=_("Art des Termins:")?></B> <?= htmlReady($tpl['art']) ?><BR/>
        <BR/>
        <B><?=_("Durchführende Dozenten:")?></B>
            <? foreach ($tpl['related_persons'] as $key => $dozent_id) {
                $key < 1 || print ",";
                print " ".htmlReady(get_fullname($dozent_id));
            }?><BR/>
        <? if ($tpl['related_groups'] && count($tpl['related_groups'])) : ?>
        <BR/>
        <B><?=_("Beteiligte Gruppen")?>:</B>
            <? if (count($groups) > count($tpl['related_groups'])) : ?>
            <? foreach ($tpl['related_groups'] as $key => $statusgruppe_id) {
                $key < 1 || print ",";
                print " ".htmlReady(Statusgruppen::find($statusgruppe_id)->name);
            }?>
            <? else : ?>
            <?= _("alle Teilnehmer") ?>
            <? endif ?>
            <BR/>
        <? endif ?>
        <? if ($tpl['additional_themes']) { ?>
        <BR/>
        <?=_("Weitere Themen:")?><BR/>
        <?  foreach ($tpl['additional_themes'] as $val) { ?>
            <B><?= htmlReady($val['title']) ?></B><BR/>
            <?= formatReady($val['desc'])?><BR/>
            <BR/>
        <?  }
            }
        ?>
        <div style="text-align:center">
        <?
        if ($rechte && !$cancelled_dates_locked) {
            echo \Studip\LinkButton::create(_('Ausfallen lassen'), "javascript:STUDIP.CancelDatesDialog.initialize('".URLHelper::getScriptURL('dispatch.php/course/cancel_dates', array('termin_id' =>  $tpl['sd_id']))."');");
        }
        ?>
        </div>
    </td>
</tr>
<? } ?>
<? else:    // Gelöschter Termin... ?>
<tr style="height: 1.8em">
    <TD width="1%" align="right" valign="bottom" class="content_title_red" nowrap="nowrap">
        <?= Assets::img('icons/16/blue/arr_1right.png') ?>
    </TD>
    <TD width="1%" align="right" valign="bottom" class="content_title_red" nowrap="nowrap">
        <A name="<?=$tpl['sd_id']?>" />
        <?= Assets::img('icons/16/blue/date.png', array('class' => 'middle')) ?>
    </TD>
    <TD nowrap="nowrap" class="content_title_red" valign="bottom">
                <i><?= htmlReady($tpl['art']) ?>:&nbsp;</i>
                <?=$tpl['date']?>&nbsp;
    </TD>

    <td width="80%" nowrap="nowrap" colspan="5" class="content_title_red" valign="bottom" align="left">
        <b><?=_("fällt aus")?></b>
        (<?=_("Kommentar")?>: <?=htmlready($tpl['comment'])?>)
    </td>
</tr>
<?
endif;
unset($tpl);
