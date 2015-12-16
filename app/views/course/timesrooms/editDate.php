<fieldset style="margin-top: 1ex">
    <legend><?= _('Zeitangaben') ?></legend>
    <label>
        <?= _('Datum') ?>
        <input class="has-date-picker" type="text" name="date"
               value="<?= $date->date ? strftime('%d.%m.%Y', $date->date) : '' ?>">
    </label>
    <label>
        <?= _('Startzeit') ?>
        <input class="has-time-picker" type="text" name="start_time"
               value="<?= $date->date ? strftime('%H:%M', $date->date) : '' ?>">
    </label>
    <label>
        <?= _('Endzeit') ?>
        <input class="has-time-picker" type="text" name="end_time"
               value="<?= $date->end_time ? strftime('%H:%M', $date->end_time) : '' ?>">
    </label>
    <label id="course_type">
        <?= _('Art') ?>
        <select name="course_type" id="course_type">
            <? foreach ($GLOBALS['TERMIN_TYP'] as $id => $value) : ?>
                <option value="<?= $id ?>"
                    <?= $date->date_typ == $id ? 'selected' : '' ?>>
                    <?= htmlReady($value['name']) ?>
                </option>
            <? endforeach; ?>
        </select>
    </label>
</fieldset>
<fieldset class="collapsed">
    <legend><?= _('Raumangaben') ?></legend>
    <? if (Config::get()->RESOURCES_ENABLE && $resList->numberOfRooms()) : ?>
        <label>
            <input style="display: inline;" type="radio" name="room" value="room"
                   id="room" <?= $date->room_assignment->resource_id ? 'checked' : '' ?>>

            <select name="room_sd" style="display: inline-block; margin-left: 40px" class="single_room">
                <option value=""><?= _('Wählen Sie einen Raum aus') ?></option>
                <? foreach ($resList->resources as $room_id => $room) : ?>
                    <option value="<?= $room_id ?>"
                        <?= $date->room_assignment->resource_id == $room_id ? 'selected' : '' ?>>
                        <?= $room ?>
                    </option>
                <? endforeach; ?>
            </select>
        </label>
    <? endif; ?>
    <label class="horizontal">
        <input type="radio" name="room" value="freetext" <?= $date->raum ? 'checked' : '' ?>
               style="display: inline">
        <input style="margin-left: 40px; display: inline-block" type="text"
               name="freeRoomText_sd"
               placeholder="<?= _('Freie Ortsangabe (keine Raumbuchung)') ?>"
               value="<?= $date->raum ? htmlReady($date->raum) : '' ?>">
    </label>

    <label>
        <input type="radio" name="room" style="display:inline;" value="noroom"
            <?= (!empty($date->room_assignment->resource_id) || !empty($date->raum) ? '' : 'checked') ?>>
        <span style="display: inline-block; margin-left: 40px"><?= _('Kein Raum') ?></span>
    </label>

</fieldset>

<? if (!empty($dozenten)) : ?>
    <fieldset class="collapsed">
        <legend><?= _('Durchführende Dozenten') ?></legend>

        <ul class="termin_related teachers">
            <? foreach ($dozenten as $related_person => $dozent) : ?>

                <? $related = false;
                if (in_array($related_person, $related_persons) !== false || empty($related_persons)) :
                    $related = true;
                endif ?>

                <li data-lecturerid="<?= $related_person ?>" <?= $related ? '' : 'style="display: none"' ?>>
                    <? $dozenten[$related_person]['hidden'] = $related ?>
                    <?= htmlReady(User::find($related_person)->getFullname()); ?>

                    <a href="javascript:" onClick="STUDIP.Raumzeit.removeLecturer('<?= $related_person ?>')">
                        <?= Assets::img('icons/16/blue/trash.png') ?>
                    </a>
                </li>
            <? endforeach ?>
        </ul>
        <input type="hidden" name="related_teachers" value="<?= implode(',', $related_persons) ?>"/>

        <label for="add_teacher">
            <span style="display: block">
                <?= _('Lehrenden auswählen') ?>
            </span>
            <select id="add_teacher" name="teachers" style="display: inline-block">
                <option value="none"><?= _('Dozent/in auswählen') ?></option>
                <? foreach ($dozenten as $dozent) : ?>
                    <option
                        value="<?= htmlReady($dozent['user_id']) ?>" <?= $dozent['hidden'] ? 'style="display: none"' : '' ?>>
                        <?= htmlReady($dozent['fullname']) ?>
                    </option>
                <? endforeach; ?>
            </select>
            <a href="javascript:" onClick="STUDIP.Raumzeit.addLecturer()"
               title="<?= _('Lehrenden hinzufügen') ?>">
                <?= Assets::img('icons/16/yellow/arr_2up.png') ?>
            </a>
        </label>
    </fieldset>
<? endif ?>

<? if (!empty($gruppen)) : ?>
    <fieldset>
        <legend><?= _('Beteiligte Gruppen') ?></legend>

        <ul class="termin_related groups">
            <? foreach ($gruppen as $index => $statusgruppe) : ?>
                <? $related = false ?>
                <? if (in_array($statusgruppe->getId(), $related_groups) || empty($related_groups)) : ?>
                    <? $related = true; ?>
                <? endif ?>
                <li data-groupid="<?= htmlReady($statusgruppe->getId()) ?>" <?= $related ? '' : 'style="display: none"' ?>>
                    <?= htmlReady($statusgruppe['name']) ?>
                    <a href="javascript:" onClick="STUDIP.Raumzeit.removeGroup('<?= $statusgruppe->getId() ?>')">
                        <?= Assets::img('icons/blue/trash') ?>
                    </a>
                </li>
            <? endforeach ?>
        </ul>

        <input type="hidden" name="related_statusgruppen" value="<?= implode(',', $related_groups) ?>">

        <label>
            <span style="display:block;">
                <?=_('Gruppe hinzufügen')?>
            </span>

            <select name="groups" style="display: inline-block">
                <option value="none"><?= _('Gruppen auswählen') ?></option>
                <? foreach ($gruppen as $gruppe) : ?>
                    <option value="<?= htmlReady($gruppe->getId()) ?>"
                            style="<?= in_array($gruppe->getId(), $related_groups) ? 'display: none;' : '' ?>">
                        <?= htmlReady($gruppe['name']) ?>
                    </option>
                <? endforeach ?>
            </select>
            <a href="javascript:" onClick="STUDIP.Raumzeit.addGroup()" title="<?= _('Gruppe hinzufügen') ?>">
                <?= Assets::img('icons/16/yellow/arr_2up.png') ?>
            </a>

        </label>
    </fieldset>
<? endif ?>

<footer>
    <?= Studip\Button::createAccept(_('Speichern'), 'save_dates',
        array('formaction' => $controller->url_for('course/timesrooms/saveDate/' . $date->termin_id)) + $attributes) ?>
    <?= Studip\LinkButton::create(_('Raumanfrage erstellen'), $controller->url_for('course/room_requests/edit/' . $course->id, 
            array_merge($params, array('origin' => 'course_timesrooms'))),
        array('data-dialog' => 'size=big')) ?>
</footer>
