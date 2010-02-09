<?php
/**
* Displays the details of a requested event
*
* Parameters received via stdin<br/>
* <code>
*	$session_id
*	$event_id
*	$events_pc	(page counter)
* </code>
*
* @author		Florian Hansen <f1701h@gmx.net>
* @version		0.11	12.09.2003	00:10:31
* @access		public
* @modulegroup	wap_modules
* @module		show_event.php
* @package		WAP
*/
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// show_event.php
// Event details
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
    * workaround for PHPDoc
    *
    * Use this if module contains no elements to document!
    * @const PHPDOC_DUMMY
    */
    define("PHPDOC_DUMMY", TRUE);

	include_once("wap_adm.inc.php");
	include_once("wap_txt.inc.php");
	include_once("wap_hlp.inc.php");
	include_once("wap_buttons.inc.php");
	include_once('lib/dates.inc.php');

	$session_user_id = wap_adm_start_card($session_id);
    if ($session_user_id)
    {
        $db = new DB_Seminar();
        $current_time = time();

        wap_hlp_get_global_user_var($session_user_id, "CurrentLogin");

        $q_string  = "SELECT COUNT(news_range.news_id) AS num_news ";
        $q_string .= "FROM news_range LEFT JOIN news USING (news_id) ";
        $q_string .= "WHERE news_range.range_id='" . $event_id . "' ";
        $q_string .= "AND date < $current_time AND (date + expire) > $current_time ";
        $q_string .= "AND date > $CurrentLogin";

        $db-> query($q_string);
        $db-> next_record();
        $num_news = $db-> f("num_news");

        $q_string  = "SELECT COUNT(termin_id) AS num_dates FROM termine ";
        $q_string .= "WHERE range_id = '" . $event_id . "' ";
        $q_string .= "AND chdate > $CurrentLogin";

        $db-> query($q_string);
        $db-> next_record();
        $num_dates = $db-> f("num_dates");

        $q_string  = "SELECT Name FROM seminare ";
        $q_string .= "WHERE Seminar_id = '" . $event_id . "'";

        $db-> query($q_string);
        $db-> next_record();
        $event_name = $db-> f("Name");
        $short_name = wap_txt_shorten_text($event_name, WAP_TXT_LINE_LENGTH);

        echo "<p align=\"center\">\n";
        echo "<b>" . wap_txt_encode_to_wml($short_name) . "</b><br/>\n";
        echo view_turnus($event_id, TRUE) . "<br/>\n";
        echo "</p>\n";

        echo "<p align=\"center\">\n";
        if ($num_news == 0 && $num_dates == 0)
        {
            echo "? ";
            $t = _("Keine neuen News oder Termine seit letztem Web-Besuch.");
            echo wap_txt_encode_to_wml($t) . " &#191;\n";
        }
        else
        {
            if ($num_news > 0)
            {
                echo "<anchor>" . wap_txt_encode_to_wml(_("News")) . "\n";
                echo "    <go method=\"post\" href=\"event_news.php\">\n";
                echo "        <postfield name=\"session_id\" value=\"$session_id\"/>\n";
                echo "        <postfield name=\"event_id\" value=\"$event_id\"/>\n";
                echo "        <postfield name=\"events_pc\" value=\"$events_pc\"/>\n";
                echo "    </go>\n";
                echo "</anchor><br/>\n";
            }
            if ($num_dates > 0)
            {
                echo "<anchor>" . wap_txt_encode_to_wml(_("Termine")) . "\n";
                echo "    <go method=\"post\" href=\"event_dates.php\">\n";
                echo "        <postfield name=\"session_id\" value=\"$session_id\"/>\n";
                echo "        <postfield name=\"event_id\" value=\"$event_id\"/>\n";
                echo "        <postfield name=\"events_pc\" value=\"$events_pc\"/>\n";
                echo "    </go>\n";
                echo "</anchor><br/>\n";
            }
        }
        echo "</p>\n";

        echo "<p align=\"right\">\n";
        echo "<anchor>" . wap_buttons_back() . "\n";
        echo "    <go method=\"post\" href=\"events.php\">\n";
        echo "        <postfield name=\"session_id\" value=\"$session_id\"/>\n";
        echo "        <postfield name=\"events_pc\" value=\"$events_pc\"/>\n";
        echo "    </go>\n";
        echo "</anchor><br/>\n";

        wap_buttons_menu_link($session_id);
        echo "</p>\n";
    }
	wap_adm_end_card();
?>
