<? if (!Request::isXhr()) : ?>
    <h1><?= _('Neuen Blocktermin anlegen') ?></h1>
<? endif ?>

<form <?= Request::isXhr() ? 'data-dialog="size=big"' : '' ?>
    class="default collapsable"
    action="<?= $controller->url_for('course/block_appointments/save/' . $course_id, $editParams) ?>"
    method="post">

    <fieldset>
        <legend><?= _('Die Veranstaltung findet in folgendem Zeitraum statt') ?></legend>
        <label for="block_appointments_start_day">
            <?= _('Startdatum') ?>
            <input type="text" class="size-m has-date-picker" id="block_appointments_start_day"
                   name="block_appointments_start_day" value="<?= $request['block_appointments_start_day'] ?>">
        </label>
        <label for="block_appointments_end_day">
            <?= _('Enddatum') ?>
            <input type="text" class="size-m has-date-picker" id="block_appointments_end_day"
                   name="block_appointments_end_day" value="<?= $request['block_appointments_end_day'] ?>">
        </label>
    </fieldset>

    <fieldset>
        <legend><?= _('Die Veranstaltung findet zu folgenden Zeiten statt') ?></legend>
        <label for="block_appointments_start_time">
            <?= _('Startzeit') ?>
            <input type="text" class="size-m has-time-picker" id="block_appointments_start_time"
                   name="block_appointments_start_time" value="<?= $request['block_appointments_start_time'] ?>">
        </label>

        <label for="block_appointments_end_time">
            <?= _('Endzeit') ?>
            <input type="text" class="size-m has-time-picker" id="block_appointments_end_time"
                   name="block_appointments_end_time" value="<?= $request['block_appointments_end_time'] ?>">
        </label>

    </fieldset>

    <fieldset class="collapsed">
        <legend><?= _('Weitere Daten') ?></legend>
        <label for="block_appointments_termin_typ">
            <?= _('Art der Termine') ?>
            <select clas="size-l" name="block_appointments_termin_typ" id="block_appointments_termin_typ">
                <? foreach ($GLOBALS['TERMIN_TYP'] as $key => $value) : ?>
                    <option
                        value="<?= $key ?>" <?= $request['block_appointments_termin_typ'] == $key ? 'selected' : '' ?>>
                        <?= htmlReady($value['name']) ?>
                    </option>
                <? endforeach ?>
            </select>
        </label>
        <label for="block_appointments_room_text">
            <?= _('Freie Ortsangabe') ?>
            <input type="text" name="block_appointments_room_text" id="block_appointments_room_text"
                   value="<?= $request['block_appointments_room_text'] ?>">
        </label>
    </fieldset>

    <fieldset class="collapsed">
        <legend><?= _('Mehrere Termine parallel anlegen') ?></legend>
        <label for="block_appointments_date_count">
            <?= _('Anzahl') ?>
            <select name="block_appointments_date_count" id="block_appointments_date_count" class="size-s">
                <? foreach (range(1, 5) as $day) : ?>
                    <option
                        value="<?= $day ?>" <?= $request['block_appointments_date_count'] == $day ? 'selected' : '' ?>><?= $day ?></option>
                <? endforeach ?>
            </select>
        </label>

    </fieldset>

    <fieldset class="collapsed" id="block_appointments_days">
        <legend><?= _('Die Veranstaltung findet an folgenden Tagen statt') ?></legend>
        <label for="block_appointments_days_0" class="horizontal" style="font-weight:normal">
            <input <?= !is_array($request['block_appointments_day']) ? '' : (in_array('everyday', $request['block_appointments_days']) ? 'checked ' : '') ?>
                class="block_appointments_days"
                name="block_appointments_days[]" id="block_appointments_days_0" type="checkbox" value="everyday">
            <?= _('Jeden Tag') ?>
        </label>

        <label for="block_appointments_days_1" class="horizontal" style="font-weight:normal">
            <input <?= !is_array($request['block_appointments_day']) ? '' : (in_array('everyday', $request['block_appointments_days']) ? 'checked ' : '') ?>
                class="block_appointments_days"
                name="block_appointments_days[]" id="block_appointments_days_1" type="checkbox" value="weekdays">
            <?= _('Mo-Fr') ?>
        </label>
        <? foreach (range(0, 6) as $d) : ?>
            <? $id = 2 + $d ?>
            <label for="block_appointments_days_<?= $id ?>" class="horizontal" style="font-weight: normal">
                <input <?= !is_array($request['block_appointments_day']) ? '' : (in_array('everyday', $request['block_appointments_days']) ? 'checked ' : '') ?>
                    class="block_appointments_days"
                    name="block_appointments_days[]" id="block_appointments_days_<?= $id ?>" type="checkbox"
                    value="<?= $d + 1 ?>">
                <?= strftime('%A', strtotime("+$d day", $start_ts)) ?>
            </label>
        <? endforeach ?>
    </fieldset>

    <footer data-dialog-button>
        <?= Studip\Button::createAccept(_('Speichern'), 'save') ?>
        <?= Studip\LinkButton::create(_('Zurück zur Übersicht'), $controller->url_for('course/timesrooms/index'), array('data-dialog' => 'size=big')) ?>
    </footer>
</form>