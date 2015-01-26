<?
$step_day = $settings['step_day_group'];
$step = 3600 / $step_day;
// add one cell for day events
$cells = (($settings['end'] - $settings['start']) / (1 / $step)) + 2;
$width1 = floor(90 / $cells);
$width2 = 10 + (90 - $width1 * $cells);
$start = $settings['start'] * 3600;
$end = ($settings['end'] + 1) * 3600;
// add skip link
SkipLinks::addIndex(_('Tagesansicht'), 'main_content', 100);
?>
<table id="main_content" style="width:100%; table-layout:fixed;">
    <tr>
        <td align="center" width="10%" height="40">
            <a href="<?= $controller->url_for('calendar/group/day/' . $this->range_id, array('atime' => $calendars[0]->getStart() + $settings['start'] * 3600 - 86400)) ?>">
                <?= Assets::img('icons/16/blue/arr_1left.png', tooltip2(_('zurück'))) ?>
            </a>
        </td>
        <td class="calhead" width="80%">
            <b><?= strftime('%x', $atime) ?>
            <? if ($hday = holiday($atime)) : ?>
                <br><?= $hday['name'] ?>
            <? endif ?>
            </b>
        </td>
        <td align="center" width="10%">
            <a href="<?= $controller->url_for('calendar/group/day/' . $this->range_id, array('atime' => $calendars[0]->getStart() + $settings['start'] * 3600  + 86400)) ?>">
                <?= Assets::img('icons/16/blue/arr_1right.png', tooltip2(_('vor'))) ?>
            </a>
        </td>
    </tr>
    <tr>
        <td colspan="3">
            <div style="overflow:auto; width:100%;">
            <table style="width: 100%;">
                <? $time = mktime(0, 0, 0, date('n', $atime), date('j', $atime), date('Y', $atime)); ?>
                <? if ($step_day < 3600) : $colsp = ' colspan="' . $step . '"'; endif ?>
                <tr>
                    <td width="<?= $width2 ?>%" class="precol1w" nowrap="nowrap" align="center">
                        <?= _("Mitglied") ?>
                    </td>
                    <td width="<?= $width1 ?>%" class="precol1w" align="center">
                        <a href="<?= $controller->url_for('calendar/group/edit/' . $this->range_id, array('atime' => $atime, 'dayvent' => '1')) ?>">
                            <?= Assets::img('icons/16/blue/schedule.png', tooltip2(_('Tagestermin'))) ?>
                        </a>
                    </td>
                    <? for ($i = $time + $start; $i < $time + $end; $i += $step_day) : ?>
                        <? if (!($i % 3600)) : ?>
                        <td<?= $colsp ?> class="precol1w" align="center">
                            <a href="<?= $controller->url_for('calendar/group/edit/' . $this->range_id, array('atime' => $i)) ?>" class="calhead"><?= date('G', $i) ?></a>
                        </td>
                        <? endif ?>
                    <? endfor ?>
                </tr>
                <? foreach ($calendars as $calendar) : ?>
                    <? $adapted = $calendar->adapt_events($start, $end, $settings['step_day_group']); ?>
                    <tr>
                        <td width="<?= $width2 ?>%" nowrap="nowrap" class="month">
                            <span class="precol2">
                                <a class="calhead" href="<?= $controller->url_for('calendar/single/day/' . $calendar->getRangeId(), array('atime' => $atime,)); ?>">
                                    <?= htmlReady(($calendar->havePermission(Calendar::PERMISSION_OWN) ? _('Eigener Kalender') : get_fullname($calendar->getRangeId(), 'no_title_short'))) ?>
                                </a>
                            </span>
                        </td>
                        <? // display day events
                        $js_events = array(); ?>
                        <? for ($i = 0; $i < sizeof($adapted['day_events']); $i++) :
                            $js_events[] = $calendar->events[$adapted['day_map'][$i]];
                        endfor; ?>
                        <? if (sizeof($js_events)) : ?>
                            <td data-tooltip="" width="<?= $width1 ?>%" class="lightmonth calendar-group-events">
                                <?= $this->render_partial('calendar/group/_tooltip', array('calendar' => $calendar, 'events' => $js_events)) ?>
                        <? else : ?>
                            <td width="<?= $width1 ?>%" class="lightmonth" align="right">
                        <? endif ?>
                        <? if ($calendar->havePermission(Calendar::PERMISSION_WRITABLE)) : ?>
                                <a href="<?= $controller->url_for('calendar/group/edit/' . $calendar->getRangeId(), array('atime' => $atime, 'devent' => '1')) ?>">
                                    <?= Assets::img('calplus.gif', tooltip2(_('neuer Tagestermin'))) ?>
                                </a>
                            </td>
                        <? else : ?>
                            </td>
                        <? endif ?>
                        <? $k = 0;
                        for ($i = $start + $calendar->getStart(); $i < $end + $calendar->getStart(); $i += $step_day) :
                            if (!($i % 3600)) {
                                $k++;
                            }
                            $js_events = array();
                            ?>

                            <? for ($j = 0; $j < sizeof($adapted['events']); $j++) : ?>
                                <? if (($adapted['events'][$j]->getStart() <= $i && $adapted['events'][$j]->getEnd() > $i) || ($adapted['events'][$j]->getStart() > $i && $adapted['events'][$j]->getStart() < $i + $step_day)) :
                                    $js_events[] = $calendar->events[$adapted['map'][$j]];
                                endif ?>
                            <? endfor ?>

                            <? if (sizeof($js_events)) : ?>
                                <td data-tooltip="" width="<?= $width1 ?>%" class="calendar-group-events">
                                    <?= $this->render_partial('calendar/group/_tooltip', array('calendar' => $calendar, 'events' => $js_events)) ?>
                            <? else: ?>
                                <td width="<?= $width1 ?>%" class="<?= (($k % 2) ? 'month' : 'lightmonth') ?>">
                            <? endif ?>

                            <? if ($calendar->havePermission(Calendar::PERMISSION_WRITABLE)) : ?>
                                <a href="<?= $controller->url_for('calendar/single/day/' . $calendar->getRangeId(), array('atime' => $i)) ?>">
                                    <?= Assets::img('calplus.gif', tooltip2(strftime(_('neuer Termin um %R Uhr'), $i))) ?>
                                </a>
                            <? endif ?>
                        </td>
                        <? endfor ?>
                    </tr>
                <? endforeach; ?>
                <tr>
                    <td width="<?= $width2 ?>%" class="precol1w"> </td>
                    <td width="<?= $width1 ?>%" class="precol1w" style="text-align: center;">
                        <a href="<?= $controller->url_for('calendar/group/edit/' . $this->range_id, array('atime' => $atime, 'dayevent' => '1')) ?>">
                            <?= Assets::img('icons/16/blue/schedule.png', tooltip2(_('Tagestermin'))) ?>
                        </a>
                    </td>
                    <? for ($i = $time + $start; $i < $time + $end; $i += $step_day) : ?>
                        <? if (!($i % 3600)) : ?>
                        <td<?= $colsp ?> class="precol1w" style="text-align: center;">
                            <a href="<?= $controller->url_for('calendar/group/edit/' . $this->range_id, array('atime' => $i)) ?>" class="calhead">
                                <?= date('G', $i) ?>
                            </a>
                        </td>
                        <? endif ?>
                    <? endfor ?>
                </tr>
            </table>
            </div>
        </td>
    </tr>
</table>
