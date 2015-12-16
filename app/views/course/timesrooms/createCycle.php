<form action="<?= $controller->url_for('course/timesrooms/' . ($cycle->isNew() ? 'saveCycle' : 'editCycle/' . $cycle->id), $editParams) ?>"
    class="default" method="post"
    <?= Request::isXhr() ? 'data-dialog="size=big"' : '' ?>>
    <?= CSRFProtection::tokenTag() ?>

<? if ($has_bookings): ?>
    <?= MessageBox::error(_('Wenn Sie die regelmäßige Zeit auf %s ändern, verlieren Sie die Raumbuchungen für alle in der Zukunft liegenden Termine!'),
        array(_('Sind Sie sicher, dass Sie die regelmäßige Zeit ändern möchten?'))) ?>
<? endif; ?>

    <label>
        <?= _('Starttag') ?>
        <select name="day">
        <? foreach (array(1, 2, 3, 4, 5, 6, 0) as $d): ?>
            <option value="<?= $d ?>" <? if (Request::int('day', $cycle->weekday) === $d) echo 'selected'; ?>>
                <?= getWeekday($d, false) ?>
            </option>
        <? endforeach; ?>
        </select>
    </label>

    <label>
        <?= _('Startzeit') ?>
        <input class="has-time-picker" type="text" name="start_time"
               value="<?= htmlReady(Request::get('start_time', $cycle->start_time)) ?>"
               required>
    </label>

    <label>
        <?= _('Endzeit') ?>
        <input class="has-time-picker" type="text" name="end_time"
               value="<?= htmlReady(Request::get('end_time', $cycle->end_time)) ?>"
               required>
    </label>

    <label>
        <?= _('Beschreibung') ?>
        <input type="text" name="description"
               value="<?= Request::get('description', $cycle->description) ?>">
    </label>

    <label>
        <?= _('Turnus') ?>
        <select name="cycle">
            <option value="0" <? if (Request::int('cycle', $cycle->cycle) === 0) echo 'selected'; ?>>
                <?= _('Wöchentlich') ?>
            </option>
            <option value="1" <? if (Request::int('cycle', $cycle->cycle) === 1) echo 'selected'; ?>>
                <?= _('Zweiwöchentlich') ?>
            </option>
            <option value="2" <? if (Request::int('cycle', $cycle->cycle) === 2) echo 'selected'; ?>>
                <?= _('Dreiwöchentlich') ?>
            </option>
        </select>
    </label>

    <label>
        <?= _('Startwoche') ?>
        <select name="startWeek">
        <? foreach ($start_weeks as $value => $data): ?>
            <option value="<?= $value ?>" <? if (Request::get('startWeek', $cycle->week_offset) == $value) echo 'selected'; ?>>
                <?= htmlReady($data['text']) ?>
            </option>
        <? endforeach; ?>
        </select>
    </label>

    <label>
        <?= _('Endwoche') ?>
        <select name="endWeek">
            <option value="0"><?= _('Ganzes Semester') ?></option>
        <? foreach ($start_weeks as $value => $data): ?>
            <option value="<?= $value + 1 ?>" <? if (Request::get('endWeek', $cycle->end_offset) == $value + 1) echo 'selected'; ?>>
                <?= htmlReady($data['text']) ?>
            </option>
        <? endforeach; ?>
        </select>
    </label>

    <label>
        <?= _('SWS Dozent') ?>
        <input type="text" name="teacher_sws"
               value="<?= htmlReady(Request::get('teacher_sws', $cycle->sws)) ?>">
    </label>

    <footer data-dialog-button>
        <?= Studip\Button::createAccept(_('Speichern'), 'save') ?>
    <? if (Request::get('fromDialog') == 'true'): ?>
        <?= Studip\LinkButton::create(_('Zurück zur Übersicht'), $controller->url_for('course/timesrooms/index'), array('data-dialog' => 'size=big')) ?>
    <? endif; ?>
    </footer>
</form>
