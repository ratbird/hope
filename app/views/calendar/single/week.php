<?
// add skip link
SkipLinks::addIndex(_("Wochenansicht"), 'main_content', 100);
?>
<table id="main_content" class="blank" border="0" cellpadding="10" cellspacing="0" style="width:100%; table-layout: fixed;">
    <tr>
        <td style="width:100%;">
<?
$tab_arr = '';
$max_columns = 0;
$week_type = $calendar_settings['week_type'] == 'SHORT' ? 5 : 7;
$rows = ($end - $start + 1) * 3600 / $step;

for ($i = 0; $i < $week_type; $i++) {
    $tab_arr[$i] = $calendars[$i]->createEventMatrix($start * 3600, $end * 3600, $step);
    if ($tab_arr[$i]['max_cols']) {
        $max_columns += ($tab_arr[$i]['max_cols'] + 1);
    } else {
        $max_columns++;
    }
}

$rowspan = ceil(3600 / $step);
$height = ' height="20"';

if ($rowspan > 1) {
    $colspan_1 = ' colspan="2"';
    $colspan_2 = $max_columns + 4;
} else {
    $colspan_1 = '';
    $colspan_2 = $max_columns + 2;
}
?>
<table border="0" width="100%" cellspacing="1" cellpadding="0" class="steelgroup0">
    <thead>
    <tr>
        <td colspan="<?= $colspan_2 ?>">
            <table width="100%" border="0" cellpadding="2" cellspacing="0" align="center" class="steelgroup0">
                <tr>
                    <td align="center" width="15%">
                        <a href="<?= $controller->url_for('calendar/single/week', array('atime' => mktime(12, 0, 0, date('n', $calendars[0]->getStart()), date('j', $calendars[0]->getStart()) - 7, date('Y', $calendars[0]->getStart())))) ?>">
                            <?= Assets::img('icons/16/blue/arr_1left.png', tooltip2(_("eine Woche zurück"))) ?>
                        </a>
                    </td>
                    <td width="70%" class="calhead">
                        <? printf(_("%s. Woche vom %s bis %s"), strftime("%V", $calendars[0]->getStart()), strftime("%x", $calendars[0]->getStart()), strftime("%x", $calendars[$week_type - 1]->getStart())) ?>
                    </td>
                    <td align="center" width="15%">
                        <a href="<?= $controller->url_for('calendar/single/week', array('atime' => mktime(12, 0, 0, date('n', $calendars[0]->getStart()), date('j', $calendars[0]->getStart()) + 7, date('Y', $calendars[0]->getStart())))) ?>">
                            <?= Assets::img('icons/16/blue/arr_1right.png', tooltip2(_("eine Woche vor"))) ?>
                        </a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td nowrap="nowrap" align="center" width="<?= $week_type == 7 ? '1%' : '3%' ?>"<?= $colspan_1 ?>>
            <? if ($start > 0) : ?>
            <a href="<?= $controller->url_for('calendar/single/week', array('atime' => mktime($start - 1, 0, 0, date('n', $calendars[0]->getStart()), date('j', $calendars[0]->getStart()), date('Y', $calendars[0]->getStart())))) ?>">
                <?= Assets::img('icons/16/blue/arr_1up.png', tooltip2(_("zeig davor"))) ?>
            </a>
            <? endif ?>
        </td>
        <? // weekday and date as title for each column ?>
        <? for ($i = 0; $i < $week_type; $i++) : ?>
        <td class="steelgroup0" style="text-align:center; font-weight:bold; width:<?= (98 / $week_type) ?>%;"<?= ($tab_arr[$i]['max_cols'] > 0 ? ' colspan="' . ($tab_arr[$i]['max_cols'] + 1) . '"' : '' ) ?>>
            <a class="calhead" href="<?= $controller->url_for('calendar/single/day', array('atime' => $calendars[$i]->getStart())) ?>">
                <?= strftime('%a', $calendars[$i]->getStart()) . ' ' . date('d', $calendars[$i]->getStart()) ?>
            </a>
            <? if ($holiday = holiday($calendars[$i]->getStart())) : ?>
            <div style="font-size:9pt; color:#bbb; height:auto; overflow:visible; font-weight:bold;"><?= $holiday['name'] ?></div>
            <? endif ?>
        </td>
        <? endfor ?>
        <td nowrap="nowrap" align="center" width="<?= $week_type == 7 ? '1%' : '3%' ?>"<?= $colspan_1 ?>>
            <? if ($start > 0) : ?>
            <a href="<?= $controller->url_for('calendar/single/week', array('atime' => mktime($start - 1, 0, 0, date('n', $calendars[0]->getStart()), date('j', $calendars[0]->getStart()), date('Y', $calendars[0]->getStart())))) ?>">
                <?= Assets::img('icons/16/blue/arr_1up.png', tooltip2(_("zeig davor"))) ?>
            </a>
            <? endif ?>
        </td>
    </tr>
    <tr>
        <? // Zeile mit Tagesterminen ausgeben ?>
        <td class="precol1w"<?= $colspan_1 ?> height="20">
            <?= _("Tag") ?>
        </td>
        <? for ($i = 0; $i < $week_type; $i++) : ?>
        <?
        // emphesize the current day if $compact is FALSE (this means week-view)
        if (date('Ymd', $calendars[$i]->getStart()) == date('Ymd')) {
            $style_cell = 'celltoday';
        } else {
            $style_cell = 'table_row_even';
        }
        ?>
        <td class="<?= $style_cell ?>" style="text-align:right; vertical-align:top;"<?= (($tab_arr[$i]['max_cols'] > 0) ? ' colspan="' . ($tab_arr[$i]['max_cols'] + 1) . '"' : '') ?>>
            <?= $this->render_partial('calendar/single/_day_dayevents', array('em' => $tab_arr[$i], 'calendar' => $calendars[$i])) ?>
            <?/* if ($calendar->havePermission(Calendar::PERMISSION_WRITABLE)) : ?>
                <div style="width: 14px; float:right;">
                    <a href="<?= URLHelper::getLink('',  array('cmd' => 'edit', 'atime' => $calendar->view->wdays[$i]->getTs(), 'devent' => '1')) ?>">
                        <img src="<?= Assets::image_path('calplus.gif') ?>"<?= tooltip(_("neuer Tagestermin")) ?>>
                    </a>
                </div>
            <? endif */?>
        </td>
        <? endfor ?>
        <td class="precol1w"<?= $colspan_1 ?>>
            <?= _("Tag") ?>
        </td>
    </tr>
    </thead>
    <tbody>
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
            <?= $this->render_partial('calendar/single/_day_cell', array('calendar' => $calendars[$y], 'em' => $tab_arr[$y], 'row' => $i, 'start' => $start * 3600, 'i' => $i + ($start * 3600 / $step), 'step' => $step)); ?>
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
            <? $j = $j + ceil($step / 3600); ?>
        <? endif ?>
    </tr>
    <? endfor ?>
    </tbody>
    <tfoot>
    <tr>
        <td<?= $colspan_1 ?> style="text-align:center;">
        <? if ($end < 23) : ?>
            <a href="<?= $controller->url_for('calendar/single/week', array('atime' => mktime($end + 1, 0, 0, date('n', $calendars[0]->getStart()), date('j', $calendars[0]->getStart()), date('Y', $calendars[0]->getStart())))) ?>">
                <?= Assets::img('icons/16/blue/arr_1down.png', tooltip2(_("zeig danach"))) ?>
            </a>
        <? endif ?>
        </td>
        <td colspan="<?= $max_columns ?>">&nbsp;</td>
        <td<?= $colspan_1 ?> style="text-align:center;">
        <? if ($end < 23) : ?>
            <a href="<?= $controller->url_for('calendar/single/week', array('atime' => mktime($end + 1, 0, 0, date('n', $calendars[0]->getStart()), date('j', $calendars[0]->getStart()), date('Y', $calendars[0]->getStart())))) ?>">
                <?= Assets::img('icons/16/blue/arr_1down.png', tooltip2(_("zeig danach"))) ?>
            </a>
        <? endif ?>
        </td>
    </tr>
    </tfoot>
</table>
        </td>
    </tr>
</table>