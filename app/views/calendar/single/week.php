<?
// add skip link
SkipLinks::addIndex(_("Wochenansicht"), 'main_content', 100);
$at = date('G', $atime);
if ($at >= $settings['start']
        && $at <= $settings['end'] || !$atime) {
    $start = $settings['start'];
    $end = $settings['end'];
} elseif ($at < $settings['start']) {
    $start = 0;
    $end = $settings['start'] + 2;
} else {
    $start = $settings['end'] - 2;
    $end = 23;
}
$tab_arr = '';
$max_columns = 0;
$week_type = $settings['type_week'] == 'SHORT' ? 5 : 7;
$rows = ($end - $start + 1) * 3600 / $settings['step_week'];

for ($i = 0; $i < $week_type; $i++) {
    $tab_arr[$i] = $calendars[$i]->createEventMatrix($start * 3600, $end * 3600, $settings['step_week']);
    if ($tab_arr[$i]['max_cols']) {
        $max_columns += ($tab_arr[$i]['max_cols'] + 1);
    } else {
        $max_columns++;
    }
}

$rowspan = ceil(3600 / $settings['step_week']);
$height = ' height="20"';

if ($rowspan > 1) {
    $colspan_1 = ' colspan="2"';
    $colspan_2 = $max_columns + 4;
    $width_daycols = 100 - (4 + $week_type) * 0.1;
} else {
    $colspan_1 = '';
    $colspan_2 = $max_columns + 2;
    $width_daycols = 100 - (2 + $week_type) * 0.1;
}
?>
<table id="main_content" class="calendar-week">
    <colgroup>
        <col style="max-width: 1.5em; width: 1.5em;">
        <? if ($rowspan > 1) : ?>
        <col style="max-width: 1.5em; width: 1.5em;">
        <? endif; ?>
        <? for ($i = 0; $i < $week_type; $i++) : ?>
        <? if ($tab_arr[$i]['max_cols'] > 0) : ?>
        <? $event_cols = $tab_arr[$i]['max_cols'] ?: 1; ?>
        <col span="<?= $event_cols ?>" style="width: <?= 100 / $week_type / $event_cols ?>%">
        <col style="max-width: 0.9em; width: 0.9em;">
        <? else : ?>
        <col style="width: <?= 100 / $week_type ?>%">
        <? endif; ?>
        <? endfor; ?>
        <col style="max-width: 1.5em; width: 1.5em;">
        <? if ($rowspan > 1) : ?>
        <col style="max-width: 1.5em; width: 1.5em;">
        <? endif; ?>
    </colgroup>
    <thead>
    <tr>
        <td colspan="<?= $colspan_2 ?>" style="vertical-align: middle; text-align: center;">
            <div style="text-align: left; width: 20%; display: inline-block; white-space: nowrap;">
                <a href="<?= $controller->url_for('calendar/single/week', array('atime' => mktime(12, 0, 0, date('n', $atime), date('j', $atime) - 7, date('Y', $atime)))) ?>">
                    <span style="vertical-align: middle;" <?= tooltip(_('eine Woche zurück')) ?>>
                    <?= Assets::img('icons/16/blue/arr_1left.png') ?>
                    </span>
                    <?= strftime(_('%V. Woche'), strtotime('-1 week', $atime)) ?>
                </a>
            </div>
            <div style="width: 50%; display: inline-block; text-align: center;" class="calhead">
                <? printf(_("%s. Woche vom %s bis %s"), strftime("%V", $calendars[0]->getStart()), strftime("%x", $calendars[0]->getStart()), strftime("%x", $calendars[$week_type - 1]->getStart())) ?>
            </div>
            <div style="text-align: right; width: 20%;  display: inline-block; white-space: nowrap;">
                <a href="<?= $controller->url_for('calendar/single/week', array('atime' => mktime(12, 0, 0, date('n', $atime), date('j', $atime) + 7, date('Y', $atime)))) ?>">
                    <?= strftime(_('%V. Woche'), strtotime('+1 week', $atime)) ?>
                    <span style="vertical-align: middle;" <?= tooltip(_('eine Woche vor')) ?>>
                    <?= Assets::img('icons/16/blue/arr_1right.png') ?>
                    </span>
                </a>
            </div>
        </td>
    </tr>
    <tr>
        <td style="text-align: center; white-space: nowrap;" <?= $colspan_1 ?>>
            <? if ($start > 0) : ?>
            <a href="<?= $controller->url_for('calendar/single/week', array('atime' => mktime($start - 1, 0, 0, date('n', $atime), date('j', $atime), date('Y', $atime)))) ?>">
                <?= Assets::img('icons/16/blue/arr_1up.png', tooltip2(_('zeig davor'))) ?>
            </a>
            <? endif ?>
        </td>
        <? // weekday and date as title for each column ?>
        <? for ($i = 0; $i < $week_type; $i++) : ?>
        <td style="text-align:center; font-weight:bold;"<?= ($tab_arr[$i]['max_cols'] > 0 ? ' colspan="' . ($tab_arr[$i]['max_cols'] + 1) . '"' : '' ) ?>>
            <a class="calhead" href="<?= $controller->url_for('calendar/single/day', array('atime' => $calendars[$i]->getStart())) ?>">
                <?= strftime('%a', $calendars[$i]->getStart()) . ' ' . date('d', $calendars[$i]->getStart()) ?>
            </a>
            <? if ($holiday = holiday($calendars[$i]->getStart())) : ?>
            <div style="font-size:9pt; color:#bbb; height:auto; overflow:visible; font-weight:bold;"><?= $holiday['name'] ?></div>
            <? endif ?>
        </td>
        <? endfor ?>
        <td style="text-align: center; white-space: nowrap;" <?= $colspan_1 ?>>
            <? if ($start > 0) : ?>
            <a href="<?= $controller->url_for('calendar/single/week', array('atime' => mktime($start - 1, 0, 0, date('n', $calendars[0]->getStart()), date('j', $calendars[0]->getStart()), date('Y', $calendars[0]->getStart())))) ?>">
                <?= Assets::img('icons/16/blue/arr_1up.png', tooltip2(_('zeig davor'))) ?>
            </a>
            <? endif ?>
        </td>
    </tr>
    </thead>
    <tbody>
    <tr>
        <? // Zeile mit Tagesterminen ausgeben ?>
        <td class="precol1w"<?= $colspan_1 ?> height="20">
            <?= _("Tag") ?>
        </td>
        <? for ($i = 0; $i < $week_type; $i++) : ?>
        <?
        if (date('Ymd', $calendars[$i]->getStart()) == date('Ymd')) {
            $class_cell = 'lightgrey';
        } else {
            $class_cell = '';
        }
        ?>
        <?= $this->render_partial('calendar/single/_day_dayevents', array('em' => $tab_arr[$i], 'calendar' => $calendars[$i], 'class_cell' => $class_cell)) ?>
        <? endfor ?>
        <td class="precol1w"<?= $colspan_1 ?>>
            <?= _('Tag') ?>
        </td>
    </tr>
    <? $j = $start ?>
    <? for ($i = 0; $i < $rows; $i++) : ?>
    <tr>
        <? if ($i % $rowspan == 0) : ?>
            <? if ($rowspan == 1) : ?>
            <td class="precol1w"<?= $height ?>><?= $j ?></td>
            <?  else : ?>
            <td class="precol1w" rowspan="<?= $rowspan ?>"><?= $j ?></td>
            <? endif ?>
        <? endif ?>
        <? if ($rowspan > 1) : ?>
            <? $minutes = (60 / $rowspan) * ($i % $rowspan); ?>
            <? if ($minutes == 0) : ?>
            <td class="precol2w"<?= $height ?>>00</td>
            <? else : ?>
            <td class="precol2w"<?= $height ?>><?= $minutes ?></td>
            <? endif ?>
        <? endif ?>
        <? for ($y = 0; $y < $week_type; $y++) : ?>
            <?
            if (date('Ymd', $calendars[$y]->getStart()) == date('Ymd')) {
                $class_cell = 'lightgrey';
            } else {
                $class_cell = '';
            }
            ?>
            <?= $this->render_partial('calendar/single/_day_cell', array('calendar' => $calendars[$y], 'em' => $tab_arr[$y], 'row' => $i, 'start' => $start * 3600, 'i' => $i + ($start * 3600 / $settings['step_week']), 'step' => $settings['step_week'], 'class_cell' => $class_cell)); ?>
        <? endfor ?>
        <? if ($rowspan > 1) : ?>
            <? if ($minutes == 0) : ?>
            <td class="precol2w"<?= $height ?>>00</td>
            <? else : ?>
            <td class="precol2w"<?= $height ?>><?= $minutes ?></td>
            <? endif ?>
        <? endif ?>
        <? if (($i + 2) % $rowspan == 0) : ?>
            <? if ($rowspan == 1) : ?>
            <td class="precol1w"<?= $height ?>><?= $j ?></td>
            <?  else : ?>
            <td class="precol1w" rowspan="<?= $rowspan ?>"><?= $j ?></td>
            <? endif ?>
            <? $j = $j + ceil($settings['step_week'] / 3600); ?>
        <? endif ?>
    </tr>
    <? endfor ?>
    </tbody>
    <tfoot>
    <tr>
        <td<?= $colspan_1 ?> style="text-align:center;">
        <? if ($end < 23) : ?>
            <a href="<?= $controller->url_for('calendar/single/week', array('atime' => mktime($end + 1, 0, 0, date('n', $calendars[0]->getStart()), date('j', $calendars[0]->getStart()), date('Y', $calendars[0]->getStart())))) ?>">
                <?= Assets::img('icons/16/blue/arr_1down.png', tooltip2(_('zeig danach'))) ?>
            </a>
        <? endif ?>
        </td>
        <td colspan="<?= $max_columns ?>">&nbsp;</td>
        <td<?= $colspan_1 ?> style="text-align:center;">
        <? if ($end < 23) : ?>
            <a href="<?= $controller->url_for('calendar/single/week', array('atime' => mktime($end + 1, 0, 0, date('n', $calendars[0]->getStart()), date('j', $calendars[0]->getStart()), date('Y', $calendars[0]->getStart())))) ?>">
                <?= Assets::img('icons/16/blue/arr_1down.png', tooltip2(_('zeig danach'))) ?>
            </a>
        <? endif ?>
        </td>
    </tr>
    </tfoot>
</table>