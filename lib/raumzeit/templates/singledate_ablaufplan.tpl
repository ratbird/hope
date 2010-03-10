<TR>
    <TD width="1%" align="right" valign="center" class="<?=$tpl['class']?>" nowrap="nowrap">
        <A name="<?=$tpl['sd_id']?>" />
        <A href="<?= URLHelper::getLink('?cmd='. ($issue_open[$tpl['sd_id']] ? 'close' : 'open') .'&open_close_id='. $tpl['sd_id'] .'#'. $tpl['sd_id'])?>">
            <IMG src="<?=$GLOBALS['ASSETS_URL']?>images/forumgrau<?=($issue_open[$tpl['sd_id']]) ? 'runt' : ''?>.gif" border="0">
        </A>
    </TD>
    <TD width="1%" align="right" valign="top" class="<?=$tpl['class']?>" nowrap="nowrap">
        <IMG src="<?=$GLOBALS['ASSETS_URL']?>images/termin-icon.gif" border="0" align="abstop">&nbsp;
    </TD>

    <TD nowrap="nowrap" class="<?=$tpl['class']?>" valign="bottom">
        <A class="tree" href="<?= URLHelper::getLink('?cmd='. ($issue_open[$tpl['sd_id']] ? 'close' : 'open') .'&open_close_id='. $tpl['sd_id'] .'#'. $tpl['sd_id'])?>">
            <FONT size="-1">
                <i><?=$tpl['art']?>:&nbsp;</i>
                <?=$tpl['date']?>&nbsp;&nbsp;&nbsp;&nbsp;
            </FONT>
        </A>
    </TD>

    <td width="80%" nowrap="nowrap" class="<?=$tpl['class']?>" valign="bottom" align="left">
        <font size="-1" color="#000000">
            <?=htmlReady(mila($tpl['theme_title']))?>
        </font>
    </td>

    <td width="10%" nowrap="nowrap" class="<?=$tpl['class']?>" valign="bottom">
        <font size="-1" color="#000000">
            <?=$tpl['room']?>
        </font>
    </td>

    <td width="1%" class="<?=$tpl['class']?>" nowrap="nowrap" valign="bottom" align="right">
        <?=$tpl['calendar']?>&nbsp;
    </td>
</tr>
<? if ($issue_open[$tpl['sd_id']] || $openAll) { ?>
<TR>
    <TD colspan="6" class="steel1" align="center">
        <? if (!$openAll) { ?><FORM action="<?= URLHelper::getLink() ?>" method="post"><? } ?>
        <TABLE border="0" cellspacing="0" cellpadding="1" width="99%">
            <TR>
                <TD width="70%" class="steel1">
                    <FONT size="-1">
                        <B><?=("Thema:")?></B><br>
                        <INPUT type="text" name="theme_title<?=$openAll ? '§'.$tpl['sd_id']: ''?>" maxlength="255" size="50" value="<?=$tpl['theme_title']?>" style="width: 98%"><br>
                        <B><?=_("Beschreibung:")?></B><br>
                        <TEXTAREA name="theme_description<?=$openAll ? '§'.$tpl['sd_id']: ''?>" rows="5" cols="50" style="width: 98%"><?=$tpl['theme_description']?></TEXTAREA><br>
                    </FONT>
                </TD>
                <TD class="steel1" valign="top" nowrap="nowrap">
                    <font size="-1">
                        <? if ($modules['forum'] || $modules['documents']) : ?>
                        <b><?=_("Verknüfpungen mit diesem Termin:")?></b><br>
                        <? 
                        if ($modules['forum']) :
                            if ($tpl['forumEntry']) :
                                echo _("Forenthema vorhanden").'<br>';
                                echo '<INPUT type="hidden" name="forumFolder" value="on">';
                            else : 
                                echo '<input type="checkbox" name="forumFolder'.($openAll ? '§'.$tpl['sd_id']: '').'"> ';
                                echo _("Thema im Forum anlegen"). '<br>';
                            endif;
                        endif;

                        if ($modules['documents']) :
                            if ($tpl['fileEntry']) :
                                echo _("Dateiordner vorhanden");
                                echo '<INPUT type="hidden" name="fileFolder" value="on">';
                            else :
                                echo '<input type="checkbox" name="fileFolder'.($openAll ? '§'.$tpl['sd_id']: '').'"'.$tpl['fileEntry'].'> ';
                                echo _("Dateiordner anlegen");
                            endif;
                        endif;

                        echo '<br><br>';
                    endif; ?>
                        <b><?=_("Art des Termins")?>:</b> <?=$tpl['art']?>
                    </font>
                </TD>
            </TR>
            <TR>
                <TD class="steel1" align="center" colspan="2">
                    <? if (!$openAll) { ?>
                    <? if ($tpl['issue_id']) { ?>
                    <INPUT type="hidden" name="issue_id" value="<?=$tpl['issue_id']?>">
                    <? } ?>
                    <INPUT type="hidden" name="singledate_id" value="<?=$tpl['sd_id']?>">
                    <INPUT type="image" <?=makebutton('uebernehmen', 'src')?> align="absmiddle" name="<?=$tpl['submit_name']?>">
                    <A href="<?= URLHelper::getLink('?cmd=close&open_close_id='. $tpl['sd_id'])?>">
                        <IMG <?=makebutton('abbrechen', 'src')?> border="0" align="absmiddle">
                    </A>
                    <? } ?>
                </TD>
            </TR>
        </TABLE>
        <? if (!$openAll) { ?></FORM> <? } ?>
    </TD>
</TR>
<?
}
unset($tpl);
