<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/**
 * calendar_visual.inc.php
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Peter Thienel <thienel@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     calendar
 */
require_once('lib/visual.inc.php');
require_once('lib/calendar_functions.inc.php');
require_once('lib/functions.php');

function createEventMatrix($day_obj, $start, $end, $step, $params = NULL)
{
    $em = array();
    $term = array();

    $em = adapt_events($day_obj, $start, $end, $step);

    // calculate maximum number of columns
    $w = 0;
    for ($i = $start / $step; $i < $end / $step + 3600 / $step; $i++) {
        $col = 0;
        $row = $i - $start / $step;
        while ($w < sizeof($em['events']) && $em['events'][$w]->getStart() >= $day_obj->getStart() + $i * $step
        && $em['events'][$w]->getStart() < $day_obj->getStart() + ($i + 1) * $step) {
            $rows = ceil($em['events'][$w]->getDuration() / $step);
            if ($rows < 1) {
                $rows = 1;
            }

            while ($term[$row][$col] != '' && $term[$row][$col] != '#') {
                $col++;
            }

            $term[$row][$col] = $em['events'][$w];
            $mapping[$row][$col] = $em['map'][$w];

            $count = $rows - 1;
            for ($x = $row + 1; $x < $row + $rows; $x++) {
                for ($y = 0; $y <= $col; $y++) {
                    if ($y == $col) {
                        $term[$x][$y] = $count--;
                    } elseif ($term[$x][$y] == '') {
                        $term[$x][$y] = '#';
                    }
                }
            }
            if ($max_cols < sizeof($term[$row])) {
                $max_cols = sizeof($term[$row]);
            }
            $w++;
        }
    }

    $row_min = 0;
    for ($i = $start / $step; $i < $end / $step + 3600 / $step; $i++) {
        $row = $i - $start / $step;
        $row_min = $row;

        while (maxValue($term[$row], $step) > 1) {
            $row += maxValue($term[$row], $step) - 1;
        }

        $size = 0;
        for ($j = $row_min; $j <= $row; $j++) {
            if (sizeof($term[$j]) > $size) {
                $size = sizeof($term[$j]);
            }
        }

        for ($j = $row_min; $j <= $row; $j++) {
            $colsp[$j] = $size;
        }

        $i = $row + $start / $step;
    }

    $rows = array();
    for ($i = $start / $step; $i < $end / $step + 3600 / $step; $i++) {
        $row = $i - $start / $step;
        $cspan_0 = 0;
        if ($term[$row]) {
            if ($colsp[$row] > 0) {
                $cspan_0 = (int) ($max_cols / $colsp[$row]);
            }

            for ($j = 0; $j < $colsp[$row]; $j++) {
                $sp = 0;
                $n = 0;
                if ($j + 1 == $colsp[$row]) {
                    $cspan[$row][$j] = $cspan_0 + $max_cols % $colsp[$row];
                }

                if (is_object($term[$row][$j])) {
                    // Wieviele Termine sind zum aktuellen Termin zeitgleich?
                    $p = 0;
                    $count = 0;
                    while ($aterm = $em['events'][$p]) {
                        if ($aterm->getStart() >= $term[$row][$j]->getStart()
                                && $aterm->getStart() <= $term[$row][$j]->getEnd()) {
                            $count++;
                        }
                        $p++;
                    }

                    if ($count == 0) {
                        for ($n = $j + 1; $n < $colsp[$row]; $n++) {
                            if (!is_int($term[$row][$n])) {
                                $sp++;
                            } else {
                                break;
                            }
                        }
                        $cspan[$row][$j] += $sp;
                    }
                    $rows[$row][$j] = ceil($term[$row][$j]->getDuration() / $step);
                    if ($rows[$row][$j] < 1) {
                        $rows[$row][$j] = 1;
                    }
                    if ($sp > 0) {
                        for ($m = $row; $m < $rows + $row; $m++) {
                            $colsp[$m] = $colsp[$m] - $sp + 1;
                            $v = $j;
                            while ($term[$m][$v] == '#') {
                                $term[$m][$v] = 1;
                            }
                        }
                        $j = $n;
                    }
                } elseif ($term[$row][$j] == '#') {
                    $csp = 1;
                    while ($term[$row][$j] == '#') {
                        $csp += $cspan[$row][$j];
                        $j++;
                    }
                    $cspan[$row][$j] = $csp;
                } elseif ($term[$row][$j] == '') {
                    $cspan[$row][$j] = $max_cols - $j + 1;
                }
            }
        }
    }
    $em['cspan'] = $cspan;
    $em['rows'] = $rows;
    $em['colsp'] = $colsp;
    $em['term'] = $term;
    $em['max_cols'] = $max_cols;
    $em['mapping'] = $mapping;

    return $em;
}

function maxValue($term, $st)
{
    $max_value = 0;
    for ($i = 0; $i < sizeof($term); $i++) {
        if (is_object($term[$i]))
            $max = ceil($term[$i]->getDuration() / $st);
        elseif ($term[$i] == '#')
            continue;
        elseif ($term[$i] > $max_value)
            $max = $term[$i];
        if ($max > $max_value)
            $max_value = $max;
    }

    return $max_value;
}

function adapt_events($day_obj, $start, $end, $step = 900)
{
    // Die Generierung der Tabellenansicht erfolgt mit Hilfe kopierter Termine,
    // da die Anfangs- und Endzeiten zur korrekten Darstellung evtl. angepasst
    // werden muessen
    for ($i = 0; $i < sizeof($day_obj->events); $i++) {
        if (($day_obj->events[$i]->getEnd() >= $day_obj->getStart() + $start)
                && ($day_obj->events[$i]->getStart() < $day_obj->getStart() + $end + 3600)) {

            if ($day_obj->events[$i]->isDayEvent()
                    || ($day_obj->events[$i]->getStart() <= $day_obj->getStart()
                    && $day_obj->events[$i]->getEnd() >= $day_obj->getEnd())) {
                $cloned_day_event = clone $day_obj->events[$i];
                $cloned_day_event->setStart($day_obj->getStart());
                $cloned_day_event->setEnd($day_obj->getEnd());
                $tmp_day_event[] = $cloned_day_event;
                $map_day_events[] = $i;
            } else {
                $cloned_event = clone $day_obj->events[$i];
                $end_corr = $cloned_event->getEnd() % $step;
                if ($end_corr > 0) {
                    $end_corr = $cloned_event->getEnd() + ($step - $end_corr);
                    $cloned_event->setEnd($end_corr);
                }
                if ($cloned_event->getStart() < ($day_obj->getStart() + $start))
                    $cloned_event->setStart($day_obj->getStart() + $start);
                if ($cloned_event->getEnd() > ($day_obj->getStart() + $end + 3600))
                    $cloned_event->setEnd($day_obj->getStart() + $end + 3600);

                $tmp_event[] = $cloned_event;
                $map_events[] = $i;
            }
        }
    }

    return array('events' => $tmp_event, 'map' => $map_events, 'day_events' => $tmp_day_event,
        'day_map' => $map_day_events);
}

function create_month_view(&$calendar, $atime, $step = NULL)
{

    $month = $calendar->view;

    $out = "<table width=\"98%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">\n";
    $out .= "<tr><td>\n";
    $out .= "<table width=\"100%\" class=\"steelgroup0\" border=\"0\" cellspacing=\"1\" cellpadding=\"0\" align=\"center\">\n";
    $out .= "<tr><td>\n";

    $out .= "<table width=\"100%\" border=\"0\" cellspacing=\"1\" cellpadding=\"1\">\n";
    $out .= "<tr>\n<td align=\"center\">";
    $out .= '&nbsp;<a href="' . URLHelper::getLink('', array('cmd' => 'showmonth', 'atime' => mktime(12, 0, 0, $month->getMonth(), date('j', $month->getStart()), date('Y', $month->getStart()) - 1))) . '">';
    $out .= Assets::img('icons/16/blue/arr_2left.png', tooltip2(_("ein Jahr zurück"))) . '</a>';
    $out .= '&nbsp; &nbsp; &nbsp; &nbsp;<a href="' . URLHelper::getLink('', array('cmd' => 'showmonth', 'atime' => $month->getStart() - 1)) . '">';
    $out .= Assets::img('icons/16/blue/arr_1left.png', tooltip2(_("einen Monat zurück"))) . "</a>&nbsp;</td>\n";
    $out .= sprintf("<td colspan=\"%s\" class=\"calhead\">\n", $mod == "nokw" ? "5" : "6");
    $out .= '<font size="+2">';
    $out .= htmlentities(strftime("%B ", $month->getStart()), ENT_QUOTES) . $month->getYear();
    $out .= "</font></td>\n";
    $out .= '<td align="center">&nbsp;<a href="' . URLHelper::getLink('', array('cmd' => 'showmonth', 'atime' => $month->getEnd() + 1)) . '">';
    $out .= Assets::img('icons/16/blue/arr_1right.png', tooltip2(_("einen Monat vor"))) . '</a>';
    $out .= '&nbsp; &nbsp; &nbsp; &nbsp;<a href="' . URLHelper::getLink('', array('cmd' => 'showmonth', 'atime' => mktime(12, 0, 0, $month->getMonth(), date('j', $month->getStart()), date('Y', $month->getEnd()) + 1))) . '">';
    $out .= Assets::img('icons/16/blue/arr_2right.png', tooltip2(_("ein Jahr vor"))) . '</a></td>';
    $out .= "</tr>\n<tr>\n";

    $weekdays_german = array('MO', 'DI', 'MI', 'DO', 'FR', 'SA', 'SO');
    foreach ($weekdays_german as $weekday_german) {
        $out .= '<td class="precol1w" width="90">' . wday('', 'SHORT', $weekday_german) . '</td>';
    }

    if ($mod != 'nokw') {
        $out .= "<td align=\"center\" class=\"precol1w\" width=\"90\">" . _("Woche") . "</td>\n";
    }
    $out .= "</tr></table>\n</td></tr>\n";
    $out .= "<tr><td class=\"blank\">\n";
    $out .= "<table width=\"100%\" class=\"steelgroup0\" border=\"0\" cellspacing=\"1\" cellpadding=\"1\">\n";

    // Im Kalenderblatt ist links oben immer Montag. Das muss natuerlich nicht der
    // Monatserste sein. Es muessen evtl. noch Tage des vorangegangenen Monats
    // am Anfang und des folgenden Monats am Ende angefuegt werden.

    $adow = strftime("%u", $month->getStart()) - 1;

    $first_day = $month->getStart() - $adow * 86400 + 43200;
    // Ist erforderlich, um den Maerz richtig darzustellen
    // Ursache ist die Sommer-/Winterzeit-Umstellung
    $cor = 0;
    if ($month->getMonth() == 3) {
        $cor = 1;
    }

    $last_day = ((42 - ($adow + date("t", $month->getStart()))) % 7 + $cor) * 86400
            + $month->getEnd() - 43199;

    for ($i = $first_day, $j = 0; $i <= $last_day; $i += 86400, $j++) {
        $aday = date('j', $i);
        // Tage des vorangehenden und des nachfolgenden Monats erhalten andere
        // style-sheets
        $class_day = '';
        if (($aday - $j - 1 > 0) || ($j - $aday > 6)) {
            $class_cell = 'lightmonth';
            $class_day = 'light';
        } elseif (date('Ymd', $i) == date('Ymd')) { // emphesize current day
            $class_cell = 'celltoday';
        } else {
            $class_cell = 'month';
        }

        // Feiertagsueberpruefung
        if ($mod != 'compact' && $mod != 'nokw') {
            $hday = holiday($i);
        }

        // week column
        if ($j % 7 == 0) {
            $out .= "<tr>\n";
        }
        $out .= "<td class=\"$class_cell\" valign=\"top\" width=\"90\" height=\"80\">&nbsp;";

        // sunday column
        if (($j + 1) % 7 == 0) {
            $out .= '<a class="' . $class_day . 'sday" href="' . URLHelper::getLink('', array('cmd' => 'showday', 'atime' => $i)) . '">' . $aday . '</a>';

            if ($hday["name"] != "")
                $out .= "<br><span style=\"color: #AAAAAA;\" class=\"inday\">{$hday['name']}</span>";

            $out .= to_string_month_events($calendar, $i);

            $out .= "</td>\n";

            if ($mod != 'nokw') {
                $out .= "<td class=\"lightmonth\" align=\"center\" width=\"90\" height=\"80\">";
                $out .= '<a style="font-weight:bold;" class="calhead" href="' . URLHelper::getLink('', array('cmd' => 'showweek', 'atime' => $i)) . '">' . strftime("%V", $i) . '</a></td>';
            }
            $out .= "</tr>\n";
        } else {
            // other days columns
            // unterschiedliche Darstellung je nach Art des Tages (Rang des Feiertages)
            $up_down_nav = '';
            switch ($hday['col']) {
                case 1:
                    $out .= '<a class="' . $class_day . 'day" href="' . URLHelper::getLink('', array('cmd' => 'showday', 'atime' => $i)) . '">' . $aday . '</a>';
                    $out .= $up_down_nav;
                    $out .= "<br><span style=\"color: #AAAAAA;\" class=\"inday\">{$hday['name']}</span>";
                    break;
                case 2:
                    $out .= '<a class="' . $class_day . 'shday" href="' . URLHelper::getLink('', array('cmd' => 'showday', 'atime' => $i)) . '">' . $aday . '</a>';
                    $out .= $up_down_nav;
                    $out .= "<br><span style=\"color: #AAAAAA;\" class=\"inday\">{$hday['name']}</span>";
                    break;
                case 3:
                    $out .= '<a class="' . $class_day . 'hday" href="' . URLHelper::getLink('', array('cmd' => 'showday', 'atime' => $i)) . '">' . $aday . '</a>';
                    $out .= $up_down_nav;
                    $out .= "<br><span style=\"color: #AAAAAA;\" class=\"inday\">{$hday['name']}</span>";
                    break;
                default:
                    $out .= '<a class="' . $class_day . 'day" href="' . URLHelper::getLink('', array('cmd' => 'showday', 'atime' => $i)) . '">' . $aday . '</a>';
                    $out .= $up_down_nav;
            }

            $out .= to_string_month_events($calendar, $i);
            $out .= "</td>\n";
        }
    }

    $out .= "</td></tr></table>\n";
    $out .= "</td></tr>\n";
    $out .= "<tr><td>&nbsp;</td></tr>\n";
    $out .= "</table></td></tr></table>\n";

    return $out;
}

/**
 * Print a list of events for each day of month
 *
 * @access public
 * @param object $month_obj instance of DbCalendarMonth
 * @param int $max_events the number of events to print
 * @param int $day_timestamp unix timestamp of the day
 */
function to_string_month_events(&$calendar, $day_timestamp, $max_events = NULL)
{
    if (is_null($max_events)) {
        $max_events = 100;
    }
    $out = '';
    $count = 0;
    if ($calendar instanceof GroupCalendar) {
        for ($i = 0; $i < sizeof($calendar->calendars); $i++) {
            $events = $calendar->calendars[$i]->view->getEventsOfDay($day_timestamp);
            if (sizeof($events) && $count < $max_events) {
                $js_hover = js_hover_group($events, $calendar->calendars[$i]->view->getStart(),
                        $calendar->calendars[$i]->view->getEnd(), $calendar->calendars[$i]->getUserId());
                $out .= '<br><a class="inday" href="' . URLHelper::getLink('', array('cmd' => 'showday', 'cal_user' => get_username($calendar->calendars[$i]->getUserId()), 'atime' => $day_timestamp)) . '" ' . $js_hover .'>';
                $out .= fit_title(get_fullname($calendar->calendars[$i]->getUserId(), 'no_title_rev'), 1, 1, 15) . "</a>";
                $count++;
            }
        }
    } else {
        $month = $calendar->view;
        while (($event = $month->nextEvent($day_timestamp)) && $count < $max_events) {
            if ($event instanceof SeminarEvent) {
                $html_title = fit_title($event->getSemName(), 1, 1, 15);
                $ev_type = 'sem';
            } elseif ($event instanceof SeminarCalendarEvent) {
                $html_title = fit_title($event->getTitle(), 1, 1, 15);
                $ev_type = 'semcal';
            } else {
                $html_title = fit_title($event->getTitle(), 1, 1, 15);
                $ev_type = '';
            }
	    $out .= '<br><a class="inday" href="'
                    . URLHelper::getLink('', array('cmd' => 'edit',
                        'termin_id' => $event->getId(),
                        'atime' => $day_timestamp,
                        'evtype' => $ev_type))
                    . '"' . js_hover($event) . '>';
	    $category_style = $event->getCategoryStyle();
            $out .= sprintf("<span style=\"color: %s;\">%s</span></a>", $category_style['color'], $html_title);
            $count++;
        }
    }

    return $out;
}

function create_year_view(&$calendar)
{
    $year = $calendar->view;

    $out = "<table class=\"steelgroup0\" width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
    $out .= "<tr><td align=\"center\" width=\"10%\">\n";
    $out .= '<a href="' . URLHelper::getLink('', array('', 'cmd' => 'showyear', 'atime' => $year->getStart() - 1)) . '">';
    $out .= Assets::img('icons/16/blue/arr_1left.png', tooltip2(_("zurück"))). "&nbsp;</a></td>\n";
    $out .= "<td class=\"calhead\" align=\"center\" width=\"80%\">\n";
    $out .= "<font size=\"+2\"><b>" . $year->getYear() . "</b></font></td>\n";
    $out .= '<td align="center" width="10%"><a href="' . URLHelper::getLink('', array('', 'cmd' => 'showyear', 'atime' => $year->getEnd() + 1)) . '">';
    $out .= Assets::img('icons/16/blue/arr_1right.png', tooltip2(_("vor"))) . '&nbsp;</a></td>';
    $out .= "</tr>\n";
    $out .= "<tr><td colspan=\"3\" class=\"blank\">";
    $out .= '<table class="steelgroup0" width="100%" border="0" ';
    $out .= "cellpadding=\"2\" cellspacing=\"1\">\n";

    $days_per_month = array(31, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    if (date('L', $year->getStart())) {
        $days_per_month[2]++;
    }

    $out .= '<tr>';
    for ($i = 1; $i < 13; $i++) {
        $ts_month += ( $days_per_month[$i] - 1) * 86400;
        $out .= '<td align="center" width="8%">';
        $out .= '<a class="calhead" href="' . URLHelper::getLink('', array('cmd' => 'showmonth', 'atime' => $year->getStart() + $ts_month)) . '">';
        $out .= '<font size="-1"><b>';
        $out .= htmlentities(strftime("%B", $ts_month), ENT_QUOTES);
        $out .= "</b></font></a></td>\n";
    }
    $out .= "</tr>\n";

    $now = date('Ymd');
    for ($i = 1; $i < 32; $i++) {
        $out .= '<tr>';
        for ($month = 1; $month < 13; $month++) {
            $aday = mktime(12, 0, 0, $month, $i, $year->getYear());

            if ($i <= $days_per_month[$month]) {
                $wday = date('w', $aday);
                // emphesize current day
                if (date('Ymd', $aday) == $now)
                    $day_class = ' class="celltoday"';
                else if ($wday == 0 || $wday == 6)
                    $day_class = ' class="weekend"';
                else
                    $day_class = ' class="weekday"';

                if ($month == 1)
                    $out .= "<td$day_class height=\"25\">";
                else
                    $out .= "<td$day_class>";

                $event_count_txt = javascript_hover_year($calendar, $aday);
                if ($event_count_txt != '') {
                    $out .= "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\"><tr>";
                    $out .= "<td$day_class>";
                }

                $weekday = '<font size="2">' . wday($aday, 'SHORT') . '</font>';

                $hday = holiday($aday);
                switch ($hday['col']) {

                    case "1":
                        if (date("w", $aday) == "0") {
                            $out .= '<a style="font-weight:bold;" class="sday" href="' . URLHelper::getLink('', array('cmd' => 'showday', 'atime' => $aday)) . '">';
                            $out .= "$i</a> " . $weekday;
                            $count++;
                        } else {
                            $out .= '<a style="font-weight:bold;" class="day" href="' . URLHelper::getLink('', array('cmd' => 'showday', 'atime' => $aday)) . '">';
                            $out .= "$i</a> " . $weekday;
                        }
                        break;
                    case "2":
                    case "3":
                        if (date("w", $aday) == "0") {
                            $out .= '<a style="font-weight:bold;" class="sday" href="' . URLHelper::getLink('', array('cmd' => 'showday', 'atime' => $aday)) . '">';
                            $out .= "$i</a> " . $weekday;
                            $count++;
                        } else {
                            $out .= '<a style="font-weight:bold;" class="hday" href="' . URLHelper::getLink('', array('cmd' => 'showday', 'atime' => $aday)) . '">';
                            $out .= "$i</a> " . $weekday;
                        }
                        break;
                    default:
                        if (date("w", $aday) == "0") {
                            $out .= '<a style="font-weight:bold;" class="sday" href="' . URLHelper::getLink('', array('cmd' => 'showday', 'atime' => $aday)) . '">';
                            $out .= "$i</a> " . $weekday;
                            $count++;
                        } else {
                            $out .= '<a style="font-weight:bold;" class="day" href="' . URLHelper::getLink('', array('cmd' => 'showday', 'atime' => $aday)) . '">';
                            $out .= "$i</a> " . $weekday;
                        }
                }

                if ($event_count_txt != '') {
                    $out .= "</td><td$day_class align=\"right\">";
                    $out .= '<img src="' . Assets::image_path('icons/16/blue/date.png') . '" ';
                    $out .= $event_count_txt . '>';
                    $out .= "</td></tr></table>\n";
                }
                $out .= '</td>';
            }
            else
                $out .= '<td class="weekday">&nbsp;</td>';
        }
        $out .= "</tr>\n";
    }
    $out .= '<tr>';
    $ts_month = 0;
    for ($i = 1; $i < 13; $i++) {
        $ts_month += ( $days_per_month[$i] - 1) * 86400;
        $out .= "<td align=\"center\" width=\"8%%\">";
        $out .= '<a class="calhead" href="' . URLHelper::getLink('', array('cmd' => 'showmonth', 'atime' => $year->getStart() + $ts_month)) . '">';
        $out .= "<font size=\"-1\"><b>";
        $out .= htmlentities(strftime("%B", $ts_month), ENT_QUOTES);
        $out .= "</b></font></a></td>\n";
    }
    $out .= "</tr></table>\n</td></tr></table>\n";

    return $out;
}

function javascript_hover_year(&$calendar, $day_time)
{
    global $forum;

    $out = '';
    $event_count_txt = array();
    if ($calendar instanceof GroupCalendar) {
        foreach ($calendar->calendars as $user_calendar) {
            if ($event_count = $user_calendar->view->numberOfEvents($day_time)) {
                if ($event_count > 1) {
                    $txt = _("%s hat %s Termine");
                } else {
                    $txt = _("%s hat 1 Termin");
                }

                if ($forum['jshover'] == 1) {
                    $event_count_txt[] = sprintf($txt, '<b>' . get_fullname($user_calendar->getUserId(), 'no_title_rev') . '</b>', $event_count);
                } else {
                    $event_count_txt[] = sprintf($txt, get_fullname($user_calendar->getUserId(), 'no_title_rev'), $event_count);
                }
            }
        }
        if (sizeof($event_count_txt)) {
            if ($forum['jshover'] == 1) {
                $js_title = sprintf(_("Termine am %s"), strftime('%x, ', $day_time));
                $out .= implode('<hr>', $event_count_txt);
                $out = " onmouseover=\"STUDIP.CalendarDialog.openCalendarHover('" . JSReady($js_title) . "', '" . JSReady($out, 'contact') . "', this);\" onmouseout=\"STUDIP.CalendarDialog.closeCalendarHover();\"";
            } else {
                $out .= implode('; ', $event_count_txt);
                $out = tooltip($out);
            }
        }
    } else {
        $event_count = $calendar->view->numberOfEvents($day_time);
        if ($event_count > 1) {
            $out = tooltip(sprintf(_("%s Termine"), $event_count));
        } elseif ($event_count == 1) {
            $out = tooltip(_("1 Termin"));
        }
    }

    return $out;
}

/**
 * Creates a small month view.
 *
 * @access public
 * @param int $imt A unix time stamp within the time range of the month.
 * @param string $href The part of the query with parameters needed for the script where this calendar is embedded.
 * @param int $mod Possible modifications are: 'NOKW' hide calendar weeks; 'NONAVARROWS': hide navigation arrows;<br>
 * 'NONAV': calendar weeks will not be linked to the calendars week view.
 * @param string $js_include Java Script triggered by onClick event handler.
 * @param int $ptime The day with this time stamp gets a red border.
 */
function includeMonth($imt, $href, $mod = '', $js_include = '', $ptime = '')
{
    require_once($GLOBALS['RELATIVE_PATH_CALENDAR'] . '/lib/CalendarMonth.class.php');

    $amonth = new CalendarMonth($imt);
    $now = mktime(12, 0, 0, date('n', time()), date('j', time()), date('Y', time()), 0);
    $width = '25';
    $height = '25';

    $ret = "<table valign=\"top\" class=\"blank\" border=\"0\" cellspacing=\"1\" cellpadding=\"0\">\n";
    $ret .= "<tr><td class=\"steelgroup0\" align=\"center\">\n";
    $ret .= "<table border=\"0\" cellspacing=\"1\" cellpadding=\"1\">\n";
    $ret .= "<tr>\n";
    $ret .= '<td colspan="' . ($mod == 'NOKW' ? '7' : '8') . "\" align=\"center\" class=\"steelgroup0\" valign=\"top\" style=\"white-space:nowrap;\">\n";
    // navigation arrows left
    $ret .= '<div style="float:left; width:15%;">';
    if ($mod == 'NONAV' || $mod == 'NONAVARROWS') {
        $ret .= '&nbsp;';
    } else {
        $ret .= '<a href="' . URLHelper::getLink($href . $ptime, array('imt' => mktime(12, 0, 0, $amonth->mon, 1, $amonth->year - 1))) . '">';
        $ret .= Assets::img('icons/16/blue/arr_2left.png', tooltip2(_("ein Jahr zurück"))) . '</a>';
        $ret .= '<a href="' . URLHelper::getLink($href . $ptime, array('imt' => mktime(12, 0, 0, $amonth->mon - 1, 1, $amonth->year))) . '">';
        $ret .= Assets::img('icons/16/blue/arr_1left.png', tooltip2(_("einen Monat zurück"))) . "</a>\n";
    }
    $ret .= '</div><div class="precol1w" style="float:left; text-align:center; width:70%;">';

    // month and year
    $ret .= sprintf("%s %s\n", htmlentities(strftime("%B", $amonth->getStart()), ENT_QUOTES), $amonth->getYear());
    $ret .= '</div><div style="float:right; width:15%;">';
    // navigation arrows right
    if ($mod == 'NONAV' || $mod == 'NONAVARROWS') {
        $ret .= '&nbsp;';
    } else {
        $ret .= '<a href="' . URLHelper::getLink($href . $ptime, array('imt' => mktime(12, 0, 0, $amonth->mon + 1, 1, $amonth->year))) . '">';
        $ret .= Assets::img('icons/16/blue/arr_1right.png', tooltip2(_("einen Monat vor"))) . '</a>';
        $ret .= '<a href="' . URLHelper::getLink($href . $ptime, array('imt' => mktime(12, 0, 0, $amonth->mon, 1, $amonth->year + 1))) . '">';
        $ret .= Assets::img('icons/16/blue/arr_2right.png', tooltip2(_("ein Jahr vor"))) . "</a>\n";
    }
    $ret .= "</div></td></tr>\n";

    // weekdays
    $ret .= "<tr>\n";
    $day_names_german = array('MO', 'DI', 'MI', 'DO', 'FR', 'SA', 'SO');
    foreach ($day_names_german as $day_name_german)
        $ret .= "<td align=\"center\" class=\"precol2w\" width=\"$width\">" . wday("", "SHORT", $day_name_german) . "</td>\n";
    if ($mod != 'NOKW')
        $ret .= "<td class=\"precol2w\" width=\"$width\">&nbsp;</td>";
    $ret .= "</tr>\n</table></td></tr>\n<tr><td class=\"blank\">";
    $ret .= "<table class=\"blank\" border=\"0\" cellspacing=\"1\" cellpadding=\"1\">";

    // Im Kalenderblatt ist links oben immer Montag. Das muss natuerlich nicht der
    // Monatserste sein. Es muessen evtl. noch Tage des vorangegangenen Monats
    // am Anfang und des folgenden Monats am Ende angefuegt werden.
    $adow = date('w', $amonth->getStart());
    if ($adow == 0)
        $adow = 6;
    else
        $adow--;
    $first_day = $amonth->getStart() - $adow * 86400 + 43200;
    // Ist erforderlich, um den Maerz richtig darzustellen
    // Ursache ist die Sommer-/Winterzeit-Umstellung
    $cor = 0;
    if ($amonth->mon == 3)
        $cor = 1;

    $last_day = ((42 - ($adow + date("t", $amonth->getStart()))) % 7 + $cor) * 86400
            + $amonth->getEnd() - 43199;

    for ($i = $first_day, $j = 0; $i <= $last_day; $i += 86400, $j++) {
        $aday = date("j", $i);
        // Tage des vorangehenden und des nachfolgenden Monats erhalten andere
        // style-sheets
        $style = '';
        if (($aday - $j - 1 > 0) || ($j - $aday > 6))
            $style = 'light';

        // Feiertagsueberpruefung
        $hday = holiday($i);

        if ($j % 7 == 0)
            $ret .= '<tr>';

        if (abs($now - $i) < 43199 && !($mod == 'NONAV' && $style == 'light'))
            $ret .= "<td class=\"celltoday\" ";
        elseif (date('m', $i) != $amonth->mon)
            $ret .= "<td class=\"lightmonth\"";
        else
            $ret .= "<td class=\"month\"";

        $ret .= "align=\"center\" width=\"$width\" height=\"$height\">";

        $js_inc = '';
        if (is_array($js_include)) {
            $js_inc = " onClick=\"{$js_include['function']}(";
            if (sizeof($js_include['parameters']))
                $js_inc .= implode(", ", $js_include['parameters']) . ", ";
            $js_inc .= "'" . date('m', $i) . "', '$aday', '" . date('Y', $i) . "')\"";
        }
        if (abs($ptime - $i) < 43199) {
            $aday = "<span style=\"border-width: 2px; border-style: solid; "
                    . "border-color: #DD0000; padding: 2px;\">$aday</span>";
        }

        if (($j + 1) % 7 == 0) {
            if ($mod == 'NONAV' && $style == 'light') {
                $ret .= '&nbsp;'; // Tag gehört nicht zu diesem Monat
            } else {
                $ret .= "<a class=\"{$style}sdaymin\" href=\"$href$i\"";
                if ($hday['name'])
                    $ret .= ' ' . tooltip($hday['name']);
                $ret .= "$js_inc>$aday</a>";
            }
            $ret .= "</td>\n";

            if ($mod != 'NOKW') {
                $ret .= " <td class=\"steel1\" align=\"center\" width=\"$width\" height=\"$height\">";
                if ($mod != 'NONAV')
                    $ret .= "<a href=\"./calendar.php?cmd=showweek&atime=$i\">";
                $ret .= "<font class=\"kwmin\">" . strftime("%V", $i) . "</font>";
                if ($mod != 'NONAV')
                    $ret .= '</a>';
                $ret .= '</td>';
            }
            $ret .= "</tr>\n";
        }
        else {
            if ($mod == 'NONAV' && $style == 'light') {
                $ret .= '&nbsp;'; // Tag gehört nicht zu diesem Monat
            } else {
                // unterschiedliche Darstellung je nach Art des Tages (Rang des Feiertages)
                switch ($hday['col']) {
                    case 1:
                        $ret .= "<a class=\"{$style}daymin\" href=\"$href$i\" ";
                        $ret .= tooltip($hday['name']) . "$js_inc>$aday</a>";
                        break;
                    case 2:
                    case 3;
                        $ret .= "<a class=\"{$style}hdaymin\" href=\"$href$i\" ";
                        $ret .= tooltip($hday['name']) . "$js_inc>$aday</a>";
                        break;
                    default:
                        $ret .= "<a class=\"{$style}daymin\" href=\"$href$i\"$js_inc>$aday</a>";
                }
            }
            $ret .= "</td>\n";
        }
    }
    $ret .= "</table>\n</td></tr>\n";
    $ret .= "</table>\n";
    return $ret;
}

function fit_title($title, $cols, $rows, $max_length, $end_str = "...", $pad = TRUE)
{
    global $auth;
    if ($auth->auth['jscript'])
        $max_length = $max_length * ($auth->auth['xres'] / 1024);

    $title_length = strlen($title);
    $length = ceil($max_length / $cols);
    $new_title = substr($title, 0, $length * $rows);

    if (strlen($new_title) < $title_length)
        $new_title = substr($new_title, 0, - (strlen($end_str))) . $end_str;

    $new_title = htmlentities(chunk_split($new_title, $length, "\n"), ENT_QUOTES);
    $new_title = substr(str_replace("\n", '<br>', $new_title), 0, -4);

    if ($pad && $title_length < $length)
        $new_title .= str_repeat('&nbsp;', $length - $title_length);

    return $new_title;
}




function js_hover(Event $aterm)
{
    global $user;

    $jscript_text = '<b>' . _("Zusammenfassung:") . ' </b>'
            . htmlReady($aterm->getTitle()) . '<hr>';

    if ($aterm instanceof SeminarEvent || $aterm instanceof SeminarCalendarEvent) {
        $jscript_text .= '<b>' . _("Veranstaltung:") . ' </b> '
                . htmlReady($aterm->getSemName()) . '<br>';
    }
    if ($aterm->getDescription()) {
        $jscript_text .= '<b>' . _("Beschreibung:") . ' </b> '
                . htmlReady(mila($aterm->getDescription(), 200)) . '<br>';
    }
    if ($categories = $aterm->toStringCategories()) {
        $jscript_text .= '<b>' . _("Kategorie:") . ' </b> '
                . htmlReady($categories) . '<br>';
    }
    if ($aterm->getLocation()) {
        $jscript_text .= '<b>' . _("Ort:") . ' </b> '
                . htmlReady($aterm->getLocation()) . '<br>';
    }
    if (!($aterm instanceof SeminarEvent)) {
        if ($aterm->toStringPriority()) {
            $jscript_text .= '<b>' . _("Priorit&auml;t:") . ' </b>'
                    . htmlReady($aterm->toStringPriority()) . '<br>';
        }
        $jscript_text .= '<b>' . _("Zugriff:") . ' </b>'
                . htmlReady($aterm->toStringAccessibility()) . '<br>';
        $jscript_text .= '<b>' . _("Wiederholung:") . ' </b>'
                . htmlReady($aterm->toStringRecurrence()) . '<br>';
    }
    
    if (!($aterm instanceof SeminarEvent)) {
        if (get_config('CALENDAR_GROUP_ENABLE')) {
            $jscript_text .= sprintf(_('<span style="font-weight: bold;">Eingetragen am:</span> %s von %s'),
                strftime('%x, %X', $aterm->getMakeDate()),
                    htmlReady(get_fullname($aterm->getAuthorId(), 'no_title')))
                . '<br>';
            if ($aterm->getMakeDate() < $aterm->getChangeDate()) {
                $jscript_text .= sprintf(_('<span style="font-weight: bold;">Zuletzt bearbeitet am:</span> %s von %s'),
                    strftime('%x, %X', $aterm->getChangeDate()),
                        htmlReady(get_fullname($aterm->getEditorId(), 'no_title')))
                    . '<br>';
            }
        } else {
            $jscript_text .= sprintf(_('<span style="font-weight: bold;">Eingetragen am:</span> %s'),
                    strftime('%x, %X', $aterm->getMakeDate())) . '<br>';
            if ($aterm->getMakeDate() < $aterm->getChangeDate()) {
                $jscript_text .= sprintf(_('<span style="font-weight: bold;">Zuletzt bearbeitet am:</span> %s'),
                    strftime('%x, %X', $aterm->getChangeDate())) . '<br>';
            }
        }
    }
    $jscript_text .= '<br>';

    return " onmouseover=\"STUDIP.CalendarDialog.openCalendarHover('" . JSReady($aterm->toStringDate('SHORT_DAY')) . "', '" . JSReady($jscript_text, 'contact') . "', this);\" onmouseout=\"STUDIP.CalendarDialog.closeCalendarHover();\"";
}




/*
function js_hover($aterm)
{
    global $forum, $auth;

    if ($forum['jshover'] == 1 && $auth->auth['jscript']) { // Hovern
        $jscript_text = '<b>' . _("Zusammenfassung:") . ' </b>'
                . htmlReady($aterm->getTitle()) . '<hr>';

        if (strtolower(get_class($aterm)) == 'seminarevent' || strtolower(get_class($aterm)) == 'seminarcalendarevent') {
            $jscript_text .= '<b>' . _("Veranstaltung:") . ' </b> '
                    . htmlReady($aterm->getSemName()) . '<br>';
        }
        if ($aterm->getDescription()) {
            $jscript_text .= '<b>' . _("Beschreibung:") . ' </b> '
                    . htmlReady($aterm->getDescription()) . '<br>';
        }
        if ($categories = $aterm->toStringCategories()) {
            $jscript_text .= '<b>' . _("Kategorie:") . ' </b> '
                    . htmlReady($categories) . '<br>';
        }
        if ($aterm->getLocation()) {
            $jscript_text .= '<b>' . _("Ort:") . ' </b> '
                    . htmlReady($aterm->getLocation()) . '<br>';
        }
        if (strtolower(get_class($aterm)) != 'seminarevent') {
            if ($aterm->toStringPriority()) {
                $jscript_text .= '<b>' . _("Priorit&auml;t:") . ' </b>'
                        . htmlReady($aterm->toStringPriority()) . '<br>';
            }
            $jscript_text .= '<b>' . _("Zugriff:") . ' </b>'
                    . htmlReady($aterm->toStringAccessibility()) . '<br>';
            $jscript_text .= '<b>' . _("Wiederholung:") . ' </b>'
                    . htmlReady($aterm->toStringRecurrence()) . '<br>';
        }

        $jscript_text = "'" . JSReady($jscript_text, 'contact')
                . "',CAPTION,'"
                . JSReady($aterm->toStringDate('SHORT_DAY'))
                //  . "&nbsp; &nbsp; ". $jscript_title
                . "',NOCLOSE,CSSOFF";

        return " onmouseover=\"return overlib($jscript_text);\" onmouseout=\"return nd();\"";
    }

    return '';
}
*/





function info_icons(&$event)
{
    global $CANONICAL_RELATIVE_PATH_STUDIP;

    $out = '';
    if ($event->havePermission(Event::PERMISSION_READABLE) && (strtolower(get_class($event)) == 'seminarcalendarevent' || strtolower(get_class($event)) == 'seminarevent')) {
        $out .= "<img src=\"{$GLOBALS['ASSETS_URL']}images/projectevent-icon.gif\" ";
        $out .= "border=\"0\"" . tooltip(_("Veranstaltungstermin") . ' - ' . $event->getSemName()) . " valign>";
    }

    if ($event->getType() == 'PUBLIC') {
        $out .= '<img src="' . Assets::image_path('icons/16/blue/visibility-visible.png') . '" ';
        $out .= 'border="0"' . tooltip($event->toStringAccessibility()) . '>';
    } else if ($event->getType() == 'CONFIDENTIAL') {
        $out .= '<img src="' . Assets::image_path('icons/16/blue/visibility-invisible.png') . '" ';
        $out .= 'border="0"' . tooltip($event->toStringAccessibility()) . '>';
    }

    if ($event->getRepeat('rtype') != 'SINGLE') {
        $out .= '<img src="' . Assets::image_path('icons/16/blue/refresh.png') . '" ';
        $out .= 'border="0"' . tooltip($event->toStringRecurrence()) . '>';
    }

    if ($out != '') {
        $out = "<div align=\"right\">" . $out . "</div>";
    }

    return $out;
}

function quick_search_form($search_string, $cmd, $atime)
{
    global $PHP_SELF;

    $out = "\n<!-- CALENDAR QUICK SEARCH -->\n";
    $out .= "<form name=\"cal_event_search\" method=\"post\" action=\"$PHP_SELF?cmd=$cmd&atime=$atime\">\n";
    $out .= "<font font size=\"2\" color=\"#555555\">";
    $out .= _("Suche: ") . " </font>";
    $out .= "<input type=\"text\" name=\"cal_quick_search\" size=\"15\" maxlength=\"50\">";
    $out .= stripslashes($search_string) . "</input>\n";
    $out .= '<input type="image" src="' . Assets::image_path('icons/16/blue/accept.png') . ' border="0" style="vertical-align: bottom;"></form>';
    $out .= "<!-- END CALENDAR QUICK SEARCH -->\n";

    return $out;
}

function js_hover_group ($events, $start, $end, $user_id)
{
    global $forum, $auth;

    if (!$forum['jshover']) {
        return '';
    }

    if ($end) {
        $date_time = strftime('%x, ', $start) . strftime('%H:%M - ', $start)
            . strftime('%H:%M', $end);
    } else {
        $date_time = strftime('%x, ', $start);
    }
    if ($user_id == $GLOBALS['user']->id) {
        $js_title = sprintf(_("Termine am %s, Eigener Kalender"), $date_time);
    } else {
        $js_title = sprintf(_("Termine am %s, Gruppenmitglied: %s"), $date_time, get_fullname($user_id, 'no_title_short'));
    }

    if (!is_array($events)) {
        $events = array();
    }

    $js_text = '';
    foreach ($events as $event) {
        if (date('j', $event->getStart()) != date('j', $event->getEnd())) {
            $js_text .= '<b>' . $event->toStringDate('SHORT_DAY') . '</b> &nbsp; ';
        } else {
            $js_text .= '<b>' . $event->toStringDate('SHORT') . '</b> &nbsp; ';
        }
        $js_text .= htmlReady($event->getTitle()) . '<br>';
    }

    //$js_text = "'" . JSReady($js_text, 'contact') . "',CAPTION,'" . JSReady($js_title) . "',NOCLOSE,CSSOFF";

    return " onmouseover=\"STUDIP.CalendarDialog.openCalendarHover('" . JSReady($js_title) . "', '" . JSReady($js_text, 'contact') . "', this);\" onmouseout=\"STUDIP.CalendarDialog.closeCalendarHover();\"";
}
