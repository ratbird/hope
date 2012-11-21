<?
# Lifter010: TODO
use Studip\Button,
    Studip\LinkButton;
?>
<tr>
    <? if ($tpl['cycle_id']) : ?>
    <td <?= (!$tpl['last_element']) ? 'style="background-image: url(\'assets/images/forumstrich.gif\')"' : '' ?>> &nbsp;</td>
    <? endif ?>

    <td class="printcontent" colspan="9" style="padding-left: 10px; padding-top: 0.5em;">
        <a name="<?=$tpl['sd_id']?>"></a>
        <? if ($tpl['deleted']) : ?>   
            <br>
            <?=_("Der hier eingegebene Kommentar wird im Ablaufplan und auf der Kurzinfo-Seite der Veranstaltung angezeigt.")?><br>
            <br>
            <b><?=_("Kommentar")?>:<br></b>
            <input type="text" name="comment" size="50" value="<?=$tpl['comment']?>">
            <br>
            <!--<input type="checkbox">Mail an alle Teilnehmenden verschicken<br>
            <br>-->
            
            <div style="text-align: center">
                <div class="button-group">
                    <?= Button::createAccept(_('Übernehmen'), 'editDeletedSingleDate') ?>
                    <?= LinkButton::createCancel(_('Abbrechen'),
                            URLHelper::getURL('?#' . $tpl['sd_id'])) ?>
                </div>
            </div>

            <input type="hidden" name="cmd" value="editDeletedSingleDate">
            <input type="hidden" name="singleDateID" value="<?=$tpl['sd_id']?>">
            <? if ($tpl['cycle_id']) : ?>
                <input type="hidden" name="cycle_id" value="<?=$tpl['cycle_id']?>">
            <? endif ?>
        <? else : ?>
            <div style="float: left; width: 49%">
                <b><?= _('Datum/Uhrzeit:') ?></b><br>
                <input type="text" id="day" name="day" maxlength="2" size="2" value="<?=$tpl['day']?>">.
                <input type="text" id="month" name="month" maxlength="2" size="2" value="<?=$tpl['month']?>">.
                <input type="text" id="year" name="year" maxlength="4" size="4" value="<?=$tpl['year']?>">
                
                <b><?=_("von")?></b>
                <input type="text" id="start_stunde" name="start_stunde" maxlength="2" size="2" value="<?=$tpl['start_stunde']?>">:
                <input type="text" id="start_minute" name="start_minute" maxlength="2" size="2" value="<?=$tpl['start_minute']?>">
                
                <b><?=_("bis")?></b>
                <input type="text" id="end_stunde" name="end_stunde" maxlength="2" size="2" value="<?=$tpl['end_stunde']?>">:
                <input type="text" id="end_minute" name="end_minute" maxlength="2" size="2" value="<?=$tpl['end_minute']?>">&nbsp;<?=_("Uhr")?>
                <?=Termin_Eingabe_javascript(1,0,mktime(12,0,0,$tpl['month'],$tpl['day'],$tpl['year']),$tpl['start_stunde'],$tpl['start_minute'],$tpl['end_stunde'],$tpl['end_minute']);?>
                <br>
                <br>
               
                <b><?=_("Art:")?></b>
                <select name="dateType">
                <?
                if (!$tpl['type']) $tpl['type'] = 1;
                foreach ($TERMIN_TYP as $key => $val) :
                    echo '<OPTION value="'.$key.'"';
                    if ($tpl['type'] == $key) :
                        echo ' selected';
                    endif;
                    echo '>'.$val['name']."</OPTION>\n";
                endforeach;
                ?>
                </select>
                <br><br>

                <b><?= _("Durchführende Dozenten:") ?></b><br>
                
				<? $dozenten = $sem->getMembers('dozent') ?>

				<ul class="teachers">
					<? foreach ($dozenten as $related_person => $dozent) : ?>

					<? $related = false; 
					if (in_array($related_person, $tpl['related_persons']) !== false) : 
						$related = true;
					endif ?>

					<li data-lecturerid="<?= $related_person ?>" <?= $related ? '' : 'style="display: none"'?>>
						<? $dozenten[$related_person]['hidden'] = $related ?>
						<?= htmlReady(get_fullname($related_person)); ?>
						
						<a href="javascript:" onClick="STUDIP.Raumzeit.removeLecturer('<?= $related_person ?>')" style="position: absolute; right: 5px;">
							<?= Assets::img('icons/16/blue/trash.png') ?>
						</a>
					</li>
					<? endforeach ?>
				</ul>

                <input type="hidden" name="related_teachers" value="<?= implode(',', $tpl['related_persons']) ?>">

                <select name="teachers" style="width: 300px">
					<option value="none"><?= _('-- Dozent/in auswählen --') ?></option>
                    <? foreach ($dozenten as $dozent) : ?>
                    <option value="<?= htmlReady($dozent['user_id']) ?>" <?= $dozent['hidden'] ? 'style="display: none"' : '' ?>>
                        <?= htmlReady($dozent['fullname']) ?>
                    </option>
                    <? endforeach ?>
                </select>
                
                <a href="javascript:" onClick="STUDIP.Raumzeit.addLecturer()" title="<?= _('DozentIn hinzufügen') ?>">
                    <?= Assets::img('icons/16/yellow/arr_2up.png') ?>
                </a>

                <!--
                <? if (count($tpl['related_persons']) !== count($dozenten)) : ?>
                <? foreach ($tpl['related_persons'] as $key => $related_person) {
                    echo ($key > 0 ? ", " : "").htmlReady(get_fullname($related_person));
                } ?>
                <? else : ?>
                <?= _("alle") ?>
                <? endif ?>-->
            </div>
            
            <div style="float: right; width: 49%">
                <b><?= _('Raumangaben:') ?></b><br>
                <? if ($GLOBALS['RESOURCES_ENABLE'] && $resList->numberOfRooms()) : ?>
                <? $resList->reset() ?>
				<label>
					<input type="radio" name="action" value="room" checked="checked">
					<?= _("Raum:"); ?>
				</label>

				<select name="room_sd" onFocus="jQuery('input[type=radio][name=action][value=room]').attr('checked', 'checked')">
					<option value="">-- kein Raum gebucht --</value>
					<? while ($res = $resList->next()) : ?>
						<option value="<?= $res['resource_id'] ?>" <?= $res['resource_id'] == $tpl['resource_id'] ? 'selected="selected"' : '' ?>>
							<?= my_substr(htmlReady($res["name"]), 0, 30) ?> <?= $seats[$res['resource_id']] ? '('. $seats[$res['resource_id']] .' '. _('Sitzplätze') .')' : '' ?>
						</option>
					<? endwhile; ?>
				</select>

                <?= Assets::img('icons/16/grey/room_clear.png', array('class' => 'bookable_rooms_action', 'title' => _("Nur buchbare Räume anzeigen"))) ?>
                
                <br>
                <br>

				<label>
					<input type="radio" name="action" value="freetext" <?= $tpl['freeRoomText'] ? 'checked="checked"' : '' ?>>
					<?= _('freie Ortsangabe (keine Raumbuchung):') ?><br>
				</label>
                <? else : ?>
                    <br>
                    <?= _('freie Ortsangabe:') ?><br>
				<? endif ?>

                <input type="text" name="freeRoomText_sd" maxlength="255" value="<?= $tpl['freeRoomText'] ?>" style="margin-left: 25px; width: 90%;"
					onFocus="jQuery('input[type=radio][name=action][value=freetext]').attr('checked', 'checked')">

				<? if ($GLOBALS['RESOURCES_ENABLE'] && $resList->numberOfRooms()) : ?>
					<br>
					<br>
					<label>
						<input type="radio" name="action" value="noroom" <?= !$tpl['freeRoomText'] && !$tpl['resource_id'] ? 'checked="checked"' : '' ?>>
						<?=_('kein Raum') ?>
					</label>
					<br>
				<? endif ?>
            </div>
            <br style="clear: both;"><br>
            
            <div style="text-align: center">
                <div class="button-group">
                    <?= Button::createAccept(_('Übernehmen'), 'editSingleDate_button') ?>
                    <?= LinkButton::createCancel(_('Abbrechen'),
                            URLHelper::getURL('?cycle_id=' . $tpl['cycle_id'] . '#' . $tpl['sd_id'])) ?>
                </div>
                <div class="button-group">
                <? if ($GLOBALS['RESOURCES_ENABLE'] && $GLOBALS['RESOURCES_ALLOW_ROOM_REQUESTS']) : ?>
                    <?= LinkButton::create(($tpl['room_request']) ? _('Raumanfrage bearbeiten') : _('Raumanfrage erstellen'),
                            URLHelper::getURL('dispatch.php/course/room_requests/edit/' .$tpl['seminar_id'], $tpl['room_request'] ? array('request_id' => $tpl['room_request']->getId()) : array('new_room_request_type' => 'date_' . $tpl['sd_id'])),
                            array('onClick' => "STUDIP.RoomRequestDialog.initialize(this.href.replace('edit','edit_dialog'));return false")) ?>
                    <? if ($tpl['room_request']) : ?>
                    <?= LinkButton::create(_('Raumanfrage zurückziehen'), 
                            URLHelper::getURL('?cmd=removeRequest&cycle_id='. $tpl['cycle_id'] .'&singleDateID='. $tpl['sd_id'])) ?>
                    <? endif ?>
                <? endif ?>
                </div>
            </div>
            
            <input type="hidden" name="cmd" value="editSingleDate">
            <input type="hidden" name="singleDateID" value="<?=$tpl['sd_id']?>">

            <? if ($tpl['cycle_id']) : ?>
                <input type="hidden" name="cycle_id" value="<?=$tpl['cycle_id']?>">
            <? endif ?>

        <? endif ?>
    </td>
</tr>
