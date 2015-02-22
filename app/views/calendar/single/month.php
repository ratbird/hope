<? $month = $calendar->view; ?>
<table class="calendar-month">
    <thead>
        <tr>
            <td colspan="8" style="text-align: center; vertical-align: middle;">
                <div style="text-align: left; display: inline-block; width: 20%; white-space: nowrap;">
                    <a style="padding-right: 2em;" href="<?= $controller->url_for('calendar/single/month', array('atime' => mktime(12, 0, 0, date('n', $calendars[15]->getStart()), 15, date('Y', $calendars[15]->getStart()) - 1))) ?>">
                        <span style="vertical-align: middle;" <?= tooltip(_('ein Jahr zur�ck')) ?>>
                        <?= Assets::img('icons/16/blue/arr_2left.png') ?>
                        </span>
                        <?= strftime('%B %Y', strtotime('-1 year', $calendars[15]->getStart())) ?>
                    </a>
                    <a href="<?= $controller->url_for('calendar/single/month', array('atime' => $calendars[0]->getStart() - 1)) ?>">
                        <span style="vertical-align: middle;" <?= tooltip(_('einen Monat zur�ck')) ?>>
                        <?= Assets::img('icons/16/blue/arr_1left.png', tooltip2(_('einen Monat zur�ck'))) ?>
                        </span>
                        <?= strftime('%B %Y', strtotime('-1 month', $calendars[15]->getStart())) ?>
                    </a>
                </div>
                <div class="calhead" style="text-align: center; display: inline-block; width:50%;">
                    <?= htmlReady(strftime("%B ", $calendars[15]->getStart())) .' '. date('Y', $calendars[15]->getStart()); ?>
                </div>
                <div style="text-align: right; display: inline-block; width: 20%; white-space: nowrap;">
                    <a style="padding-right: 2em;" href="<?= $controller->url_for('calendar/single/month', array('atime' => $calendars[sizeof($calendars) - 1]->getEnd() + 1)) ?>">
                        <?= strftime('%B %Y', strtotime('+1 month', $calendars[15]->getStart())) ?>
                        <span style="vertical-align: middle;" <?= tooltip(_('einen Monat vor')) ?>>
                        <?= Assets::img('icons/16/blue/arr_1right.png') ?>
                        </span>
                    </a>
                    <a href="<?= $controller->url_for('calendar/single/month', array('atime' => mktime(12, 0, 0, date('n', $calendars[15]->getStart()), 15, date('Y', $calendars[15]->getEnd()) + 1))) ?>">
                        <?= strftime('%B %Y', strtotime('+1 year', $calendars[15]->getStart())) ?>
                        <span style="vertical-align: middle;" <?= tooltip(_('ein Jahr vor')) ?>>
                        <?= Assets::img('icons/16/blue/arr_2right.png') ?>
                        </span>
                    </a>
                </div>
            </td>
        </tr>
        <tr class="calendar-month-weekdays">
            <? $week_days = array(39092400, 39178800, 39265200, 39351600, 39438000, 39524400, 39610800); ?>
            <? foreach ($week_days as $week_day) : ?>
                <td class="precol1w">
                    <?= strftime('%a', $week_day) ?>
                </td>
            <? endforeach; ?>
            <td align="center" class="precol1w">
                <?= _('Woche') ?>
            </td>
        </tr>
    </thead>
    <tbody>
        <? for ($i = $first_day, $j = 0; $i <= $last_day; $i += 86400, $j++) : ?>
            <? $aday = date('j', $i); ?>
            <?
            $class_day = '';
            if (($aday - $j - 1 > 0) || ($j - $aday > 6)) {
                $class_cell = 'lightmonth';
                $class_day = 'light';
            } elseif (date('Ymd', $i) == date('Ymd')) {
                $class_cell = 'celltoday';
            } else {
                $class_cell = 'month';
            }
            $hday = holiday($i);

            if ($j % 7 == 0) {
                ?><tr><?
            }
            ?>
            <td class="<?= $class_cell ?>">
            <? if (($j + 1) % 7 == 0) : ?>
                <a class="<?= $class_day . 'sday' ?>" href="<?= $controller->url_for('calendar/single/day', array('atime' => $i)) ?>">
                    <?= $aday ?>
                </a>
                <? if ($hday["name"] != "") : ?>
                    <div style="color: #aaaaaa;" class="inday"><?= $hday['name'] ?></div>
                <? endif; ?>
                <? foreach ($calendars[$j]->events as $event) : ?>
                    <div data-tooltip>
                        <a data-dialog="size=auto" title="<?= _('Termin bearbeiten') ?>" class="inday calendar-event-text<?= $event->getCategory() ?>" href="<?= $controller->url_for('calendar/single/edit/' . $event->range_id . '/' . $event->event_id, array('atime' => $event->getStart())) ?>"><?= htmlReady($event->getTitle()) ?></a>
                        <?= $this->render_partial('calendar/single/_tooltip', array('event' => $event)) ?>
                    </div>
                <? endforeach; ?>
                </td>
                    <td class="lightmonth calendar-month-week">
                    <a style="font-weight: bold;" class="calhead" href="<?= $controller->url_for('calendar/single/week', array('atime' => $i)) ?>"><?= strftime("%V", $i) ?></a>
                    </td>
                </tr>
            <? else : ?>
                <? $hday_class = array('day', 'day', 'shday', 'hday') ?>
                <? if ($hday['col']) : ?>
                    <a class="<?= $class_day . $hday_class[$hday['col']] ?>" href="<?= $controller->url_for('calendar/single/day', array('atime' => $i)) ?>">
                        <?= $aday ?>
                    </a>
                    <div style="color: #aaaaaa;" class="inday"><?= $hday['name'] ?></div>
                <? else : ?>
                    <a class="<?= $class_day . 'day' ?>" href="<?= $controller->url_for('calendar/single/day', array('atime' => $i)) ?>">
                        <?= $aday ?>
                    </a>
                <? endif; ?>
                <? foreach ($calendars[$j]->events as $event) : ?>
                    <div data-tooltip>
                        <a data-dialog="size=auto" title="<?= _('Termin bearbeiten') ?>" class="inday calendar-event-text<?= $event->getCategory() ?>" href="<?= $controller->url_for('calendar/single/edit/' . $event->range_id . '/' . $event->event_id, array('atime' => $event->getStart())) ?>"><?= htmlReady($event->getTitle()) ?></a>
                        <?= $this->render_partial('calendar/single/_tooltip', array('event' => $event)) ?>
                    </div>
                <? endforeach; ?>
                </td>
            <? endif; ?>
        <? endfor; ?>
        </tr>
    </tbody>
</table>