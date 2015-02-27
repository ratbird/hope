<? if (sizeof($em['day_events'])) : ?>
    <td class="<?= $class_cell ?>" style="padding: 0px;" <?= (($em['max_cols'] > 0) ? ' colspan="' . ($em['max_cols']) . '"' : '') ?>>
        <table style="width: 100%; border-spacing: 0;">
        <? foreach ($em['day_events'] as $day_event) : ?>
            <tr>
                <? if ($day_event->getPermission() == Event::PERMISSION_CONFIDENTIAL) : ?>
                <td class="calendar-category<?= $day_event->getCategory() ?>">
                    <?= htmlReady($day_event->getTitle()) ?>
                </td>
                <? else : ?>
                <td onclick="STUDIP.Dialog.fromElement(jQuery(this).children('a').first(), {size: 'auto'}); return false;" class="calendar-category<?= $day_event->getCategory() ?>">
                    <a style="color:#fff;" data-dialog="size=auto" href="<?= $controller->url_for('calendar/single/edit/' . $calendar->getRangeId() . '/' . $day_event->event_id, array('isdayevent' => '1')) ?>"><?= htmlReady($day_event->getTitle()) ?></a>
                </td>
                <? endif; ?>
            </tr>
        <? endforeach ?>
        </table>
    </td>
    <? if ($calendar->havePermission(Calendar::PERMISSION_WRITABLE)) : ?>
    <td class="calendar-day-edit" onclick="STUDIP.Dialog.fromElement(jQuery(this).children('a').first(), {size: 'auto'}); return false;">
        <a data-dialog="size=auto" title="<?= strftime(_('Neuer Tagestermin am %x'), $atime) ?>" href="<?= $controller->url_for('calendar/single/edit/' . $calendar->getRangeId(),  array('atime' => $calendar->getStart(), 'isdayevent' => '1')) ?>">+</a>
    </td>
    <? endif; ?>
<? else : ?>
    <? if ($calendar->havePermission(Calendar::PERMISSION_WRITABLE)) : ?>
        <td class="calendar-day-edit <?= $class_cell ?>" <?= (($em['max_cols'] > 0) ? ' colspan="' . ($em['max_cols'] + 1) . '"' : '') ?>>
            <a data-dialog="size=auto" title="<?= strftime(_('Neuer Tagestermin am %x'), $atime) ?>" href="<?= $controller->url_for('calendar/single/edit/' . $calendar->getRangeId(),  array('atime' => $atime, 'isdayevent' => '1')) ?>">+</a>
        </td>
    <? else : ?>
        <td class="calendar-day-edit <?= $class_cell ?>" <?= (($em['max_cols'] > 0) ? ' colspan="' . ($em['max_cols'] + 1) . '"' : '') ?>></td>
    <? endif; ?>
<? endif; ?>