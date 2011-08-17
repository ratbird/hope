<? if ($em['day_events']) : ?>
        <table width="100%" border="0" cellpadding="0" cellspacing="0">
            <? foreach ($em['day_events'] as $day_event) : ?>
                <?
                if ($calendar->view instanceof DbCalendarDay) {
                    $title_length = 70;
                } else {
                    $title_length = ceil(125 / $calendar->view->getType());
                }
                ?>
                <? $cstyle = $day_event->getCategoryStyle($calendar->view instanceof DbCalendarDay ? 'big' : 'small') ?>
                <tr>
                    <td style="width:100%; height:20px; vertical-align:top; text-align:left; border:solid 1px <?= $cstyle['color'] ?>; background-image:url(<?= $cstyle['image'] ?>);">
                        <? if ($day_event->getPermission == Event::PERMISSION_CONFIDENTIAL) : ?>
                            <?= fit_title($day_event->getTitle(), 1, 1, $title_length); ?>
                        <? else : ?>
                            <? if (strtolower(get_class($day_event)) == 'seminarevent') : ?>
                            <a style="color:#fff; font-size:10px;" href="<?= URLHelper::getLink('', array('cmd' => 'edit', 'termin_id' => $day_event->getId(), 'atime' => $day_event->getStart(), 'evtype' => 'sem')) ?>"><?= fit_title($day_event->getTitle(), 1, 1, $title_length); ?></a>
                            <? elseif  (strtolower(get_class($day_event)) == 'seminarcalendarevent') : ?>
                            <a style="color:#fff; font-size:10px;" href="<?= URLHelper::getLink('', array('cmd' => 'edit', 'termin_id' => $day_event->getId(), 'atime' => $day_event->getStart(), 'evtype' => 'semcal')) ?>"><?= fit_title($day_event->getTitle(), 1, 1, $title_length); ?></a>
                            <? else : ?>
                            <a style="color:#fff; font-size:10px;" href="<?= URLHelper::getLink('', array('cmd' => 'edit', 'termin_id' => $day_event->getId(), 'atime' => $day_event->getStart())) ?>"><?= fit_title($day_event->getTitle(), 1, 1, $title_length); ?></a>
                            <? endif ?>
                        <? endif ?>
                    </td>
                </tr>
            <? endforeach ?>
        </table>
<? endif; ?>