<?php
    $is_exTermin =  $termin instanceof CourseExDate;
?>
<tr>
    <td>
        <label for="<?= htmlReady($termin->termin_id) ?>">
            <input class="<?= $class_ids ?>" type="checkbox" id="<?= htmlReady($termin->termin_id) ?>"
                   value="<?= htmlReady($termin->termin_id) ?>"
                   name="single_dates[]">
        </label>
    </td>

    <td class="<?= $termin->getRoom() !== null ? 'green' : 'red' ?>">
    <? if ($is_exTermin) : ?>
        <span class="is_ex_termin">
            <?= htmlReady($termin->getFullname()) ?>
        </span>
    <? else : ?>
        <a class="load-in-new-row <?= $termin->id == Request::get('singleDateID') ? 'load-in-new-row-open' : ''?>"
           href="<?= $controller->url_for('course/timesrooms/editDate/' . $termin->termin_id, $editParams) ?>">
                <?= htmlReady($termin->getFullname()) ?>
            </a>
    <? endif ?>
    </td>

    <td>
    <? if (count($termin->dozenten) > 0): ?>
        <ul class="list-unstyled list-csv <? if ($is_exTermin) echo 'is_ex_termin'; ?>">
        <? foreach ($termin->dozenten as $dozent) : ?>
            <li><?= $dozent instanceof User ? htmlReady($dozent->getFullname()) : '' ?></li>
        <? endforeach ?>
        </ul>
    <? endif; ?>
    </td>
    <td>
    <? if ($room_holiday = SemesterHoliday::isHoliday($termin->date,false)) : ?>
        <? $room_holiday = sprintf('<span class="is_ex_termin">(%s)</span>', htmlReady($room_holiday['name'])) ?>
    <? endif ?>

    <? if ($is_exTermin && ($comment = $termin->content)) : ?>
        <span class="is_ex_termin" style="font-style: italic"><?= _("(fällt aus)") ?></span>
        <?= tooltipIcon($termin->content, false) ?>
    <? elseif ($name = SemesterHoliday::isHoliday($termin->date, false)): ?>
        <span <?= $is_exTermin ?  'class="is_ex_termin"' : '' ?>>
            (<?= htmlReady($name['name']) ?>)
        </span>
    <? elseif ($room = $termin->getRoom()) : ?>
        <?= $room->getFormattedLink(true, true, true, 'view_schedule', 'no_nav', $termin->date, $room->getName()) ?>
        <?= $room_holiday['name'] ?: '' ?>
    <? elseif ($freeTextRoom = $termin->getRoomName() ) : ?>
        <?= sprintf('(%s)', htmlReady($freeTextRoom)) ?>
    <? else : ?>
        <?= _('Keine Raumangabe') ?>
        <?= $room_holiday ?: '' ?>
    <? endif ?>

    <? $room_request = RoomRequest::find(RoomRequest::existsByDate($termin->id, true))?>
    <? if (isset($room_request)) : ?>
        <? $msg_info = _('Für diesen Termin existiert eine Raumanfrage: ') . $room_request->getInfo() ?>
        <?= tooltipIcon($msg_info) ?>
    <? endif ?>
    </td>
    <td class="actions">
    <? if ($is_exTermin): ?>
        <a class="load-in-new-row"
           href="<?= $controller->url_for('course/timesrooms/cancel/'
                                          . $termin->termin_id . ($termin->metadate_id ? '/' . $termin->metadate_id : ''), $editParams) ?>">
            <?= Icon::create('edit', 'inactive', ['title' => _('Kommentar für diesen Termin bearbeiten')])->asImg() ?>
        </a>

        <? $warning = array() ?>
        <? $course_topic = CourseTopic::findByTermin_id($termin->id)?>
        <? if (!empty($course_topic)) : ?>
            <? if (Config::get()->RESOURCES_ENABLE_EXPERT_SCHEDULE_VIEW) : ?>
                <? $warning[] = _('Diesem Termin ist im Ablaufplan ein Thema zugeordnet.
                    Titel und Beschreibung des Themas bleiben erhalten und können in der Expertenansicht des Ablaufplans einem anderen Termin wieder zugeordnet werden.'); ?>
            <? else : ?>
                <? $warning[] = _('Diesem Termin ist ein Thema zugeordnet.'); ?>
            <? endif ?>
        <? endif ?>

        <? if (Config::get()->RESOURCES_ENABLE && $termin->getRoom()) : ?>
            <? $warning[] = _('Dieser Termin hat eine Raumbuchung, welche mit dem Termin gelöscht wird.'); ?>
        <? endif ?>
        <a <?= Request::isXhr() ? 'data-dialog="size=big"' : '' ?> <? !empty($warning) ? 'data-confirm="' . implode("\n", $warning) . '"' : '' ?>
            href="<?= $controller->url_for('course/timesrooms/undeleteSingle/' . $termin->termin_id, $editParams) ?>">
            <?= Icon::create('trash+decline', 'inactive', ['title' => _('Diesen Termin wiederherstellen')])->asImg() ?>
        </a>

    <? else: ?>

        <a class="load-in-new-row"
           href="<?= $controller->url_for('course/timesrooms/editDate/' . $termin->termin_id, $editParams) ?>">
            <?= Icon::create('edit', 'clickable', ['title' => _('Diesen Termin bearbeiten')])->asImg() ?>
        </a>
        <a data-confirm="<?= _('Wollen Sie diesen Termin wirklich löschen / ausfallen lassen?') ?>" <?= Request::isXhr() ? 'data-dialog="size=big"' : '' ?>
            href="<?= $controller->url_for('course/timesrooms/deleteSingle/' . $termin->termin_id, array('cycle_id' => $termin->metadate_id)) ?>" <? !empty($warning) ? 'data-confirm="' . implode("\n", $warning) . '"' : '' ?>>
            <?= Icon::create('trash', 'clickable', ['title' => _('Diesen Termin löschen / ausfallen lassen')])->asImg() ?>
        </a>

    <? endif; ?>
    </td>
</tr>
