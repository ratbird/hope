<?
# Lifter010: TODO

use Studip\Button, Studip\LinkButton;
?>
<TR style="height: 1.8em">
    <TD width="1%" align="right" valign="bottom" class="<?=$tpl['class']?>" nowrap="nowrap">
        <A href="<?= URLHelper::getLink('?cmd='. ($_SESSION['issue_open'][$tpl['sd_id']] ? 'close' : 'open') .'&open_close_id='. $tpl['sd_id'] .'#'. $tpl['sd_id'])?>">
        <IMG class="middle" src="<?=$GLOBALS['ASSETS_URL'].(($_SESSION['issue_open'][$tpl['sd_id']]) ? 'images/icons/16/blue/arr_1down.png' : 'images/icons/16/blue/arr_1right.png')?>">
        </A>
    </TD>
    <TD width="1%" align="right" valign="bottom" class="<?=$tpl['class']?>" nowrap="nowrap">
        <A name="<?=$tpl['sd_id']?>" />
        <IMG src="<?=$GLOBALS['ASSETS_URL']?>images/icons/16/blue/date.png" class="middle">&nbsp;
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
                        <b><?=_("Art des Termins")?>:</b> <?= htmlReady($tpl['art']) ?>
                    </font>
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
unset($tpl);
