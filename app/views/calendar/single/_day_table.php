<?
if ($step >= 3600) {
    $rowspan_precol = '';
}
else {
    $rowspan_precol = ' rowspan="' . 3600 / $step . '"';
}

$em = $calendar->createEventMatrix($start, $end, $step);

?>
<thead>
    <tr>
        <td class="precol1w" style="width:1%;"<?= $step >= 3600 ? '' : ' colspan="2"' ?>><?= _("Tag") ?></td>
        <td class="table_row_even" style="text-align:right; vertical-align:bottom; width:99%;"<?= (($em['max_cols'] > 0) ? ' colspan="' . ($em['max_cols'] + 1) . '"' : '') ?> valign="bottom">
    <?= $this->render_partial('calendar/single/_day_dayevents', array('em' => $em)); ?>
        </td>
    </tr>
</thead>
<tbody>
    <? for ($i = $start / $step; $i < $end / $step + 3600 / $step; $i++) : ?>
    <? $row = $i - $start / $step; ?>
    <tr>
        <? if (($i * $step) % 3600 == 0) : ?>
        <td class="precol1" style="width: 1%;"<?= $rowspan_precol ?>>
            <a class="calhead" href="<?= $controller->url_for('calendar/single/edit', array('atime' => $calendar->getStart() + $i * $step)) ?>"><?= $i / (3600 / $step) ?></a>
        </td>
        <? endif ?>
        <? if ($step % 3600 != 0) : ?>
        <? $minute = ($row % (3600 / $step)) * ($step / 60); ?>
        <td class="precol2" style="height: 20px; width: 1%; padding-right: 3px;">
            <a class="calhead" href="<?= $controller->url_for('calendar/single/edit', array('atime' => $calendar->getStart() + $i * $step)) ?>"><?= $minute ? $minute : '00' ?></a>
        </td>
        <? endif ?>
        <?= $this->render_partial('calendar/single/_day_cell', array('events' => $calendar->events, 'em' => $em, 'row' => $row, 'i' => $i)); ?>
    </tr>
    <? endfor; ?>
</tbody>