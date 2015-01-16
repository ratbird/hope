<?
$at = date('G', $atime);
if ($at >= $settings['start']
        && $at <= $settings['end'] || !$atime) {
    $start = $settings['start'] * 3600;
    $end = $settings['end'] * 3600;
} elseif ($at < $settings['start']) {
    $start = 0;
    $end = ($settings['start'] + 2) * 3600;
} else {
    $start = ($settings['end'] - 2) * 3600;
    $end = 23 * 3600;
}
?>
<table width="100%" class="blank">
    <tr>
        <td class="blank" style="width: 100%; text-align: center; vertical-align: middle;">
            <div style="text-align: left; width: 20%; display: inline-block; white-space: nowrap;">
                <a href="<?= $controller->url_for('calendar/single/day', array('atime' => $atime - 86400)) ?>">
                    <span style="vertical-align: middle;" <?= tooltip(_('eine Woche zurück')) ?>>
                    <img border="0" src="<?= Assets::image_path('icons/16/blue/arr_1left.png') ?>"<?= tooltip(_("zurück")) ?>>
                    </span>
                    <?= strftime(_('%x'), strtotime('-1 day', $calendar->getStart())) ?>
                </a>
            </div>
            <div class="calhead" style="width: 50%; display: inline-block;">
                <?= strftime('%A, %e. %B %Y', $atime) ?>
                <div style="text-align: center; font-size: 12pt; color: #bbb; height: auto; overflow: visible; font-weight: bold;"><? $hd = holiday($atime); echo $holiday['name']; ?></div>
            </div>
            <div style="text-align: right; width: 20%; display: inline-block; white-space: nowrap;">
                <a href="<?= $controller->url_for('calendar/single/day', array('atime' => $atime + 86400)) ?>">
                    <?= strftime(_('%x'), strtotime('+1 day', $calendar->getStart())) ?>
                    <span style="vertical-align: middle;" <?= tooltip(_('eine Woche vor')) ?>>
                    <?= Assets::img('icons/16/blue/arr_1right.png') ?>
                    </span>
                </a>
            </div>
            <? if ($start > 0) : ?>
                <div style="text-align: center;" colspan="3">
                    <a href="<?= $controller->url_for('calendar/single/day', array('atime' => ($atime - (date('G', $atime) * 3600 - $start + 3600)))) ?>">
                        <?= Assets::img('icons/16/blue/arr_1up.png', tooltip2(_('zeig davor'))) ?>
                    </a>
                </div>
            <? endif ?>
        </td>
    </tr>
    <tr> 
        <td class="blank">
            <table class="calendar-day">
                <?= $this->render_partial('calendar/single/_day_table', array('start' => $start, 'end' => $end)) ?>
            </table>
        </td>
    </tr>
<? if ($end / 3600 < 23) : ?>
    <tr>
        <td align="center">
            <a href="<?= $controller->url_for('calendar/single/day', array('atime' => ($atime + $end - date('G', $atime) * 3600 + 3600))) ?>">
                <img border="0" src="<?= Assets::image_path('icons/16/blue/arr_1down.png') ?>"<?= tooltip(_("zeig danach")) ?>>
            </a>
        </td>
    </tr>
<? else : ?>
    <tr>
        <td>&nbsp;</td>
    </tr>
<? endif ?>
</table>