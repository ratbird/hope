<?php
/**
* Displays the details of a requested dates
*
* Parameters received via stdin<br/>
* <code>
*   $session_id
*   $date_id
*   $event_id
*   $num_days
*   $event_sem_name
*   $events_pc          (page counter)
*   $event_dates_pc     (page counter)
*   $dates_search_pc    (page counter)
* </code>
*
* @author       Florian Hansen <f1701h@gmx.net>
* @version      0.12    10.09.2003  21:24:37
* @access       public
* @modulegroup  wap_modules
* @module       show_date.php
* @package      WAP
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// show_date.php
// Date datails
// Copyright (c) 2003 Florian Hansen <f1701h@gmx.net>
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+

/**
* Maximum of characters used for event description
* @const MAX_DESCR_LENGTH
*/
define("MAX_DESCR_LENGTH", 250);

include_once("Dummy.class.php");
include_once("wap_adm.inc.php");
include_once("wap_txt.inc.php");
include_once("wap_buttons.inc.php");
require_once 'lib/calendar_functions.inc.php';
require_once($GLOBALS["RELATIVE_PATH_CALENDAR"]
        . "/lib/DbCalendarEvent.class.php");
require_once($GLOBALS["RELATIVE_PATH_CALENDAR"]
        . "/lib/SeminarEvent.class.php");

$session_user_id = wap_adm_start_card($session_id);
if ($session_user_id)
{
    $user     = new Dummy();
    $user->id = $session_user_id;

    if ($sem_event)
        $event = new SeminarEvent($date_id);
    else
        $event = new DbCalendarEvent($date_id);

    $event_start = $event->getStart();
    $event_end   = $event->getEnd();
    $event_title = $event->getTitle();
    $event_descr = $event->getDescription();

    $week_day_start = wday($event_start, "SHORT");
    $week_day_end   = wday($event_end, "SHORT");
    $date_start     = date("d.m.", $event_start);
    $date_end       = date("d.m.", $event_end);
    $time_start     = date("H:i", $event_start);
    $time_end       = date("H:i", $event_end);

    echo "<p align=\"center\">\n";

    if ($sem_event)
    {
        $short_event_sem_name = wap_txt_shorten_text($event->getSemName(), WAP_TXT_LINE_LENGTH);
        echo "<b>";
        echo wap_txt_encode_to_wml($short_event_sem_name);
        echo "</b><br/>\n";
    }

    echo "<b>" . wap_txt_encode_to_wml($event_title) . "</b><br/>\n";
    echo "$week_day_start, $date_start, $time_start<br/>\n";
    echo "</p>\n";

    echo "<p align=\"left\">\n";
    $short_event_descr = wap_txt_shorten_text($event_descr, MAX_DESCR_LENGTH, "cut_end");
    echo wap_txt_encode_to_wml($short_event_descr) . "\n";
    echo "</p>\n";

    echo "<p align=\"center\">";
    echo wap_txt_encode_to_wml(_("bis"));
    if ($date_start != $date_end)
        echo " $week_day_end, $date_end,";
    echo " $time_end";
    echo "</p>\n";

    echo "<p align=\"right\">\n";
    echo "<anchor>" . wap_buttons_back() . "\n";
    if ($event_id)
    {
        echo "   <go method=\"post\" href=\"event_dates.php\">\n";
        echo "        <postfield name=\"session_id\" value=\"$session_id\"/>\n";
        echo "        <postfield name=\"event_id\" value=\"$event_id\"/>\n";
        echo "        <postfield name=\"events_pc\" value=\"$events_pc\"/>\n";
        echo "        <postfield name=\"event_dates_pc\" value=\"$event_dates_pc\"/>\n";
        echo "   </go>\n";
    }
    else
    {
        echo "   <go method=\"post\" href=\"dates_search.php\">\n";
        echo "        <postfield name=\"session_id\" value=\"$session_id\"/>\n";
        echo "        <postfield name=\"num_days\" value=\"$num_days\"/>\n";
        echo "        <postfield name=\"dates_search_pc\" value=\"$dates_search_pc\"/>\n";
        echo "   </go>\n";
    }
    echo "</anchor><br/>\n";

    wap_buttons_menu_link($session_id);
    echo "</p>\n";
}
wap_adm_end_card();
?>
