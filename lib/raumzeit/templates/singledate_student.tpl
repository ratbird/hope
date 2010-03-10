<? if (!$tpl['deleted']) : ?>
<TR>
    <TD width="1%" align="left" valign="top" bgcolor="<?=$tpl['aging_color']?>" class="<?=$tpl['class']?><?=($issue_open[$tpl['sd_id']] || $tpl['openall']) ? '3' : '2'?>" nowrap>
        <A name="<?=$tpl['sd_id']?>" />
        <A href="<?=URLHelper::getLink("?cmd=".($issue_open[$tpl['sd_id']] ? 'close' : 'open')."&open_close_id=".$tpl['sd_id']."#".$tpl['sd_id'])?>">
            &nbsp;<IMG src="<?=$GLOBALS['ASSETS_URL']?>images/forumgrau<?=($issue_open[$tpl['sd_id']] || $tpl['openall']) ? 'runt' : ''?>2.gif" border="0" align="abstop">
        </A>
    </TD>

    <TD width="1%" align="right" valign="top" class="<?=$tpl['class']?>" nowrap>
        <IMG src="<?=$GLOBALS['ASSETS_URL']?>images/termin-icon.gif" border="0" align="abstop">&nbsp;
    </TD>

    <td nowrap class="<?=$tpl['class']?>" valign="bottom">
        <a class="tree" href="<?=URLHelper::getLink("?cmd=".($issue_open[$tpl['sd_id']] ? 'close' : 'open')."&open_close_id=".$tpl['sd_id']."#".$tpl['sd_id'])?>">
            <font size="-1">
                <i><?=$tpl['art']?>:&nbsp;</i>
                <?=$tpl['date']?>&nbsp;&nbsp;&nbsp;&nbsp;
            </font>
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
    </font>

    <td width="1%" class="<?=$tpl['class']?>" valign="bottom" nowrap="nowrap">
    <? if ($tpl['forumCount'] > 0) :
            if ($tpl['forumCount'] == 1) $txt = _("%s Foreneintrag vorhanden"); else $txt = _("%s Foreinträge vorhanden");
    ?>
        <a href="<?=URLHelper::getLink("forum.php?open=".$tpl['issue_id']."&treeviewstartposting=&view=#anker")?>">
            <img src="<?=$GLOBALS['ASSETS_URL']?>images/icon-posting.gif" border="0" align="absbottom" <?=tooltip(sprintf($txt, $tpl['forumCount']))?>>
        </a>
    <? endif; ?>
    </td>

    <td width="1%" class="<?=$tpl['class']?>" valign="bottom" nowrap="nowrap">
    <? if ($tpl['fileCountAll'] > 0) : ?>
        <a href="<?=URLHelper::getLink("folder.php?open=".$tpl['folder_id']."&cmd=tree#anker")?>">
            <img src="<?=$GLOBALS['ASSETS_URL']?>images/icon-disc.gif" align="absmiddle" border="0" <?=tooltip(sprintf(_("%s Dokument(e) vorhanden"), $tpl['fileCountAll']))?>><?if ($tpl['fileCountAll'] > 1) :
                for ($i = 1; ($i < $tpl['fileCountAll'] && $i < 5); $i++) :
                    ?><img src="<?=$GLOBALS['ASSETS_URL']?>/images/file1b.gif" align="absmiddle" border="0" <?=tooltip(sprintf(_("%s Dokument(e) vorhanden"), $tpl['fileCountAll']))?>><?
                endfor;
            endif;
        ?></a>
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
                <B><?=($tpl['theme_title']) ? $tpl['theme_title'] : _("Keine Titel vorhanden.")?></B><BR/>
                <?=($tpl['theme_description']) ? $tpl['theme_description'] : _("Keine Beschreibung vorhanden.")?><BR/>
                <BR/>
                <B><?=_("Art des Termins:")?></B>&nbsp;<?=$tpl['art']?><BR/>
                <BR/>
                <? if ($tpl['additional_themes']) { ?>
                <U><?=_("Weitere Themen:")?></U><BR/>
                <?  foreach ($tpl['additional_themes'] as $val) { ?>
                    <B><?=$val['title']?></B><BR/>
                    <?=$val['desc']?><BR/>
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
        <img src="<?=$GLOBALS['ASSETS_URL']?>images/termin-icon.gif" border="0" align="abstop">&nbsp;
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
