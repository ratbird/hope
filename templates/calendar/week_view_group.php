<?
$width1 = 0;
$width2 = 0;
$cols = ceil(($end_time - $start_time + 1) * 3600 / $calendar->getUserSettings('step_week_group')) + 1;
$start = $start_time * 3600;
$end = ($end_time + 1) * 3600;

// add skip link
SkipLinks::addIndex(_("Wochenansicht"), 'main_content', 100);
?>
<!-- CALENDAR GROUP VIEW WEEK -->
<table id="main_content" border="0" width="1%" cellspacing="1" cellpadding="1" class="steelgroup0">
    <tr>
        <td colspan="<?= ($cols * $calendar->view->getType() + 1) ?>">
            <table width="100%" border="0" cellpadding="2" cellspacing="0" align="center" class="steelgroup0">
                <tr>
                    <td align="center" width="15%">
                        <a href="<?= URLHelper::getLink($_SERVER['PHP_SELF'], array('cmd' => 'showweek', 'atime' => $calendar->view->getStart() + $GLOBALS['calendar_user_control_data']['start'] * 3600 - 86400)) ?>">
                            <?= Assets::img('icons/16/blue/arr_2left.png', tooltip2(_("eine Woche zurück"))) ?>
                        </a>
                    </td>
                    <td width="70%" class="calhead">
                        <?= sprintf(_("%s. Woche vom %s bis %s"), strftime("%V", $calendar->view->getStart()), strftime("%x", $calendar->view->getStart()), strftime("%x", $calendar->view->getEnd())); ?>
                    </td>
                    <td align="center" width="15%">
                        <a href="<?= URLHelper::getLink($_SERVER['PHP_SELF'], array('cmd' => 'showweek', 'atime' => $calendar->view->getEnd() + $GLOBALS['calendar_user_control_data']['start'] * 3600 + 1)) ?>">
                            <?= Assets::img('icons/16/blue/arr_2right.png', tooltip2(_("eine Woche vor"))) ?>
                        </a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td width="<?= $width2 ?>%" class="precol1" nowrap="nowrap" align="center">&nbsp;</td>
        <? $time = $calendar->view->getStart(); ?>
        <? for ($i = 0; $i < $calendar->view->getType(); $i++) : ?>
            <td colspan="<?= $cols ?>" align="center">
                <a href="<?= URLHelper::getLink($_SERVER['PHP_SELF'], array('cmd' => 'showday', 'atime' => $time, 'cal_group' => $group_id)) ?>" class="calhead">
                    <?= wday($time, "SHORT") . " " . date("d", $time) ?>
                </a>
            </td>
            <? $time += 86400; ?>
        <? endfor ?>
    </tr>
    <tr>
        <td width="<?= $width2 ?>%" class="precol1w" nowrap="nowrap" align="center">
            <?= _("Mitglied") ?>
        </td>
        <? foreach ($calendar->view->wdays as $day) : ?>
            <td width="<?= $width1 ?>%" class="precol1w" align="center">
                <a href="<?= URLHelper::getLink($_SERVER['PHP_SELF'], array('cmd' => 'edit', 'atime' => $atime, 'cal_group' => $group_id, 'devent' => '1')) ?>">
                    <?= Assets::img('day.gif', tooltip2(_("Tagestermin"))) ?>
                </a>
            </td>
            <? for ($i = $day->getStart() + $start; $i < $day->getStart() + $end; $i += 3600 * ceil($calendar->getUserSettings('step_week_group') / 3600)) : ?>
                <td colspan="<?= ceil(3600 / $calendar->getUserSettings('step_week_group')) ?>" class="precol2w" align="center">
                    <a href="<?= URLHelper::getLink($_SERVER['PHP_SELF'], array('cmd' => 'edit', 'atime' => $i, 'cal_group' => $group_id)) ?>" class="calhead">
                        <?= (date('G', $i) < 10 ? '&nbsp;' . date('G', $i) . '&nbsp;' : date('G', $i)) ?>
                    </a>
                </td>
            <? endfor ?>
        <? endforeach ?>
    </tr>

    <? while ($user_calendar = $calendar->nextCalendar()) : ?>
        <tr>
            <td width="<?= $width2 ?>%" nowrap="nowrap" class="month">
                <span class="precol2">
                    <a class="calhead" href="<?= URLHelper::getLink($_SERVER['PHP_SELF'], array('cmd' => 'showweek', 'atime' => $atime, 'cal_select' => get_username($user_calendar->getUserId()))) ?>">
                        <?= htmlReady($user_calendar->checkPermission(CALENDAR_PERMISSION_OWN) ? _("Eigener Kalender") : get_fullname($user_calendar->getUserId(), 'no_title_short')) ?>
                    </a>
                </span>
            </td>
            <? $k = 1; ?>
            <? foreach ($calendar->view->wdays as $day) : ?>
                <? // emphesize the current day if $compact is FALSE (this means week-view)
                if (date("Ymd", $day->getStart()) == date("Ymd")) {
                    $css_class = 'celltoday';
                } else {
                    if ($k % 2) {
                        $css_class = 'lightmonth';
                    } else {
                        $css_class = 'month';
                    }
                }
                $k++;
                $adapted = adapt_events($day, $start, $end);

                // display day events
                $js_events = array(); ?>
                    <? for ($i = 0; $i < sizeof($adapted['day_events']); $i++) :
                        $js_events[] = $day->events[$adapted['day_map'][$i]];
                    endfor ?>

                    <? if (sizeof($js_events)) : ?>
                        <td width="<?= $width1 ?>%" class="<?= $css_class ?>" align="right" style="background-image: url('<?= Assets::url('images/calendar/category5_small.jpg') ?>" <?/*= js_hover_group($js_events, $day->getStart(), $day->getEnd(), $user_calendar->getUserId()) */ ?>>
                    <? else : ?>
                        <td width="<?= $width1 ?>%" class="<?= $css_class ?>" align="right" class="<?= $css_class ?>">
                    <? endif ?>
                    <? if ($user_calendar->havePermission(CALENDAR_PERMISSION_WRITABLE)) : ?>
                        <a href="<?= URLHelper::getLink($_SERVER['PHP_SELF'], array('cmd' => 'edit', 'atime' => $atime, 'devent' => '1', 'cal_select' => 'user.' . get_username($user_calendar->getUserId()))) ?>">
                            <?= Assets::img('calplus.gif', tooltip2(_("neuer Tagestermin"))) ?>
                        </a>
                    <? endif ?>
                </td>

                <? for ($i = $start + $day->getStart(); $i < $end + $day->getStart(); $i += $calendar->getUserSettings('step_week_group')) : ?>
                    <? $js_events = array(); ?>
                    <? for ($j = 0; $j < sizeof($adapted['events']); $j++) : ?>
                        <? if (($adapted['events'][$j]->getStart() <= $i && $adapted['events'][$j]->getEnd() > $i) || ($adapted['events'][$j]->getStart() > $i && $adapted['events'][$j]->getStart() < $i + $calendar->getUserSettings('step_week_group'))) : ?>
                            <? $js_events[] = $day->events[$adapted['map'][$j]]; ?>
                        <? endif ?>
                    <? endfor ?>

                    <? if (sizeof($js_events)) : ?>
                        <td width="<?= $width1 ?>%" align="right" nowrap="nowrap" style="background-image: url('<?= Assets::url('/images/calendar/category5_small.jpg') ?>" <?/*= js_hover_group($js_events, $i, $i + $calendar->getUserSettings('step_week_group'), $calendar->getUserId()); */ ?>>
                    <? else : ?>
                        <td width="<?= $width1 ?>%" align="right" nowrap="nowrap" class="<?= $css_class ?>">
                    <? endif ?>
                    <? if ($user_calendar->havePermission(CALENDAR_PERMISSION_WRITABLE)) : ?>
                        <a href="<?= URLHelper::getLink($_SERVER['PHP_SELF'], array('cmd' => 'edit', 'atime' => $i, 'cal_select' => get_username($user_calendar->getUserId()))) ?>">
                            <?= Assets::img('calplus.gif', tooltip2(strftime(_("neuer Termin um %R Uhr"), $i))) ?>
                        </a>
                    <? endif ?>
                    </td>
                <? endfor ?>
            <? endforeach ?>
        </tr>
    <? endwhile ?>

    <tr>
        <td width="<?= $width2 ?>%" class="precol1" nowrap="nowrap" align="center">&nbsp;</td>
        <? foreach ($calendar->view->wdays as $day) : ?>
            <td width="<?= $width1 ?>%" class="precol1w" align="center">
                <a href="<?= URLHelper::getLink($_SERVER['PHP_SELF'], array('cmd' => 'edit', 'atime' => $atime, 'cal_group' => $group_id, 'devent' => '1')) ?>">
                    <?= Assets::img('day.gif', tooltip2(_("Tagestermin"))) ?>
                </a>
            </td>
            <? for ($i = $day->getStart() + $start; $i < $day->getStart() + $end; $i += 3600 * ceil($calendar->getUserSettings('step_week_group') / 3600)) : ?>
                <td colspan="<?= ceil(3600 / $calendar->getUserSettings('step_week_group')) ?>" class="precol2w" align="center">
                    <a href="<?= URLHelper::getLink($_SERVER['PHP_SELF'], array('cmd' => 'edit', 'atime' => $i, 'cal_group' => $group_id)) ?>" class="calhead">
                        <?= (date('G', $i) < 10 ? '&nbsp;' . date('G', $i) . '&nbsp;' : date('G', $i)) ?>
                    </a>
                </td>
            <? endfor ?>
        <? endforeach ?>
    </tr>

</table>
</td>
</tr>
<!-- END CALENDAR GROUP VIEW WEEK -->
