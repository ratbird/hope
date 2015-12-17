<? $month = $calendar->view; ?>
<table class="calendar-month">
    <thead>
        <tr>
            <td colspan="8" style="text-align: center; vertical-align: middle;">
                <div style="text-align: left; display: inline-block; white-space: nowrap; width: 33%;">
                    <a style="padding-right: 2em;" href="<?= $controller->url_for('calendar/single/month', array('atime' => strtotime('-1 year', $atime))) ?>">
                        <span style="vertical-align: middle;" <?= tooltip(_('ein Jahr zurück')) ?>>
                        <?= Icon::create('arr_2left', 'clickable')->asImg() ?>
                        </span>
                        <?= strftime('%B %Y', strtotime('-1 year', $atime)) ?>
                    </a>
                    <a href="<?= $controller->url_for('calendar/single/month', array('atime' => strtotime('-1 month', $atime))) ?>">
                        <span style="vertical-align: middle;" <?= tooltip(_('einen Monat zurück')) ?>>
                        <?= Icon::create('arr_1left', 'clickable', ['title' => _('einen Monat zurück')])->asImg() ?>
                        </span>
                        <?= strftime('%B %Y', strtotime('-1 month', $atime)) ?>
                    </a>
                </div>
                <div class="calhead" style="text-align: center; width: 33%; display: inline-block;">
                    <?= htmlReady(strftime("%B ", $calendars[15]->getStart())) .' '. date('Y', $calendars[15]->getStart()); ?>
                </div>
                <div style="text-align: right; display: inline-block; white-space: nowrap; width: 33%;">
                    <a style="padding-right: 2em;" href="<?= $controller->url_for('calendar/single/month', array('atime' => strtotime('+1 month', $atime))) ?>">
                        <?= strftime('%B %Y', strtotime('+1 month', $atime)) ?>
                        <span style="vertical-align: middle;" <?= tooltip(_('einen Monat vor')) ?>>
                        <?= Icon::create('arr_1right', 'clickable')->asImg() ?>
                        </span>
                    </a>
                    <a href="<?= $controller->url_for('calendar/single/month', array('atime' => strtotime('+1 year', $atime))) ?>">
                        <?= strftime('%B %Y', strtotime('+1 year', $atime)) ?>
                        <span style="vertical-align: middle;" <?= tooltip(_('ein Jahr vor')) ?>>
                        <?= Icon::create('arr_2right', 'clickable')->asImg() ?>
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
                        <a data-dialog="size=auto" title="<?= _('Termin bearbeiten') ?>" class="inday <?= $event instanceof CourseEvent ? 'calendar-course-event-text' : 'calendar-event-text' ?><?= $event->getCategory() ?>" href="<?= $controller->url_for('calendar/single/edit/' . $event->range_id . '/' . $event->event_id, array('atime' => $event->getStart())) ?>"><?= htmlReady($event->getTitle()) ?></a>
                        <?= $this->render_partial('calendar/single/_tooltip', array('event' => $event, 'calendar' => $calendars[$j])) ?>
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
                        <a data-dialog="size=auto" title="<?= _('Termin bearbeiten') ?>" class="inday <?= $event instanceof CourseEvent ? 'calendar-course-event-text' : 'calendar-event-text' ?><?= $event->getCategory() ?>" href="<?= $controller->url_for('calendar/single/edit/' . $event->range_id . '/' . $event->event_id, array('atime' => $event->getStart())) ?>"><?= htmlReady($event->getTitle()) ?></a>
                        <?= $this->render_partial('calendar/single/_tooltip', array('event' => $event, 'calendar' => $calendars[$j])) ?>
                    </div>
                <? endforeach; ?>
                </td>
            <? endif; ?>
        <? endfor; ?>
        </tr>
    </tbody>
</table>