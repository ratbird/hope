<?
# Lifter010: TODO
?>
<? if (!$tpl['deleted']) : ?>
<tr>
    <TD width="1%" align="left" valign="top" bgcolor="<?=$tpl['aging_color']?>" class="<?=$tpl['class']?><?=($issue_open[$tpl['sd_id']] || $tpl['openall']) ? '3' : '2'?>" nowrap><A href="<?=URLHelper::getLink("?cmd=".($issue_open[$tpl['sd_id']] ? 'close' : 'open')."&open_close_id=".$tpl['sd_id']."#".$tpl['sd_id'])?>"><?=($issue_open[$tpl['sd_id']] || $tpl['openall']) ? Assets::img('forumgraurunt2.png') : Assets::img('forumgrau2.png') ?></A></TD>

    <TD width="1%" align="right" valign="bottom" class="<?=$tpl['class']?>" nowrap>
        <A name="<?=$tpl['sd_id']?>" />
        &nbsp;<?= Assets::img('icons/16/grey/date.png', array('class' => 'text-top')) ?>
    </TD>

    <td nowrap class="<?=$tpl['class']?>" valign="bottom">
        <a class="tree" href="<?=URLHelper::getLink("?cmd=".($issue_open[$tpl['sd_id']] ? 'close' : 'open')."&open_close_id=".$tpl['sd_id']."#".$tpl['sd_id'])?>">
                &nbsp;<i><?= htmlReady($tpl['art']) ?>:</i>
                <?=$tpl['date']?>&nbsp;&nbsp;&nbsp;&nbsp;
        </a>
    </td>

    <td width="80%" nowrap class="<?=$tpl['class']?>" valign="bottom" align="left">
        <font size="-1" color="#000000">
            <?=htmlReady(mila($tpl['theme_title']));?>
        </font>
    </td>

    <td width="10%" nowrap class="<?=$tpl['class']?>" valign="bottom" align="right">
        <font size="-1" color="#000000">
            <?=$tpl['room']?>&nbsp;&nbsp;
        </font>
    </td>

    <td width="1%" class="<?=$tpl['class']?>" valign="bottom" nowrap="nowrap">
    <? if ($tpl['forumCount'] > 0) :
            if ($tpl['forumCount'] == 1) $txt = _("%s Foreneintrag vorhanden"); else $txt = _("%s Foreinträge vorhanden");
    ?>
        <a href="<?=URLHelper::getLink("forum.php?open=".$tpl['issue_id']."&treeviewstartposting=&view=#anker")?>">
            <img src="<?=$GLOBALS['ASSETS_URL']?>images/icons/16/blue/forum.png" border="0" align="absbottom" <?=tooltip(sprintf($txt, $tpl['forumCount']))?>>
        </a>
    <? endif; ?>
    </td>

    <td width="1%" class="<?=$tpl['class']?>" valign="bottom" nowrap="nowrap">
    <? if ($tpl['fileCountAll'] > 0) : ?>
        <a href="<?=URLHelper::getLink("folder.php?open=".$tpl['folder_id']."&cmd=tree#anker")?>">
            <img src="<?=$GLOBALS['ASSETS_URL']?>images/icons/16/blue/download.png" align="absmiddle" border="0" <?=tooltip(sprintf(_("%s Dokument(e) vorhanden"), $tpl['fileCountAll']))?>>
        </a>
    <? endif; ?>
    </td>

    <? if ($tpl['calendar']) : ?>
    <td width="1%" class="<?=$tpl['class']?>" valign="bottom" nowrap="nowrap" align="right">
        <?=$tpl['calendar'].'&nbsp;'?>
    </td>
    <? endif ?>

</tr>
<? if ($issue_open[$tpl['sd_id']] || $tpl['openall']) { ?>
<TR>
    <TD colspan="8" class="steel1" align="left" style="padding-left: 10px">
            <FONT size="-1">
                <BR/>
                <B><?=($tpl['theme_title']) ? htmlReady($tpl['theme_title']) : _("Keine Titel vorhanden.")?></B><BR/>
                <?=($tpl['theme_description']) ? formatReady($tpl['theme_description']) : _("Keine Beschreibung vorhanden.")?><BR/>
                <BR/>
                <B><?=_("Art des Termins:")?></B>&nbsp;<?= htmlReady($tpl['art']) ?><BR/>
                <BR/>
                <B><?=_("Durchführende Dozenten:")?></B>
                    <? foreach ($tpl['related_persons'] as $key => $dozent_id) {
                        $key < 1 || print ",";
                        print " ".htmlReady(get_fullname($dozent_id));
                    }?><BR/>
                <BR/>
                <? if ($tpl['additional_themes']) { ?>
                <U><?=_("Weitere Themen:")?></U><BR/>
                <?  foreach ($tpl['additional_themes'] as $val) { ?>
                    <B><?= htmlReady($val['title']) ?></B><BR/>
                    <?= formatReady($val['desc'])?><BR/>
                    <BR/>
                <?  }
                    }
                ?>
            </FONT>
    </TD>
</TR>
<? } ?>
<? else:    // Gelöschter Termin... ?>
<tr>
    <td width="1%" align="right" valign="top" class="steelred" nowrap>
        <a name="<?=$tpl['sd_id']?>" />
        <!--<a href="<?=$PHP_SELF?>?cmd=<?=($issue_open[$tpl['sd_id']]) ? 'close' : 'open'?>&open_close_id=<?=$tpl['sd_id']?>#<?=$tpl['sd_id']?>">
            <img src="<?=$GLOBALS['ASSETS_URL']?>images/forumrot<?=($issue_open[$tpl['sd_id']]) ? 'runt' : '3'?>.gif" border="0" align="abstop">
        </a>-->
    </td>

    <td width="1%" align="right" valign="top" class="steelred" nowrap>
        <img src="<?=$GLOBALS['ASSETS_URL']?>images/icons/16/blue/date.png" border="0" class="text-bottom">&nbsp;
    </td>

    <td nowrap class="steelred" valign="bottom">
        <a class="tree" href="<?=URLHelper::getLink("?cmd=".(($issue_open[$tpl['sd_id']]) ? 'close' : 'open')."&open_close_id=".$tpl['sd_id']."#".$tpl['sd_id'])?>">
            <font size="-1">
                <?=$tpl['date']?>&nbsp;&nbsp;&nbsp;&nbsp;
            </font>
        </a>
    </td>

    <td width="80%" colspan="5" class="steelred" valign="bottom">
        <font size="-1" style="text-color: red">
            <b><?=_("Dieser Termin findet nicht statt!")?></b>
        <font>
        <font size="-1">
            &nbsp;(<?=_("Kommentar")?>: <?=$tpl['comment']?>)
        </font>
    </td>
</tr>
<?
endif;
unset($tpl);
