<?
if ($step >= 3600) {
    $rowspan_precol = '';
}
else {
    $rowspan_precol = ' rowspan="' . 3600 / $step . '"';
}

$em = createEventMatrix($calendar->view, $start, $end, $step);

?>
<tr>

<? if ($step >= 3600) : ?>
    <td class="precol1w" style="width:1%;"><?= _("Tag") ?></td>
    <td class="steel1" style="text-align:right; vertical-align:bottom; width:99%;"<?= (($em['max_cols'] > 0) ? ' colspan="' . ($em['max_cols'] + 1) . '"' : '') ?> valign="bottom">
<? else : ?>
    <td class="precol1w" style="width:1%;" colspan="2"><?= _("Tag") ?></td>
    <td class="steel1" style="text-align:right; vertical-align:bottom; width:99%;"<?= (($em['max_cols'] > 0) ? ' colspan="' . ($em['max_cols'] + 1) . '"' : '') ?>>
<? endif ?>
<?= $this->render_partial('calendar/_day_dayevents', array('em' => $em, 'show_edit_link' => $calendar->havePermission(Calendar::PERMISSION_WRITABLE), 'wday' => $calendar->view)); ?>
    </td>
</tr>
<? for ($i = $start / $step; $i < $end / $step + 3600 / $step; $i++) : ?>
<? $row = $i - $start / $step; ?>
<tr>
    <? if (($i * $step) % 3600 == 0) : ?>
    <td class="precol1" style="width: 1%;"<?= $rowspan_precol ?>>
        <a class="calhead" href="<?= URLHelper::getLink('', array('cmd' => 'edit', 'atime' => $calendar->view->getStart() + $i * $step)) ?>"><?= $i / (3600 / $step) ?></a>
    </td>
    <? endif ?>
    <? if ($step % 3600 != 0) : ?>
    <? $minute = ($row % (3600 / $step)) * ($step / 60); ?>
    <td class="precol2" style="height: 20px; width: 1%; padding-right: 3px;">
        <a class="calhead" href="<?= URLHelper::getLink('', array('cmd' => 'edit', 'atime' => $calendar->view->getStart() + $i * $step)) ?>"><?= $minute ? $minute : '00' ?></a>
    </td>
    <? endif ?>
    <?= $this->render_partial('calendar/_day_cell', array('day' => $calendar->view, 'em' => $em, 'row' => $row, 'i' => $i)); ?>
</tr>
<? endfor; ?>
