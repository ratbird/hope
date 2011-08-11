<?
$step = $group_calendar->getUserSettings('step_day_group') / 3600;
// add one cell for day events
$cells = (($group_calendar->getUserSettings('end') - $group_calendar->getUserSettings('start') + 1) / $step) + 1;
$width1 = floor(90 / $cells);
$width2 = 10 + (90 - $width1 * $cells);
$start = $group_calendar->getUserSettings('start') * 3600;
$end = ($group_calendar->getUserSettings('end') + 1) * 3600;

// add skip link
SkipLinks::addIndex(_("Tagesansicht"), 'main_content', 100);
?>
<table id="main_content" class="steelgroup0" style="width:100%; table-layout:fixed;" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td align="center" width="10%" height="40">
            <a href="<?= URLHelper::getLink('', array('cmd' => 'showday', 'atime' => $group_calendar->view->getStart() + $calendar_user_control_data['start'] * 3600 - 86400)) ?>">
                <?= Assets::img('icons/16/blue/arr_1left.png', tooltip2(_("zurück"))) ?>
            </a>
        </td>
        <td class="calhead" width="80%" class="cal">
            <b><?= ldate($atime) ?>
            <? if ($hday = holiday($atime)) : ?>
                <br><?= $hday['name'] ?>
            <? endif ?>
            </b>
        </td>
        <td align="center" width="10%">
            <a href="<?= URLHelper::getLink('', array('cmd' => 'showday', 'atime' => $group_calendar->view->getStart() + $calendar_user_control_data['start'] * 3600  + 86400)) ?>">
                <?= Assets::img('icons/16/blue/arr_1right.png', tooltip2(_("vor"))) ?>
            </a>
        </td>
    </tr>
    <tr>
        <td colspan="3">
            <div style="overflow:auto; width:100%;">
            <table width="100%" border="0" cellpadding="2" cellspacing="1" align="center" class="steelgroup0">
                <? $time = mktime(0, 0, 0, date('n', $atime), date('j', $atime), date('Y', $atime)); ?>
                <? if ($step < 1) : $colsp = ' colspan="' . (1 / $step) . '"'; endif ?>
                <tr>
                    <td width="<?= $width2 ?>%" class="precol1w" nowrap="nowrap" align="center">
                        <?= _("Mitglied") ?>
                    </td>
                    <td width="<?= $width1 ?>%" class="precol1w" align="center">
                        <a href="<?= URLHelper::getLink('', array('cmd' => 'edit', 'atime' => $atime, 'cal_group' => $group_id, 'devent' => '1')) ?>">
                            <?= Assets::img('icons/16/blue/schedule.png', tooltip2(_("Tagestermin"))) ?>
                        </a>
                    </td>
                    <? for ($i = $time + $start; $i < $time + $end; $i += 3600 * ceil($step)) : ?>
                        <td<?= $colsp ?> class="precol1w" align="center">
                            <a href="<?= URLHelper::getLink('', array('cmd'=> 'edit', 'atime' => $i, 'cal_group' => $group_id)) ?>" class="calhead"><?= date('G', $i) ?></a>
                        </td>
                    <? endfor ?>
                </tr>
                <? while ($calendar = $group_calendar->nextCalendar()) : ?>
                    <? $adapted = adapt_events($calendar->view, $start, $end); ?>
                    <tr>
                        <td width="<?= $width2 ?>%" nowrap="nowrap" class="month">
                            <span class="precol2">
                                <a class="calhead" href="<?= URLHelper::getLink('', array('cmd' => 'showday', 'atime' => $atime, 'cal_select' => 'user.' . get_username($calendar->getUserId()))); ?>"><?= htmlReady(($calendar->checkPermission(CALENDAR_PERMISSION_OWN) ? _("Eigener Kalender") : get_fullname($calendar->getUserId(), 'no_title_short'))) ?></a>
                            </span>
                        </td>
                        <? // display day events
                        $js_events = array(); ?>
                        <? for ($i = 0; $i < sizeof($adapted['day_events']); $i++) :
                            $js_events[] = $calendar->view->events[$adapted['day_map'][$i]];
                        endfor; ?>
                        <? if (sizeof($js_events)) : ?>
                            <td width="<?= $width1 ?>%" class="lightmonth" align="right" style="background-image: url('<?= Assets::url('images/calendar/category5_small.jpg') ?>" <?/*= js_hover_group($js_events, $calendar->view->getStart(), $calendar->view->getEnd(), $calendar->getUserId()); */ ?>>
                        <? else : ?>
                            <td width="<?= $width1 ?>%" class="lightmonth" align="right">
                        <? endif ?>
                        <? if ($calendar->havePermission(CALENDAR_PERMISSION_WRITABLE)) : ?>
                                <a href="<?= URLHelper::getLink('', array('cmd' => 'edit', 'atime' => $atime, 'devent' => '1', 'cal_select' => get_username($calendar->getUserId()))) ?>">
                                    <?= Assets::img('calplus.gif', tooltip2(_("neuer Tagestermin"))) ?>
                                </a>
                            </td>
                        <? else : ?>
                            </td>
                        <? endif ?>
                        <? $k = 0;
                        for ($i = $start + $calendar->view->getStart(); $i < $end + $calendar->view->getStart(); $i += $group_calendar->getUserSettings('step_day_group')) :
                            if (!($i % ($group_calendar->getUserSettings('step_day') / $step))) {
                                $k++;
                            }
                            $js_events = array();
                            ?>

                            <? for ($j = 0; $j < sizeof($adapted['events']); $j++) : ?>
                                <? if (($adapted['events'][$j]->getStart() <= $i && $adapted['events'][$j]->getEnd() > $i) || ($adapted['events'][$j]->getStart() > $i && $adapted['events'][$j]->getStart() < $i + $group_calendar->getUserSettings('step_day_group'))) :
                                    $js_events[] = $calendar->view->events[$adapted['map'][$j]];
                                endif ?>
                            <? endfor ?>

                            <? if (sizeof($js_events)) : ?>
                                <td width="<?= $width1 ?>%" align="right" style="background-image: url('<?= Assets::url('/images/calendar/category5_small.jpg') ?>" <?/*= js_hover_group($js_events, $i, $i + $group_calendar->getUserSettings('step_day_group'), $calendar->getUserId()); */ ?>>
                            <? else: ?>
                                <td width="<?= $width1 ?>%" align="right" class="<?= (($k % 2) ? 'month' : 'lightmonth') ?>">
                            <? endif ?>

                            <? if ($calendar->havePermission(CALENDAR_PERMISSION_WRITABLE)) : ?>
                                <a href="<?= URLHelper::getLink('', array('cmd' => 'edit', 'atime' => $i, 'cal_select' => 'user.' . get_username($calendar->getUserId()))) ?>">
                                    <?= Assets::img('calplus.gif', tooltip2(strftime(_("neuer Termin um %R Uhr"), $i))) ?>
                                </a>
                            <? endif ?>
                        </td>
                        <? endfor ?>
                    </tr>
                <? endwhile ?>
                <tr>
                    <td width="<?= $width2 ?>%" class="precol1w" nowrap="nowrap" align="center">&nbsp;</td>
                    <td width="<?= $width1 ?>%" class="precol1w" align="center">
                        <a href="<?= URLHelper::getLink('', array('cmd' => 'edit', 'atime' => $atime, 'cal_group' => $group_id, 'devent' => '1')) ?>">
                            <?= Assets::img('icons/16/blue/schedule.png', tooltip2(_("Tagestermin"))) ?>
                        </a>
                    </td>
                    <? for ($i = $time + $start; $i < $time + $end; $i += 3600 * ceil($step)) : ?>
                        <td<?= $colsp ?> class="precol1w" align="center">
                            <a href="<?= URLHelper::getLink('', array('cmd' => 'edit', 'atime' => $i, 'cal_group' => $group_id)) ?>" class="calhead">
                                <?= date('G', $i) ?>
                            </a>
                        </td>
                    <? endfor ?>
                </tr>
            </table>
            </div>
        </td>
    </tr>
</table>