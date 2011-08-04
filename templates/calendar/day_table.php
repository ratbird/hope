<?
$compact = TRUE;
$link_edit = FALSE;
$title_length = 70;
$height = 20;
if (is_null($params)) {
    $params = array();
}
extract($params);
$width_precol_1 = 5;
$width_precol_2 = 4;
$day_event_row = '';
// emphesize the current day if $compact is FALSE (this means week-view)
if (date('Ymd', $calendar->view->getStart()) == date('Ymd') && !$compact) {
    $style_cell = 'celltoday';
} else {
    $style_cell = 'steel1';
}

if ($step >= 3600) {
    $height_precol_1 = ' height="' . ($step / 3600) * $height . '"';
    $height_precol_2 = '';
    $rowspan_precol = '';
    $width_precol_1_txt = '';
    $width_precol_2_txt = '';
}
else {
    $height_precol_1 = "";
    $height_precol_2 = ' height="' . $height . '"';
    $rowspan_precol = ' rowspan="' . 3600 / $step . '"';
    $width_precol_1_txt = " width=\"$width_precol_1%\" nowrap ";
    $width_precol_2_txt = " width=\"$width_precol_2%\" nowrap ";
}

$em = createEventMatrix($calendar->view, $start, $end, $step);

?>
<tr>

<? if ($step >= 3600) : ?>
    <td class="precol1w" style="width:5%;"><?= _("Tag") ?></td>
    <td class="steel1" style="text-align:right; vertical-align:bottom; width:95%;"<?= (($em['max_cols'] > 0) ? ' colspan="' . ($em['max_cols'] + 1) . '"' : '') ?> valign="bottom">
<? else : ?>
    <td class="precol1w" style="width:9%" colspan="2"><?= _("Tag") ?></td>
    <td class="steel1" style="text-align:right; vertical-align:bottom; width:91%;"<?= (($em['max_cols'] > 0) ? ' colspan="' . ($em['max_cols'] + 1) . '"' : '') ?>>
<? endif ?>
<?= $this->render_partial('calendar/_day_dayevents', array('em' => $em)); ?>
    <? if ($calendar->havePermission(CALENDAR_PERMISSION_WRITABLE)) : ?>
        <div>
            <a href="<?= URLHelper::getLink('',  array('cmd' => 'edit', 'atime' => $calendar->view->getTs(), 'devent' => '1')) ?>">
                <img src="<?= Assets::image_path('calplus.gif') ?>"<?= tooltip(_("neuer Tagestermin")) ?>>
            </a>
        </div>
    <? endif ?>
    </td>
</tr>
<? for ($i = $start / $step; $i < $end / $step + 3600 / $step; $i++) : ?>
<? $row = $i - $start / $step; ?>
<tr>
    <? if (($i * $step) % 3600 == 0) : ?>
    <td class="precol1"<?= $width_precol_1_txt . $height_precol_1 . $rowspan_precol ?>>
        <a class="calhead" href="<?= URLHelper::getLink('', array('cmd' => 'edit', 'atime' => $calendar->view->getStart() + $i * $step)) ?>"><?= $i / (3600 / $step) ?></a>
    </td>
    <? $width_precol_1_txt = '' ?>
    <? endif ?>
    <? if ($step % 3600 != 0) : ?>
    <? $minute = ($row % (3600 / $step)) * ($step / 60); ?>
    <td class="precol2"<?= $width_precol_2_txt . $height_precol_2 ?>>
        <a class="calhead" href="<?= URLHelper::getLink('', array('cmd' => 'edit', 'atime' => $calendar->view->getStart() + $i * $step)) ?>"><?= $minute ? $minute : '00' ?></a>
    </td>
    <? $width_precol_2_txt = '' ?>
    <? endif ?>
    <?= $this->render_partial('calendar/_day_cell', array('day' => $calendar->view, 'em' => $em, 'row' => $row, 'i' => $i)); ?>
</tr>
<? endfor; ?>
