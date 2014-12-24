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
        $out .= htmlReady(strftime("%B", $ts_month));
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
                    $out .= Assets::img("icons/16/blue/date.png", array('alt' => $event_count_txt, 'title' => $event_count_txt));
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
        $out .= htmlReady(strftime("%B", $ts_month));
        $out .= "</b></font></a></td>\n";
    }
    $out .= "</tr></table>\n</td></tr></table>\n";

    return $out;
}

function javascript_hover_year(&$calendar, $day_time)
{
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

                $event_count_txt[] = sprintf($txt, get_fullname($user_calendar->getUserId(), 'no_title_rev'), $event_count);
            }
        }
        if (sizeof($event_count_txt)) {
            $out .= implode('; ', $event_count_txt);
        }
    } else {
        $event_count = $calendar->view->numberOfEvents($day_time);
        if ($event_count > 1) {
            $out = sprintf(_("%s Termine"), $event_count);
        } elseif ($event_count == 1) {
            $out = _("1 Termin");
        }
    }

    return $out;
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
                . htmlReady(mila($aterm->getDescription(), 300)) . '<br>';
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
            $jscript_text .= '<b>' . _("Priorität:") . ' </b>'
                    . htmlReady($aterm->toStringPriority()) . '<br>';
        }
        $jscript_text .= '<b>' . _("Zugriff:") . ' </b>'
                . htmlReady($aterm->toStringAccessibility()) . '<br>';
        $jscript_text .= '<b>' . _("Wiederholung:") . ' </b>'
                . htmlReady($aterm->toStringRecurrence()) . '<br>';
        
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
    } else {
        // related groups
        $related_groups = $aterm->getRelatedGroups();
        if (sizeof($related_groups)) {
            $jscript_text .= '<b>' . _("Betroffene Gruppen:") . ' </b>'
                    . htmlReady(implode(', ', array_map(
                            function ($group) { return $group->name; },
                            $related_groups))) . '<br>';
        }
    }
    
    $jscript_text .= '<br>';

    return " onmouseover=\"STUDIP.CalendarDialog.openCalendarHover('" . JSReady($aterm->toStringDate('SHORT_DAY'), 'inline-single') . "', '" . JSReady($jscript_text, 'inline-single') . "', this);\" onmouseout=\"STUDIP.CalendarDialog.closeCalendarHover();\"";
}


function info_icons(&$event)
{

    $out = '';
    if ($event->havePermission(Event::PERMISSION_READABLE) && (strtolower(get_class($event)) == 'seminarcalendarevent' || strtolower(get_class($event)) == 'seminarevent')) {
        $out .= Assets::img('images/projectevent-icon.gif', tooltip2(_('Veranstaltungstermin') . ' - ' . $event->getSemName()));
    }

    if ($event->getType() == 'PUBLIC') {
        $out .= Assets::img("icons/16/blue/visibility-visible.png", array('alt' => $event->toStringAccessibility(), 'title' => $event->toStringAccessibility(), 'border' => "0"));
    } else if ($event->getType() == 'CONFIDENTIAL') {
        $out .= Assets::img("icons/16/blue/visibility-invisible.png", array('alt' => $event->toStringAccessibility(), 'title' => $event->toStringAccessibility(), 'border' => "0"));
    }

    if ($event->getRepeat('rtype') != 'SINGLE') {
        $out .= Assets::img("icons/16/blue/refresh.png", array('alt' => $event->toStringRecurrence(), 'title' => $event->toStringRecurrence(), 'border' => "0"));
    }

    if ($out != '') {
        $out = "<div align=\"right\">" . $out . "</div>";
    }

    return $out;
}
