<?
# Lifter010: TODO
use Studip\Button,
    Studip\LinkButton;
?>
<? if (!$tpl['deleted']) : ?>
<TR>
    <TD class="printcontent" colspan="9">
        <a name="<?=$tpl['sd_id']?>"></a>
        <TABLE cellpadding="2" cellspacing="0" border="0" width="100%">
            <TR>
                <TD style="min-width: 40px;white-space:nowrap" align="left" valign="top" class="<?=$tpl['class']?>">
                    <A name="<?=$tpl['sd_id']?>" href="<?= URLHelper::getLink('?cmd=close&open_close_id='. $tpl['sd_id'] .'#'. $tpl['sd_id']) ?>">
                        <?= Assets::img('icons/16/blue/arr_1down.png') ?>
                    </A>
                    <? if (!$_LOCKED) : ?>
                    <INPUT type="checkbox" name="singledate[]" value="<?=$tpl['sd_id']?>" checked>
                    <? endif ?>
                </TD>
                <TD width="39%" nowrap class="<?=$tpl['class']?>">
                    <FONT size="-1">
                        <INPUT type="text" id="day" name="day" maxlength="2" size="2" value="<?=$tpl['day']?>">.
                        <INPUT type="text" id="month" name="month" maxlength="2" size="2" value="<?=$tpl['month']?>">.
                        <INPUT type="text" id="year" name="year" maxlength="4" size="4" value="<?=$tpl['year']?>">
                        <B><?=_("von")?></B>
                        <INPUT type="text" id="start_stunde" name="start_stunde" maxlength="2" size="2" value="<?=$tpl['start_stunde']?>">:
                        <INPUT type="text" id="start_minute" name="start_minute" maxlength="2" size="2" value="<?=$tpl['start_minute']?>">
                        <B><?=_("bis")?></B>
                        <INPUT type="text" id="end_stunde" name="end_stunde" maxlength="2" size="2" value="<?=$tpl['end_stunde']?>">:
                        <INPUT type="text" id="end_minute" name="end_minute" maxlength="2" size="2" value="<?=$tpl['end_minute']?>">&nbsp;<?=_("Uhr")?>
                    </FONT>
                    <?=Termin_Eingabe_javascript(1,0,mktime(12,0,0,$tpl['month'],$tpl['day'],$tpl['year']),$tpl['start_stunde'],$tpl['start_minute'],$tpl['end_stunde'],$tpl['end_minute']);?>
                </TD>
                <TD width="45%" nowrap class="<?=$tpl['class']?>">
                  <FONT size="-1"<?=($tpl['class'] == 'steelred') ? ' color="#000000"' : ''?>>
                  <?=$tpl['room']?>
                </FONT>
                    <? if ($tpl['ausruf']) { ?>
                    <A href="javascript:;" onClick="alert('<?=jsReady($tpl['ausruf'], 'inline-single')?>')">
                        <?= Assets::img($tpl['symbol'], tooltip($tpl['ausruf']))?>
                    </A>
                    <? } ?>
                </TD>
                <TD width="5%" class="<?=$tpl['class']?>" align="right">
                    <A href="<?= URLHelper::getLink('?cmd=delete_singledate&sd_id='. $tpl['sd_id']
                        .'&cycle_id='. ($tpl['cycle_id'] ? $tpl['cycle_id'] : '')) ?>">
                        <IMG src="<?=$GLOBALS['ASSETS_URL']?>images/icons/16/blue/trash.png" border="0" align="absmiddle">
                    </A>
                </TD>
            </TR>
            <TR>
                <TD class="printcontent">&nbsp;</TD>
                <TD class="printcontent">
                    <b><?= _("Durchführende Dozenten:") ?></b>
                    <? if (count($tpl['related_persons']) !== count($dozenten)) : ?>
                    <? foreach ($tpl['related_persons'] as $key => $related_person) {
                        echo ($key > 0 ? ", " : "").htmlReady(get_fullname($related_person));
                    } ?>
                    <? else : ?>
                    <?= _("alle") ?>
                    <? endif ?>
                </TD>
                <TD class="printcontent">&nbsp;</TD>
            </TR>
            <TR>
                <TD class="printcontent">&nbsp;</TD>
                <TD class="printcontent" colspan="2" id="<?=$tpl['sd_id']?>">
                    <FONT size="-1">
                    <?
                        if ($GLOBALS['RESOURCES_ENABLE'] && $resList->numberOfRooms()) :
                            $resList->reset();
                            echo _("Raum:");
                    ?>
                    <?= Assets::img('icons/16/blue/room_clear.png', array('class' => 'bookable_rooms_action', 'title' => _("Nur buchbare Räume anzeigen"))) ?>
                    <SELECT name="room_sd">
                        <OPTION value="nochange" selected><?=_("keine &Auml;nderung")?></option>
                        <OPTION value="retreat"><?=_("Raumbuchung aufheben")?></option>
                        <OPTION value="nothing"><?=_("keine Buchung, nur Textangabe")?></option>
                        <?
                            while ($res = $resList->next()) :
                                echo '<OPTION value="'.$res['resource_id'].'">'.my_substr(htmlReady($res["name"]), 0, 30)."</OPTION>\n";
                            endwhile;
                        ?>
                    </SELECT>
                    <br>
                    <? endif; ?>
                    <?=_("freie Ortsangabe:")?>
                    <INPUT type="text" name="freeRoomText_sd" size="50" maxlength="255" value="<?=$tpl['freeRoomText']?>">
                    <?=$GLOBALS['RESOURCES_ENABLE'] ? _("(f&uuml;hrt <em>nicht</em> zu einer Raumbuchung)"): ''?><br>
                    <? if ($GLOBALS['RESOURCES_ENABLE'] && $GLOBALS['RESOURCES_ALLOW_ROOM_REQUESTS']) : ?>
                    <?=_("Raumanfrage")?>
                    <?= LinkButton::create(($tpl['room_request']) ? _('Bearbeiten') : _('Erstellen'),
                            URLHelper::getURL('dispatch.php/course/room_requests/edit/' .$tpl['seminar_id'], $tpl['room_request'] ? array('request_id' => $tpl['room_request']->getId()) : array('new_room_request_type' => 'date_' . $tpl['sd_id'])),
                            array('onClick' => "STUDIP.RoomRequestDialog.initialize(this.href.replace('edit','edit_dialog'));return false")) ?>
                    <? if ($tpl['room_request']) : ?>
                    <?=_("oder")?>
                    <?= LinkButton::create(_('Zurückziehen'), 
                            URLHelper::getURL('?cmd=removeRequest&cycle_id='. $tpl['cycle_id'] .'&singleDateID='. $tpl['sd_id'])) ?>
                    
                    <? endif ?>
                    <br>
                    <? endif ?>
                    </FONT>
                </TD>
                <TD class="printcontent" valign="top" colspan="2" align="right" nowrap>
                    <FONT size="-1">
                    <?=_("Art:")?>
                    <SELECT name="dateType">
                    <?
                    if (!$tpl['type']) $tpl['type'] = 1;
                    foreach ($TERMIN_TYP as $key => $val) {
                        echo '<OPTION value="'.$key.'"';
                        if ($tpl['type'] == $key) {
                            echo ' selected';
                        }
                        echo '>'.$val['name']."</OPTION>\n";
                    }
                    ?>
                    </SELECT>
                    </FONT>
                </TD>
            </TR>
            <TR>
                <TD align="center" class="printcontent" colspan="4" style="text-align: center">
                    <?= Button::createAccept(_('Übernehmen'), 'editSingleDate_button') ?>
                    <?= LinkButton::createCancel(_('Abbrechen'),
                            URLHelper::getURL('?cmd=close&open_close_id='. $tpl['sd_id'] .'#'. $tpl['sd_id'])) ?>
                    <INPUT type="hidden" name="cmd" value="doAddSingleDate">
                </TD>
            </TR>
        </TABLE>
        <INPUT type="hidden" name="cmd" value="editSingleDate">
        <INPUT type="hidden" name="singleDateID" value="<?=$tpl['sd_id']?>">
        <? if ($tpl['cycle_id']) { ?>
        <INPUT type="hidden" name="cycle_id" value="<?=$tpl['cycle_id']?>">
        <? } ?>
    </TD>
</TR>
<? else : ?>
<tr>
    <td class="printcontent" colspan="9">
        <a name="<?=$tpl['sd_id']?>"></a>
        <table cellpadding="2" cellspacing="0" border="0" width="100%">
            <tr>
                <td width="2%" align="left" valign="center" class="<?=$tpl['class']?>" nowrap="nowrap">
                    <a href="<?= URLHelper::getLink('?cmd=close&open_close_id='. $tpl['sd_id'] .'#'. $tpl['sd_id']) ?>">
                        <?= Assets::img('icons/16/blue/arr_1down.png', array('class' => 'text-top'))?>
                    </a>
                </td>
                <td width="43%" nowrap class="<?=$tpl['class']?>">
                    <font size="-1" color="#666666">
                        <?=$tpl['date']?>
                    </font>
                </td>
                <td width="30%" nowrap class="<?=$tpl['class']?>">
                    <font size="-1" color="#666666">
                        <?=$tpl['room']?>
                    </font>
                </td>
                <td width="20%" nowrap class="<?=$tpl['class']?>" align="right">
                    <? if (!$_LOCKED) { ?>
                        <a href="<?= URLHelper::getLink('?cmd=undelete_singledate&sd_id='. $tpl['sd_id']
                            .'&cycle_id='. ($tpl['cycle_id'] ? $tpl['cycle_id'] : '')) ?>">
                            <?= Assets::img('icons/16/grey/decline/trash.png', array('class' => 'text-top', 'title' => _("Termin wiederherstellen"))) ?>
                        </a>
                    <? } ?>
                </td>
            </tr>
            <tr>
                <td width="5%" valign="top" class="printcontent">
                </td>
                <td valign="top" class="printcontent" colspan="10">
                    <font size="-1">
                        <?=_("Der hier eingegebene Kommentar wird im Ablaufplan der Veranstaltung angezeigt.")?><br>
                        <br>
                        <?=_("Kommentar")?>: <input type="text" name="comment" size="50" value="<?=$tpl['comment']?>">
                    </font>
                </td>
            </tr>
            <tr>
                <td align="center" class="printcontent" colspan="4" style="text-align: center">
                    <?= Button::createAccept(_('Übernehmen')) ?>
                    <?= LinkButton::createCancel(_('Abbrechen'),
                            URLHelper::getURL('?cmd=close&open_close_id='. $tpl['sd_id'] .'#'. $tpl['sd_id'])) ?>
                </td>
            </tr>
        </table>
        <input type="hidden" name="cmd" value="editDeletedSingleDate">
        <input type="hidden" name="singleDateID" value="<?=$tpl['sd_id']?>">
        <? if ($tpl['cycle_id']) { ?>
        <input type="hidden" name="cycle_id" value="<?=$tpl['cycle_id']?>">
        <? } ?>
    </td>
</tr>
<? endif; ?>
