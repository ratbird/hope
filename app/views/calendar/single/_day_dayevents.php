<? if (sizeof($em['day_events'])) : ?>
        <table border="0" cellpadding="0" cellspacing="0" width="100%">
            <? foreach ($em['day_events'] as $day_event) : ?>
                <? $cstyle = $day_event->getCategoryStyle() ?>
                <tr>
                    <td style="height:20px; vertical-align:top; text-align:left; border:solid 1px <?= $cstyle['color'] ?>; background-image:url(<?= $cstyle['image'] ?>);">
                        <? if ($day_event->getPermission() == Event::PERMISSION_CONFIDENTIAL) : ?>
                            <?= htmlReady($day_event->getTitle()) ?>
                        <? else : ?>
                            <? if (strtolower(get_class($day_event)) == 'courseevent') : ?>
                            <a style="color:#fff; font-size:10px;" href="<?= $controller->url_for('', array('termin_id' => $day_event->getId(), 'atime' => $day_event->getStart(), 'evtype' => 'sem')) ?>"><?= htmlReady($day_event->getTitle()) ?></a>
                            <? elseif  (strtolower(get_class($day_event)) == 'seminarcalendarevent') : ?>
                            <a style="color:#fff; font-size:10px;" href="<?= $controller->url_for('', array('termin_id' => $day_event->getId(), 'atime' => $day_event->getStart(), 'evtype' => 'semcal')) ?>"><?= htmlReady($day_event->getTitle()) ?></a>
                            <? else : ?>
                            <a style="color:#fff; font-size:10px;" href="<?= $controller->url_for('', array('termin_id' => $day_event->getId(), 'atime' => $day_event->getStart())) ?>"><?= htmlReady($day_event->getTitle()) ?></a>
                            <? endif ?>
                        <? endif ?>
                    </td>
                    <? if ($calendar->havePermission(Calendar::PERMISSION_WRITABLE)) : ?>
                        <td style="width: 1%; vertical-align:top;" rowspan="<?= sizeof($em['day_events']) ?>">
                            <a style="display: block; min-width: 11px;" href="<?= $controller->url_for('',  array('atime' => $calendar->getStart(), 'devent' => '1')) ?>">
                                <img src="<?= Assets::image_path('calplus.gif') ?>"<?= tooltip(_('neuer Tagestermin')) ?>>
                            </a>
                        </td>
                    <? endif; ?>
                </tr>
            <? endforeach ?>
        </table>
<? else : ?>
    <? if ($calendar->havePermission(Calendar::PERMISSION_WRITABLE)) : ?>
        <a href="<?= $controller->url_for('',  array('atime' => $atime, 'devent' => '1')) ?>">
            <img src="<?= Assets::image_path('calplus.gif') ?>"<?= tooltip(_("neuer Tagestermin")) ?>>
        </a>
    <? endif; ?>
<? endif; ?>