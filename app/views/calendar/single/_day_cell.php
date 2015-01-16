<? $link_notset = true ?>
<? if (!$em['term'][$row]) : ?>
<td class="calendar-day-edit" <?= ($em['max_cols'] > 0 ? ' colspan="' . ($em['max_cols'] + 1) . '"' : '') ?>>
    <? if ($calendar->havePermission(Calendar::PERMISSION_WRITABLE)) : ?>
    <a data-dialog="" href="<?= $controller->url_for('calendar/single/edit/' . $calendar->getRangeId(), array('atime' => $calendar->getStart() + $i * $settings['step_day'])) ?>">
        <img src="<?= Assets::image_path('calplus.gif') ?>"<?= tooltip(strftime(_("neuer Termin um %R Uhr"), $row * $settings['step_day'] + $start - 3600)) ?>>
    </a>
    <? endif ?>
</td>
<? $link_notset = false ?>
<? else : ?>
    <? for ($j = 0; $j < $em['colsp'][$row]; $j++) : ?>
        <? $event = $em['term'][$row][$j]; ?>
        <? $mapped_event = $calendar->events[$em['mapping'][$row][$j]]; ?>
        <? if (is_object($event)) : ?>
            <td data-tooltip=""<?= ($em['cspan'][$row][$j] > 1 ? ' colspan="' . $em['cspan'][$row][$j] . '"' : '') ?><?= ($em['rows'][$row][$j] > 1 ? ' rowspan="' . $em['rows'][$row][$j] . '"' : '') ?> class="calendar-category<?= $event->getCategory() ?> calendar-day-event">
                <? if ($em['rows'][$row][$j] > 1) : ?>
                <div>
                    <?= date('H.i-', $mapped_event->getStart()) . date('H.i', $mapped_event->getEnd()) ?>
                </div>
                <? endif ?>
                <div class="calendar-day-event-title">
                <? if ($event->getPermission() == Event::PERMISSION_CONFIDENTIAL) : ?>
                    <?= $event->getTitle() ?>
                <? else : ?>
                    <a data-dialog="" href="<?= $controller->url_for('calendar/single/edit/' . $calendar->getRangeId() . '/' . $event->event_id, array('atime' => ($calendar->getStart() + $event->getStart() % 86400), 'evtype' => $event->getType())) ?>" <?//= js_hover($mapped_event); ?>><?= $event->getTitle() ?></a>
                    <?= $this->render_partial('calendar/single/_tooltip', array('event' => $event)) ?>
                <? endif ?>
                </div>
            </td>
        <? elseif ($event == '#') : ?>
            <td class="<?= $style_cell ?>"<?= ($em['cspan'][$row][$j] > 1 ? ' colspan="' . $em['cspan'][$row][$j] . '"' : '') ?>>
                <span class="inday">&nbsp;</span>
            </td>
        <? elseif ($event == '') : ?>
            <td class="calendar-day-edit"<?= ($em['cspan'][$row][$j] > 1 ? ' colspan="' . $em['cspan'][$row][$j] . '"' : '') ?>>
                <? if ($calendar->havePermission(Calendar::PERMISSION_WRITABLE)) : ?>
                <a data-dialog="" href="<?= $controller->url_for('calendar/single/edit/' . $calendar->getRangeId(), array('atime' => $calendar->getStart() + $i * $settings['step_day'])) ?>">
                    <img src="<?= Assets::image_path('calplus.gif') ?>"<?= tooltip(strftime(_("neuer Termin um %R Uhr"), $row * $settings['step_day'] + $start - 3600)) ?>>
                </a>
                <? endif ?>
            </td>
            <? $link_notset = false; ?>
            <? break; ?>
        <? endif ?>
    <? endfor ?>
<? endif ?>
<? if ($link_notset) : ?>
    <td class="calendar-day-edit">
        <? if ($calendar->havePermission(Calendar::PERMISSION_WRITABLE)) : ?>
        <a data-dialog="" href="<?= $controller->url_for('calendar/single/edit/' . $calendar->getRangeId(), array('atime' => $calendar->getStart() + $i * $settings['step_day'])) ?>">
            <img src="<?= Assets::image_path('calplus.gif') ?>"<?= tooltip(strftime(_('neuer Termin um %R Uhr'), $row * $settings['step_day'] + $start - 3600)) ?>>
        </a>
        <? endif ?>
    </td>
<? endif ?>