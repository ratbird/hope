<?
$width1 = 0;
$width2 = 0;
$cols = ceil(($settings['end'] - $settings['start'] + 1) * 3600 / $settings['step_week_group']) + 1;
$start = $settings['start'] * 3600;
$end = ($settings['end'] + 1) * 3600;
$wlength = sizeof($calendars[0]) - 1;
// add skip link
SkipLinks::addIndex(_('Wochenansicht'), 'main_content', 100);
?>
<table style="width: 100%">
    <tr>
        <td style="text-align: center; width: 15%;">
            <a href="<?= $controller->url_for('calendar/group/week/' . $this->range_id, array('atime' => $calendars[0][0]->getStart() + $settings['start'] * 3600 - 86400)) ?>">
                <?= Assets::img('icons/16/blue/arr_1left.png', tooltip2(_('eine Woche zurück'))) ?>
            </a>
        </td>
        <td style="width: 70%;" class="calhead">
            <?= sprintf(_('%s. Woche vom %s bis %s'), strftime('%V', $calendars[0][0]->getStart()), strftime('%x', $calendars[0][0]->getStart()), strftime('%x', $calendars[0][$wlength]->getEnd())); ?>
        </td>
        <td style="text-align: center; width: 15%;">
            <a href="<?= $controller->url_for('calendar/group/week/' . $this->range_id, array('atime' => mktime(12, 0, 0, date('n', $calendars[0][0]->getStart()), date('j', $calendars[0][0]->getStart()) + 7, date('Y', $calendars[0][0]->getStart())))) ?>">
                <?= Assets::img('icons/16/blue/arr_1right.png', tooltip2(_('eine Woche vor'))) ?>
            </a>
        </td>
    </tr>
</table>
<div style="overflow:auto; width:100%;">
    <table id="main_content" style="width: 100%;">
        <thead>
            <tr>
                <td width="<?= $width2 ?>%" class="precol1"> </td>
                <? $time = $calendars[0][0]->getStart(); ?>
                <? for ($i = 0; $i < $wlength; $i++) : ?>
                    <td colspan="<?= $cols ?>" style="text-align: center;" class="precol1w">
                        <a href="<?= $controller->url_for('calendar/group/day/' . $this->range_id, array('atime' => $time)) ?>" class="calhead">
                            <?= strftime('%a', $time) . ' ' . date('d', $time) ?>
                        </a>
                    </td>
                    <? $time += 86400; ?>
                <? endfor ?>
            </tr>
            <tr>
                <td width="<?= $width2 ?>%" class="precol1w" style="text-align: center;">
                    <?= _('Mitglied') ?>
                </td>
                <? foreach ($calendars[0] as $day) : ?>
                    <td class="precol1w" style="text-align: center; width: <?= $width1 ?>%;">
                        <a href="<?= $controller->url_for('calendar/group/edit/' . $this->range_id, array('atime' => $day->getStart(), 'dayevent' => '1')) ?>">
                            <?= Assets::img('icons/16/blue/schedule.png', tooltip2(_('Tagestermin'))) ?>
                        </a>
                    </td>
                    <? for ($i = $day->getStart() + $start; $i < $day->getStart() + $end; $i += 3600 * ceil($settings['step_week_group'] / 3600)) : ?>
                        <td colspan="<?= ceil(3600 / $settings['step_week_group']) ?>" class="precol2w" style="text-align: center;">
                            <a href="<?= $controller->url_for('calendar/group/edit/' . $this->range_id, array('atime' => $i)) ?>" class="calhead">
                                <?= (date('G', $i) < 10 ? '&nbsp;' . date('G', $i) . '&nbsp;' : date('G', $i)) ?>
                            </a>
                        </td>
                    <? endfor ?>
                <? endforeach ?>
            </tr>
        </thead>
        <tbody>
        <? foreach ($calendars as $user_calendar) : ?>
            <tr>
                <td style="width: <?= $width2 ?>%; white-space: nowrap;" class="month">
                    <span class="precol2">
                        <a class="calhead" href="<?= $controller->url_for('calendar/single/week/' . $user_calendar[0]->getRangeId(), array('atime' => $atime)) ?>">
                            <?= htmlReady($user_calendar[0]->havePermission(Calendar::PERMISSION_OWN) ? _('Eigener Kalender') : get_fullname($user_calendar[0]->getRangeId(), 'no_title_short')) ?>
                        </a>
                    </span>
                </td>
                <? $k = 1; ?>
                <? foreach ($user_calendar as $day) : ?>
                    <? // emphesize the current day if $compact is FALSE (this means week-view)
                    if (date('Ymd', $day->getStart()) == date('Ymd')) {
                        $css_class = 'celltoday';
                    } else {
                        if ($k % 2) {
                            $css_class = 'lightmonth';
                        } else {
                            $css_class = 'month';
                        }
                    }
                    $k++;
                    $adapted = $day->adapt_events($start, $end, $settings['step_week_group']);

                    // display day events
                    $js_events = array(); ?>
                        <? for ($i = 0; $i < sizeof($adapted['day_events']); $i++) : ?>
                            <? $js_events[] = $day->events[$adapted['day_map'][$i]]; ?>
                        <? endfor; ?>

                        <? if (sizeof($js_events)) : ?>
                            <td data-tooltip="" style="text-align: right; width: <?= $width1 ?>%" class="<?= $css_class ?> calendar-group-events">
                                <?= $this->render_partial('calendar/group/_tooltip', array('calendar' => $day, 'events' => $js_events)) ?>
                        <? else : ?>
                            <td style="text-align: right; width: <?= $width1 ?>%" class="<?= $css_class ?>">
                        <? endif ?>
                        <? if ($day->havePermission(Calendar::PERMISSION_WRITABLE)) : ?>
                            <a href="<?= $controller->url_for('calendar/single/edit/' . $day->getRangeId(), array('atime' => $atime, 'dayevent' => '1')) ?>">
                                <?= Assets::img('calplus.gif', tooltip2(_('neuer Tagestermin'))) ?>
                            </a>
                        <? endif ?>
                    </td>

                    <? for ($i = $start + $day->getStart(); $i < $end + $day->getStart(); $i += $settings['step_week_group']) : ?>
                        <? $js_events = array(); ?>
                        <? for ($j = 0; $j < sizeof($adapted['events']); $j++) : ?>
                            <? if (($adapted['events'][$j]->getStart() <= $i && $adapted['events'][$j]->getEnd() > $i) || ($adapted['events'][$j]->getStart() > $i && $adapted['events'][$j]->getStart() < $i + $settings['step_week_group'])) : ?>
                                <? $js_events[] = $day->events[$adapted['map'][$j]]; ?>
                            <? endif ?>
                        <? endfor ?>

                        <? if (sizeof($js_events)) : ?>
                            <td data-tooltip="" style="width:<?= $width1 ?>%; text-align: right;" class="calendar-group-events">
                                <?= $this->render_partial('calendar/group/_tooltip', array('calendar' => $day, 'events' => $js_events)) ?>
                        <? else : ?>
                            <td style="width:<?= $width1 ?>%; text-align: right;" class="<?= $css_class ?>">
                        <? endif ?>
                        <? if ($day->havePermission(Calendar::PERMISSION_WRITABLE)) : ?>
                            <a href="<?= $controller->url_for('calendar/single/edit/' . $day->getRangeId(), array('atime' => $i)) ?>">
                                <?= Assets::img('calplus.gif', tooltip2(strftime(_('neuer Termin um %R Uhr'), $i))) ?>
                            </a>
                        <? endif ?>
                        </td>
                    <? endfor ?>
                <? endforeach ?>
            </tr>
        <? endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td style="width:<?= $width2 ?>%; text-align: center;" class="precol1"> </td>
                <? foreach ($calendars[0] as $day) : ?>
                    <td style="width:<?= $width1 ?>%; text-align: center;" class="precol1w">
                        <a href="<?= $controller->url_for('calendar/single/edit/' . $day->getRangeId(), array('atime' => $atime, 'dayevent' => '1')) ?>">
                            <?= Assets::img('icons/16/blue/schedule.png', tooltip2(_('Tagestermin'))) ?>
                        </a>
                    </td>
                    <? for ($i = $day->getStart() + $start; $i < $day->getStart() + $end; $i += 3600 * ceil($settings['step_week_group'] / 3600)) : ?>
                        <td colspan="<?= ceil(3600 / $settings['step_week_group']) ?>" class="precol2w" style="text-align: center;">
                            <a href="<?= $controller->url_for('calendar/single/edit/' . $day->getRangeId(), array('atime' => $i)) ?>" class="calhead">
                                <?= (date('G', $i) < 10 ? '&nbsp;' . date('G', $i) . '&nbsp;' : date('G', $i)) ?>
                            </a>
                        </td>
                    <? endfor ?>
                <? endforeach ?>
            </tr>
        </tfoot>
    </table>
</div>
