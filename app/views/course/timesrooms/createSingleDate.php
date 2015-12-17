<form action="<?= $controller->url_for('course/timesrooms/saveSingleDate', $editParams) ?>" method="post"
      class="default" <?= Request::isXhr() ? 'data-dialog=size=big"' : '' ?>>
    <?= CSRFProtection::tokenTag() ?>
    <label>
        <?= _('Datum') ?>
        <input class="has-date-picker" type="text" name="date"
               value="<?= htmlReady(Request::get('date')) ?>" required>
    </label>

    <label>
        <?= _('Startzeit') ?>
        <input class="has-time-picker" type="text" name="start_time"
               value="<?= htmlReady(Request::get('start_time')) ?>" required>
    </label>


    <label>
        <?= _('Endzeit') ?>
        <input class="has-time-picker" type="text" name="end_time"
               value="<?= htmlReady(Request::get('end_time')) ?>" required>
    </label>

    <? if (Config::get()->RESOURCES_ENABLE) : ?>
        <label>
            <?= _('Raum') ?>
            <select name="room">
                <option value="nothing"><?= _('<em>Keinen</em> Raum buchen') ?></option>
                <? $resList->reset();
                if ($resList->numberOfRooms()) : ?>
                    <? while ($res = $resList->next()) : ?>
                        <option
                            value="<?= $res['resource_id'] ?>" <?= in_array($res['resource_id'], Request::getArray('room')) ? 'selected' : '' ?>>
                            <?= my_substr(htmlReady($res["name"]), 0, 30) ?> <?= $seats[$res['resource_id']] ? '(' . $seats[$res['resource_id']] . ' ' . _('Sitzplätze') . ')' : '' ?>
                        </option>
                    <? endwhile ?>
                <? endif ?>
            </select>
            <a href="#" class="bookable_rooms_action" title="<?= _('Nur buchbare Räume anzeigen') ?>">
                <?= Icon::create('room-clear', 'clickable')->asImg(16) ?>
            </a>
        </label>
    <? endif ?>

    <label for="freeRoomText">
        <?= _('Freie Ortsangabe') ?>
        <input value="<?= htmlReady(Request::get('freeRoomText')) ?>" id="freeRoomText"
               name="freeRoomText" type="text" maxlength="255">
        <? if (Config::get()->RESOURCES_ENABLE) : ?>
            <small style="display: block"><?= _('(führt <em>nicht</em> zu einer Raumbuchung)') ?></small>
        <? endif ?>
    </label>

    <? if (count($teachers)) : ?>
        <label for="related_teachers"><?= _('Durchführende Lehrende') ?>
            <select id="related_teachers" name="related_teachers[]" multiple class="multiple">
                <? foreach ($teachers as $dozent) : ?>
                    <option <?= in_array($dozent['user_id'], Request::getArray('related_teachers')) ? 'selected' : '' ?>
                        value="<?= $dozent['user_id'] ?>"><?= htmlReady($dozent['fullname']) ?></option>
                <? endforeach; ?>
            </select>
        </label>
    <? endif; ?>


    <? if (count($groups) > 0) : ?>
        <label for="related_statusgruppen"><?= _('Beteiligte Gruppen') ?>
            <select id="related_statusgruppen" name="related_statusgruppen[]" multiple class="multiple">
                <? foreach ($groups as $group) : ?>
                    <option <?= in_array($group->getId(), Request::getArray('related_statusgruppen')) ? 'selected' : '' ?>
                        value="<?= $group->getId() ?>"><?= htmlReady($group['name']) ?></option>
                <? endforeach; ?>
            </select>
        </label>
    <? endif; ?>

    <label for="dateType">
        <?= _('Art'); ?>
        <select id="dateType" name="dateType">
            <? foreach ($GLOBALS['TERMIN_TYP'] as $key => $val) : ?>
                <option <?= Request::get('dateType') == $key ? 'selected' : '' ?>
                    value="<?= $key ?>"><?= htmlReady($val['name']) ?></option>
            <? endforeach ?>
        </select>
    </label>
    <footer data-dialog-button>
        <?= Studip\Button::createAccept(_('Speichern'), 'save') ?>
        <? if (Request::get('fromDialog') == 'true') : ?>
            <?= Studip\LinkButton::create(_('Zurück zur Übersicht'), $controller->url_for('course/timesrooms/index'), array('data-dialog' => 'size=big')) ?>
        <? endif ?>
    </footer>
</form>
