<? $month = $calendar->view; ?>

<table width="98%" border="0" cellspacing="0" cellpadding="0" align="center">
    <tr>
        <td>
            <table width="100%" border="0" cellspacing="1" cellpadding="0" align="center">
                <tr>
                    <td>
                        <table width="100%" border="0" cellspacing="1" cellpadding="1">
                            <tr>
                                <td align="center">
                                    <a style="padding-right: 2em;" href="<?= $controller->url_for('calendar/single/month', array('atime' => mktime(12, 0, 0, date('n', $calendars[15]->getStart()), 15, date('Y', $calendars[15]->getStart()) - 1))) ?>">
                                        <?= Assets::img('icons/16/blue/arr_2left.png', tooltip2(_('ein Jahr zurück'))) ?>
                                    </a>
                                    <a href="<?= $controller->url_for('calendar/single/month', array('atime' => $calendars[0]->getStart() - 1)) ?>">
                                        <?= Assets::img('icons/16/blue/arr_1left.png', tooltip2(_('einen Monat zurück'))) ?>
                                    </a>
                                </td>
                                <td colspan="6" class="calhead">
                                    <?= htmlReady(strftime("%B ", $calendars[15]->getStart())) .' '. date('Y', $calendars[15]->getStart()); ?>
                                </td>
                                <td align="center">
                                    <a style="padding-right: 2em;" href="<?= $controller->url_for('calendar/single/month', array('atime' => $calendars[sizeof($calendars) - 1]->getEnd() + 1)) ?>">
                                            <?= Assets::img('icons/16/blue/arr_1right.png', tooltip2(_('einen Monat vor'))) ?>
                                    </a>
                                    <a href="<?= $controller->url_for('calendar/single/month', array('atime' => mktime(12, 0, 0, date('n', $calendars[15]->getStart()), 15, date('Y', $calendars[15]->getEnd()) + 1))) ?>">
                                        <?= Assets::img('icons/16/blue/arr_2right.png', tooltip2(_('ein Jahr vor'))) ?>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <? $week_days = array(39092400, 39178800, 39265200, 39351600, 39438000, 39524400, 39610800); ?>
                                <? foreach ($week_days as $week_day) : ?>
                                    <td class="precol1w" width="90">
                                        <?= strftime('%a', $week_day) ?>
                                    </td>
                                <? endforeach; ?>
                                <td align="center" class="precol1w" width="90">
                                    <?= _('Woche') ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="blank">
                        <table width="100%" border="0" cellspacing="1" cellpadding="1">
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
                                <td class="<?= $class_cell ?>" valign="top" width="90" height="80">
                                <? if (($j + 1) % 7 == 0) : ?>
                                    <a class="<?= $class_day . 'sday' ?>" href="<?= $controller->url_for('calendar/single/day', array('atime' => $i)) ?>">
                                        <?= $aday ?>
                                    </a>
                                    <? if ($hday["name"] != "") : ?>
                                        <span style="color: #aaaaaa; display: block;" class="inday"><?= $hday['name'] ?></span>
                                    <? endif; ?>
                                    <? foreach ($calendars[$j]->events as $event) : ?>
                                        <? $category_style = $event->getCategoryStyle(); ?>
                                        <span style="color: <?= $category_style['color'] ?>; display: block;"><a class="inday" href="<?= $controller->url_for('', array('atime' => $event->getStart())) ?>"><?= $event->getTitle() ?></a></span>
                                    <? endforeach; ?>
                                    </td>
                                        <td class="lightmonth" align="center" width="90" height="80">
                                        <a style="font-weight: bold;" class="calhead" href="<?= $controller->url_for('calendar/single/week', array('atime' => $i)) ?>"><?= strftime("%V", $i) ?></a>
                                        </td>
                                    </tr>
                                <? else : ?>
                                    <? $hday_class = array('day', 'day', 'shday', 'hday') ?>
                                    <? if ($hday['col']) : ?>
                                        <a class="<?= $class_day . $hday_class[$hday['col']] ?>" href="<?= $controller->url_for('calendar/single/day', array('atime' => $i)) ?>">
                                            <?= $aday ?>
                                        </a>
                                        <span style="color: #aaaaaa; display: block;" class="inday"><?= $hday['name'] ?></span>
                                    <? else : ?>
                                        <a class="<?= $class_day . 'day' ?>" href="<?= $controller->url_for('calendar/single/day', array('atime' => $i)) ?>">
                                            <?= $aday ?>
                                        </a>
                                    <? endif; ?>
                                    <? foreach ($calendars[$j]->events as $event) : ?>
                                        <? $category_style = $event->getCategoryStyle(); ?>
                                        <span style="color: <?= $category_style['color'] ?>; display: block;"><a class="inday" href="<?= $controller->url_for('', array('atime' => $event->getStart())) ?>"><?= $event->getTitle() ?></a></span>
                                    <? endforeach; ?>
                                    </td>
                                <? endif; ?>
                            <? endfor; ?>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>