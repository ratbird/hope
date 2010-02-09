<?php
/**
* Outputs a list of events subscribed by the user
*
* Parameters received via stdin<br/>
* <code>
*	$session_id
*	$events_pc	(page counter)
* </code>
*
* @author		Florian Hansen <f1701h@gmx.net>
* @version		0.12	10.09.2003	21:23:36
* @access		public
* @modulegroup	wap_modules
* @module		events.php
* @package		WAP
*/
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// events.php
// List of events subscribed by the user
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
	* Maximum of events displayed per page
	* @const EVENTS_PER_PAGE
	*/
	define ("EVENTS_PER_PAGE", 5);

	include_once("wap_adm.inc.php");
	include_once("wap_txt.inc.php");
	include_once("wap_hlp.inc.php");
	include_once("wap_buttons.inc.php");

	$session_user_id = wap_adm_start_card($session_id);
    if ($session_user_id)
    {
        echo "<p align=\"center\">";
        echo "<b>" . _("Veranstaltungen") . "</b>";
        echo "</p>\n";

        if ($events_pc)
        {
            $page_counter     = $events_pc;
            $progress_counter = $page_counter * EVENTS_PER_PAGE;
        }
        else
        {
            $page_counter     = 0;
            $progress_counter = 0;
        }

        $db       = new DB_Seminar();
        $db_entry = new DB_Seminar();
        $current_time = time();
        wap_hlp_get_global_user_var($session_user_id, "CurrentLogin");

        $q_string  = "SELECT COUNT(Seminar_id) AS num_events ";
        $q_string .= "FROM seminar_user ";
        $q_string .= "WHERE user_id = '" . $session_user_id . "'";

        $db-> query($q_string);
        $db-> next_record();
        $num_events = $db-> f("num_events");
        $num_pages  = ceil($num_events / EVENTS_PER_PAGE);

        if ($num_events > 0)
        {
            $q_string  = "SELECT seminare.Seminar_id, seminare.Name ";
            $q_string .= "FROM seminar_user LEFT JOIN seminare USING (Seminar_id) ";
            $q_string .= "WHERE seminar_user.user_id = '" . $session_user_id . "' ";
			$q_string .= "ORDER BY seminar_user.gruppe, seminare.Name";
            $db-> query($q_string);

            $event_new_array = array();
            $event_old_array = array();
            $new_sign        = "*";
            while ($db-> next_record())
            {
				$entry_array = array();
                $entry_name  = $db-> f("Name");
                $entry_id    = $db-> f("Seminar_id");

				$q_string  = "SELECT COUNT(news_range.news_id) AS num_news ";
    		    $q_string .= "FROM news_range LEFT JOIN news USING (news_id) ";
        		$q_string .= "WHERE news_range.range_id='" . $entry_id . "' ";
	        	$q_string .= "AND date < $current_time AND (date + expire) > $current_time ";
    	    	$q_string .= "AND date > $CurrentLogin";
    	    	$db_entry-> query($q_string);
    	    	$db_entry-> next_record();
    	    	$num_news = $db_entry-> f("num_news");

		        $q_string  = "SELECT COUNT(termin_id) AS num_dates FROM termine ";
		        $q_string .= "WHERE range_id = '" . $entry_id . "' ";
		        $q_string .= "AND chdate > $CurrentLogin";
    	    	$db_entry-> query($q_string);
    	    	$db_entry-> next_record();
    	    	$num_dates = $db_entry-> f("num_dates");

    	    	if ($num_news || $num_dates)
    	    	{
    	    		$entry_array[$entry_id] = $new_sign . $entry_name;
    	    		array_push($event_new_array, $entry_array);
    	    	}
    	    	else
    	    	{
                	$entry_array[$entry_id] = $entry_name;
    	    		array_push($event_old_array, $entry_array);
    	    	}
    	    }

    	    $event_array    = array_merge((array)$event_new_array, (array)$event_old_array);
    	    $progress_limit = $progress_counter + EVENTS_PER_PAGE;
    	    if ($progress_limit > $num_events)
    	    	$progress_limit = $num_events;

            while ($progress_counter < $progress_limit)
            {
                $entry_id   = key($event_array[$progress_counter]);
                $entry_name = $event_array[$progress_counter][$entry_id];
                $short_name = wap_txt_shorten_text($entry_name, WAP_TXT_LINK_LENGTH);
                echo "<p align=\"left\">\n";
                echo "<anchor>" . wap_txt_encode_to_wml($short_name) . "\n";
                echo "    <go method=\"post\" href=\"show_event.php\">\n";
                echo "        <postfield name=\"session_id\" value=\"$session_id\"/>\n";
                echo "        <postfield name=\"event_id\" value=\"$entry_id\"/>\n";
                echo "        <postfield name=\"events_pc\" value=\"$page_counter\"/>\n";
                echo "    </go>\n";
                echo "</anchor>\n";
                echo "</p>\n";
                $progress_counter ++;

                if ($progress_counter == $progress_limit)
                {
                    echo "<p align=\"right\">\n";
                    if ($progress_counter < $num_events)
                    {
                        $page_counter_v = $page_counter + 1;
                        echo "<anchor>" . wap_buttons_forward_page($page_counter_v, $num_pages) . "\n";
                        echo "    <go method=\"post\" href=\"events.php\">\n";
                        echo "        <postfield name=\"session_id\" value=\"$session_id\"/>\n";
                        echo "        <postfield name=\"events_pc\" value=\"$page_counter_v\"/>\n";
                        echo "    </go>\n";
                        echo "</anchor><br/>\n";
                    }
                    if ($page_counter > 0)
                    {
                        $page_counter_v = $page_counter - 1;
                        echo "<anchor>" . wap_buttons_back_page($page_counter_v, $num_pages) . "\n";
                        echo "    <go method=\"post\" href=\"events.php\">\n";
                        echo "        <postfield name=\"session_id\" value=\"$session_id\"/>\n";
                        echo "        <postfield name=\"events_pc\" value=\"$page_counter_v\"/>\n";
                        echo "    </go>\n";
                        echo "</anchor><br/>\n";
                    }
                    echo "</p>\n";
                }
            }
        }
        else
        {
            echo "<p align=\"left\">";
            $t = _("Keine Veranstaltungen abonniert.");
            echo "? " . wap_txt_encode_to_wml($t) . " &#191;";
            echo "</p>\n";
        }

        echo "<p align=\"right\">\n";
        wap_buttons_menu_link($session_id);
        echo "</p>\n";
    }
	wap_adm_end_card();
?>
