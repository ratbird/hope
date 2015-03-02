<? $link_notset = true ?>
<? $atime_new = $calendar->getStart() + $i * $step ?>
<? if (!$em['term'][$row]) : ?>
    <? if ($calendar->havePermission(Calendar::PERMISSION_WRITABLE)) : ?>
    <td onclick="STUDIP.Dialog.fromElement(jQuery(this).children('a').first(), {size: 'auto'}); return false;" class="calendar-day-edit <?= $class_cell ?>" <?= ($em['max_cols'] > 0 ? ' colspan="' . ($em['max_cols'] + 1) . '"' : '') ?>>
        <a data-dialog="size=auto" title="<?= strftime(_('Neuer Termin am %x, %R Uhr'), $atime_new) ?>" href="<?= $controller->url_for('calendar/single/edit/' . $calendar->getRangeId(), array('atime' => $atime_new)) ?>">+</a>
    </td>
    <? else : ?>
    <td class="calendar-day-edit <?= $class_cell ?>" <?= ($em['max_cols'] > 0 ? ' colspan="' . ($em['max_cols'] + 1) . '"' : '') ?>>
    </td>
    <? endif; ?>
<? $link_notset = false ?>
<? else : ?>
    <? for ($j = 0; $j < $em['colsp'][$row]; $j++) : ?>
        <? $event = $em['term'][$row][$j]; ?>
        <? $mapped_event = $calendar->events[$em['mapping'][$row][$j]]; ?>
        <? if (is_object($event)) : ?>
            <td onclick="STUDIP.Dialog.fromElement(jQuery(this).find('a').first(), {size: 'auto'}); return false;" data-tooltip=""<?= ($em['cspan'][$row][$j] > 1 ? ' colspan="' . $em['cspan'][$row][$j] . '"' : '') ?><?= ($em['rows'][$row][$j] > 1 ? ' rowspan="' . $em['rows'][$row][$j] . '"' : '') ?> class="calendar-category<?= $event->getCategory() ?> calendar-day-event">
                <? if ($em['rows'][$row][$j] > 1) : ?>
                <div>
                    <?= date('H.i-', $mapped_event->getStart()) . date('H.i', $mapped_event->getEnd()) ?>
                </div>
                <? endif ?>
                <div class="calendar-day-event-title">
                <? if ($event->getPermission() == Event::PERMISSION_CONFIDENTIAL) : ?>
                    <?= $event->getTitle() ?>
                <? else : ?>
                    <a title="<?= _('Termin bearbeiten') ?>" data-dialog="size=auto" href="<?= $controller->url_for('calendar/single/edit/' . $calendar->getRangeId() . '/' . $event->event_id, array('evtype' => $event->getType())) ?>"><?= $event->getTitle() ?></a>
                <? endif ?>
                    <?= $this->render_partial('calendar/single/_tooltip', array('event' => $event)) ?>
                </div>
            </td>
        <? elseif ($event == '#') : ?>
            <td class="<?= $class_cell ?>"<?= ($em['cspan'][$row][$j] > 1 ? ' colspan="' . $em['cspan'][$row][$j] . '"' : '') ?>>
                <span class="inday">&nbsp;</span>
            </td>
        <? elseif ($event == '') : ?>
            <? if ($calendar->havePermission(Calendar::PERMISSION_WRITABLE)) : ?>
                <td onclick="STUDIP.Dialog.fromElement(jQuery(this).children('a').first(), {size: 'auto'}); return false;" class="calendar-day-edit <?= $class_cell ?>"<?= ($em['cspan'][$row][$j] > 1 ? ' colspan="' . $em['cspan'][$row][$j] . '"' : '') ?>>
                    <a data-dialog="size=auto" title="<?= strftime(_('Neuer Termin am %x, %R Uhr'), $atime_new) ?>" href="<?= $controller->url_for('calendar/single/edit/' . $calendar->getRangeId(), array('atime' => $atime_new)) ?>">+</a>
                </td>
            <? else : ?>
                <td class="calendar-day-edit <?= $class_cell ?>"<?= ($em['cspan'][$row][$j] > 1 ? ' colspan="' . $em['cspan'][$row][$j] . '"' : '') ?>></td>
            <? endif ?>
            <? $link_notset = false; ?>
            <? break; ?>
        <? endif ?>
    <? endfor ?>
<? endif ?>
<? if ($link_notset) : ?>
    <? if ($calendar->havePermission(Calendar::PERMISSION_WRITABLE)) : ?>
        <td onclick="STUDIP.Dialog.fromElement(jQuery(this).children('a').first(), {size: 'auto'}); return false;" class="calendar-day-edit <?= $class_cell ?>">
            <a data-dialog="size=auto" title="<?= strftime(_('Neuer Termin am %x, %R Uhr'), $atime_new) ?>" href="<?= $controller->url_for('calendar/single/edit/' . $calendar->getRangeId(), array('atime' => $atime_new)) ?>">+</a>
        </td>
    <? else : ?>
        <td class="calendar-day-edit <?= $class_cell ?>"></td>
    <? endif; ?>
<? endif ?>