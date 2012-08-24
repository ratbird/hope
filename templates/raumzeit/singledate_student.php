<?
# Lifter010: TODO
?>
<? if (!$tpl['deleted']) : ?>
<tr class="dates_headline<?= ($issue_open[$tpl['sd_id']] || $tpl['openall'])? ' dates_opened' : ''?>">
    <td width="1%" align="left" valign="top" bgcolor="<?=$tpl['aging_color']?>" class="<?=$tpl['class']?><?=($issue_open[$tpl['sd_id']] || $tpl['openall']) ? '3' : '2'?>" nowrap>
        <a href="<?=URLHelper::getLink("?cmd=".($issue_open[$tpl['sd_id']] ? 'close' : 'open')."&open_close_id=".$tpl['sd_id']."#".$tpl['sd_id'])?>">
            <?=($issue_open[$tpl['sd_id']] || $tpl['openall']) ? Assets::img('forumgraurunt2.png') : Assets::img('forumgrau2.png') ?>
        </a>
    </td>

    <td id=""<?=$tpl['sd_id']?>" width="1%" align="right" class="<?=$tpl['class']?>" nowrap>
        <?= Assets::img('icons/16/grey/date.png', array('class' => 'middle')) ?>
    </td>

    <td nowrap class="<?=$tpl['class']?>">
        <a class="tree" href="<?=URLHelper::getLink("?cmd=".($issue_open[$tpl['sd_id']] ? 'close' : 'open')."&open_close_id=".$tpl['sd_id']."#".$tpl['sd_id'])?>">
                 <i><?= htmlReady($tpl['art']) ?>:</i>
                <?=$tpl['date']?>
        </a>
    </td>

    <td width="80%" nowrap class="<?=$tpl['class']?>" align="left">
        <?=htmlReady(mila($tpl['theme_title']));?>
    </td>

    <td width="10%" nowrap class="<?=$tpl['class']?>" align="right">
        <?=$tpl['room']?>
    </td>

    <td width="1%" class="<?=$tpl['class']?>" nowrap="nowrap">
    <? if ($tpl['forumCount'] > 0) :
            if ($tpl['forumCount'] == 1) $txt = _("%s Foreneintrag vorhanden"); else $txt = _("%s Foreinträge vorhanden");
    ?>
        <a href="<?=URLHelper::getLink("forum.php?open=".$tpl['issue_id']."&treeviewstartposting=&view=#anker")?>">
            <img src="<?=$GLOBALS['ASSETS_URL']?>images/icons/16/blue/forum.png" <?=tooltip(sprintf($txt, $tpl['forumCount']))?>>
        </a>
    <? endif; ?>
    </td>

    <td width="1%" class="<?=$tpl['class']?>" nowrap="nowrap">
    <? if ($tpl['fileCountAll'] > 0) : ?>
        <a href="<?=URLHelper::getLink("folder.php?open=".$tpl['folder_id']."&cmd=tree#anker")?>">
            <img src="<?=$GLOBALS['ASSETS_URL']?>images/icons/16/blue/download.png" <?=tooltip(sprintf(_("%s Dokument(e) vorhanden"), $tpl['fileCountAll']))?>>
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
    </td>
</tr>
<? } ?>
<? else:    // Gelöschter Termin... ?>
<tr>
    <td id="<?=$tpl['sd_id']?>" width="1%" align="right" valign="top" class="content_title_red" nowrap>
    </td>

    <td width="1%" align="right" valign="top" class="content_title_red" nowrap>
        <img src="<?=$GLOBALS['ASSETS_URL']?>images/icons/16/blue/date.png" class="middle">
    </td>

    <td nowrap class="content_title_red">
        <a class="tree" href="<?=URLHelper::getLink("?cmd=".(($issue_open[$tpl['sd_id']]) ? 'close' : 'open')."&open_close_id=".$tpl['sd_id']."#".$tpl['sd_id'])?>">
            <?=$tpl['date']?>
        </a>
    </td>

    <td width="80%" colspan="5" class="content_title_red">
        <span style="text-color: red">
            <b><?=_("Dieser Termin findet nicht statt!")?></b>
        </span>
        (<?=_("Kommentar")?>: <?=$tpl['comment']?>)
    </td>
</tr>
<?
endif;
unset($tpl);
