<? $link_notset = true ?>
<? if (!$em['term'][$row]) : ?>
<td class="<?= $style_cell ?>" align="right"  valign="middle"<?= ($em['max_cols'] > 0 ? ' colspan="' . ($em['max_cols'] + 1) . '"' : '') ?>>
    <? if ($calendar->havePermission(Calendar::PERMISSION_WRITABLE)) : ?>
    <a href="<?= $controller->url_for('calendar/single/edit', array('atime' => $calendar->getStart() + $i * $step)) ?>">
        <img src="<?= Assets::image_path('calplus.gif') ?>"<?= tooltip(strftime(_("neuer Termin um %R Uhr"), $row * $step + $start_time - 3600)) ?>>
    </a>
    <? endif ?>
</td>
<? $link_notset = false ?>
<? else : ?>
    <? for ($j = 0; $j < $em['colsp'][$row]; $j++) : ?>
        <? $event = $em['term'][$row][$j]; ?>
        <? $mapped_event = $calendar->events[$em['mapping'][$row][$j]]; ?>
        <? if (is_object($event)) : ?>
            <? $cstyle = $event->getCategoryStyle('big'); ?>
            <td<?= ($em['cspan'][$row][$j] > 1 ? ' colspan="' . $em['cspan'][$row][$j] . '"' : '') ?><?= ($em['rows'][$row][$j] > 1 ? ' rowspan="' . $em['rows'][$row][$j] . '"' : '') ?> style="vertical-align:top; font-size:10px; color:#fff; background-image:url(<?= $cstyle['image'] ?>); border:solid 1px <?= $cstyle['color'] ?>;">
                <? if ($em['rows'][$row][$j] > 1) : ?>
                <div style="font-size:10px; height:15px; background-color:<?= $cstyle['color'] ?>;">
                        <?= date('H.i-', $mapped_event->getStart()) . date('H.i', $mapped_event->getEnd()) ?>
                </div>
                <? endif ?>
                <? if ($event->getPermission() == Event::PERMISSION_CONFIDENTIAL) : ?>
                    <?= $event->getTitle() ?>
                <? else : ?>
                    <a style="color:#fff;" href="<?= $controller->url_for('', array('atime' => ($calendar->getStart() + $event->getStart() % 86400), 'termin_id' => $event->getId(), 'evtype' => $event->getType())) ?>" <?//= js_hover($mapped_event); ?>><?= $event->getTitle() ?></a>
                <? endif ?>
            </td>
        <? elseif ($event == '#') : ?>
            <td class="<?= $style_cell ?>"<?= ($em['cspan'][$row][$j] > 1 ? ' colspan="' . $em['cspan'][$row][$j] . '"' : '') ?>>
                <span class="inday">&nbsp;</span>
            </td>
        <? elseif ($event == '') : ?>
            <td class="<?= $style_cell ?>"<?= ($em['cspan'][$row][$j] > 1 ? ' colspan="' . $em['cspan'][$row][$j] . '"' : '') ?> align="right" valign="middle">
                <? if ($calendar->havePermission(Calendar::PERMISSION_WRITABLE)) : ?>
                <a style="display: block; width: 11px;" href="<?= $controller->url_for('', array('atime' => $calendar->getStart() + $i * $step)) ?>">
                    <img src="<?= Assets::image_path('calplus.gif') ?>"<?= tooltip(strftime(_("neuer Termin um %R Uhr"), $row * $step + $start_time - 3600)) ?>>
                </a>
                <? endif ?>
            </td>
            <? $link_notset = false; ?>
            <? break; ?>
        <? endif ?>
    <? endfor ?>
<? endif ?>
<? if ($link_notset) : ?>
    <td style="width: 0.1%;" class="<?= $style_cell ?>" align="right" valign="middle">
        <? if ($calendar->havePermission(Calendar::PERMISSION_WRITABLE)) : ?>
        <a style="display: block; width: 10px;" href="<?= $controller->url_for('', array('atime' => $calendar->getStart() + $i * $step)) ?>">
            <img src="<?= Assets::image_path('calplus.gif') ?>"<?= tooltip(strftime(_("neuer Termin um %R Uhr"), $row * $step + $start_time - 3600)) ?>>
        </a>
        <? endif ?>
    </td>
<? endif ?>