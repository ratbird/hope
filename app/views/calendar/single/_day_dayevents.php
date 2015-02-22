<? if (sizeof($em['day_events'])) : ?>
        <table border="0" cellpadding="0" cellspacing="0" width="100%">
            <? foreach ($em['day_events'] as $day_event) : ?>
                <tr>
                    <td class="calendar-category<?= $day_event->getCategory() ?>">
                        <? if ($day_event->getPermission() == Event::PERMISSION_CONFIDENTIAL) : ?>
                            <?= htmlReady($day_event->getTitle()) ?>
                        <? else : ?>
                            <a style="color:#fff; font-size:10px;" href="<?= $controller->url_for('', array('termin_id' => $day_event->getId(), 'atime' => $day_event->getStart())) ?>"><?= htmlReady($day_event->getTitle()) ?></a>
                        <? endif ?>
                    </td>
                    <? if ($calendar->havePermission(Calendar::PERMISSION_WRITABLE)) : ?>
                        <td style="width: 1%;" rowspan="<?= sizeof($em['day_events']) ?>">
                            <a data-dialog="size=auto" title="<?= strftime(_('Neuer Tagestermin am %x'), $atime_new) ?>" style="display: block; min-width: 11px;" href="<?= $controller->url_for('calendar/single/edit/' . $calendar->getRangeId(),  array('atime' => $calendar->getStart(), 'dayevent' => '1')) ?>">+</a>
                        </td>
                    <? endif; ?>
                </tr>
            <? endforeach ?>
        </table>
<? else : ?>
    <? if ($calendar->havePermission(Calendar::PERMISSION_WRITABLE)) : ?>
        <a data-dialog="size=auto" title="<?= strftime(_('Neuer Tagestermin am %x'), $atime_new) ?>" href="<?= $controller->url_for('calendar/single/edit/' . $calendar->getRangeId(),  array('atime' => $atime, 'dayevent' => '1')) ?>">+</a>
    <? endif; ?>
<? endif; ?>