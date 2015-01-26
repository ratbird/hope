<?
if ($settings['step_day'] >= 3600) {
    $rowspan_precol = '';
} else {
    $rowspan_precol = ' rowspan="' . 3600 / $settings['step_day'] . '"';
}

?>
<tr>
    <td class="precol1w" <?= $rowspan_precol ? ' colspan="2"' : '' ?>><?= _("Tag") ?></td>
    <td  style="text-align:right; vertical-align:bottom;"<?= (($em['max_cols'] > 0) ? ' colspan="' . ($em['max_cols'] + 1) . '"' : '') ?> valign="bottom">
<?= $this->render_partial('calendar/single/_day_dayevents', array('em' => $em)); ?>
    </td>
</tr>
<? for ($i = $start / $settings['step_day']; $i < $end / $settings['step_day'] + 3600 / $settings['step_day']; $i++) : ?>
<? $row = $i - $start / $settings['step_day']; ?>
<tr>
    <? if (($i * $settings['step_day']) % 3600 == 0) : ?>
    <td class="precol1" <?= $rowspan_precol ?>>
        <a data-dialog="size=auto" class="calhead" href="<?= $controller->url_for('calendar/single/edit/' . $calendar->getRangeId(), array('atime' => $calendar->getStart() + $i * $settings['step_day'])) ?>"><?= $i / (3600 / $settings['step_day']) ?></a>
    </td>
    <? endif ?>
    <? if ($settings['step_day'] % 3600 != 0) : ?>
    <? $minute = ($row % (3600 / $settings['step_day'])) * ($settings['step_day'] / 60); ?>
    <td class="precol2" style="height: 20px; width: 1%; padding-right: 3px;">
        <a data-dialog="size=auto" class="calhead" href="<?= $controller->url_for('calendar/single/edit/' . $calendar->getRangeId(), array('atime' => $calendar->getStart() + $i * $settings['step_day'])) ?>"><?= $minute ? $minute : '00' ?></a>
    </td>
    <? endif ?>
    <?= $this->render_partial('calendar/single/_day_cell', array('events' => $calendar->events, 'start' => $start, 'em' => $em, 'row' => $row, 'i' => $i, 'step' => $settings['step_day'])); ?>
</tr>
<? endfor; ?>