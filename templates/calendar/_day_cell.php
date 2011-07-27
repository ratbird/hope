<?
if ($calendar->view instanceof DbCalendarDay) {
    $title_length = 70;
} else {
    $title_length = ceil(125 / $calendar->view->getType());
}
?>
<? $link_notset = true ?>
<? if (!$em['term'][$row]) : ?>
<td class="steel1" align="right"  valign="middle"<?= ($em['max_cols'] > 0 ? ' colspan="' . ($em['max_cols'] + 1) . '"' : '') ?>>
    <? if ($calendar->havePermission(CALENDAR_PERMISSION_WRITABLE)) : ?>
    <a href="<?= URLHelper::getLink('', array('cmd' => 'edit', 'atime' => $day->getStart() + $i * $step)) ?>">
        <img src="<?= Assets::image_path('calplus.gif') ?>"<?= tooltip(strftime(_("neuer Termin um %R Uhr"), $row * $step + $start - 3600)) ?>>
    </a>
    <? endif ?>
</td>
<? $link_notset = false ?>
<? else : ?>
    <? for ($j = 0; $j < $em['colsp'][$row]; $j++) : ?>
        <? if (is_object($em['term'][$row][$j])) : ?>
            <? $cstyle = $em['term'][$row][$j]->getCategoryStyle('big'); ?>
            <td<?= ($em['cspan'][$row][$j] > 1 ? ' colspan="' . $em['cspan'][$row][$j] . '"' : '') ?><?= ($em['rows'][$row][$j] > 1 ? ' rowspan="' . $em['rows'][$row][$j] . '"' : '') ?> style="vertical-align:top; font-size:10px; color:#fff; background-image:url(<?= $cstyle['image'] ?>); border:solid 1px <?= $cstyle['color'] ?>;">
                <?
                if (strtolower(get_class($em['term'][$row][$j])) == 'seminarevent'
                        && $em['term'][$row][$j]->getTitle() == 'Kein Titel') {
                    $title_out = $em['term'][$row][$j]->getSemName();
                } else {
                    $title_out = $em['term'][$row][$j]->getTitle();
                }
                ?>
                <? if ($em['rows'][$row][$j] > 1) : ?>
                <div style="font-size:10px; height:15px; background-color:<?= $cstyle['color'] ?>;">
                        <?= date('H.i-', $day->events[$em['mapping'][$row][$j]]->getStart()) . date('H.i', $day->events[$em['mapping'][$row][$j]]->getEnd()) ?>
                </div>
                <? endif ?>
                <? if ($em['term'][$row][$j]->getPermission() == CALENDAR_EVENT_PERM_CONFIDENTIAL) : ?>
                    <?= fit_title($title_out, $em['colsp'][$row], 1, $title_length); ?>
                <? else : ?>
                    <a style="color:#fff;" href="<?= URLHelper::getLink('', array('cmd' => 'edit', 'atime' => ($day->getStart() + $em['term'][$row][$j]->getStart() % 86400), 'termin_id' => $em['term'][$row][$j]->getId())) ?>"><?= fit_title($title_out, $em['colsp'][$row], 1, $title_length); ?></a>
                <? endif ?>
            </td>
        <? elseif ($em['term'][$row][$j] == '#') : ?>
            <td class="steel1"<?= ($em['cspan'][$row][$j] > 1 ? ' colspan="' . $em['cspan'][$row][$j] . '"' : '') ?>>
                <span class="inday">&nbsp;</span>
            </td>
        <? elseif ($em['term'][$row][$j] == '') : ?>
            <td class="steel1"<?= ($em['cspan'][$row][$j] > 1 ? ' colspan="' . $em['cspan'][$row][$j] . '"' : '') ?> align="right" valign="middle">
                <? if ($calendar->havePermission(CALENDAR_PERMISSION_WRITABLE)) : ?>
                <a href="<?= URLHelper::getLink('', array('cmd' => 'edit', 'atime' => $day->getStart() + $i * $step)) ?>">
                    <img src="<?= Assets::image_path('calplus.gif') ?>"<?= tooltip(strftime(_("neuer Termin um %R Uhr"), $row * $step + $start - 3600)) ?>>
                </a>
                <? endif ?>
            </td>
            <? $link_notset = false; ?>
            <? break; ?>
        <? endif ?>
    <? endfor ?>
<? endif ?>
<? if ($link_notset) : ?>
    <td class="steel1" align="right" valign="middle">
        <? if ($calendar->havePermission(CALENDAR_PERMISSION_WRITABLE)) : ?>
        <a href="<?= URLHelper::getLink('', array('cmd' => 'edit', 'atime' => $day->getStart() + $i * $step)) ?>">
            <img src="<?= Assets::image_path('calplus.gif') ?>"<?= tooltip(strftime(_("neuer Termin um %R Uhr"), $row * $step + $start - 3600)) ?>>
        </a>
        <? endif ?>
    </td>
<? endif ?>