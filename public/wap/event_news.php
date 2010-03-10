<?php
/**
* Outputs a list of new event news.
*
* New news of the selected event are displayed.<br/>
* Parameters received via stdin<br/>
* <code>
*   $session_id
*   $event_id
*   $events_pc      (page counter)
*   $event_news_pc  (page counter)
* </code>
*
* @author       Florian Hansen <f1701h@gmx.net>
* @version      0.12    10.09.2003  21:23:09
* @access       public
* @modulegroup  wap_modules
* @module       event_news.php
* @package      WAP
*/
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// event_news.php
// List of new event news
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
    * Maximum of news displayed per page
    * @const NEWS_PER_PAGE
    */
    define ("NEWS_PER_PAGE", 5);

    include_once("wap_adm.inc.php");
    include_once("wap_txt.inc.php");
    include_once("wap_hlp.inc.php");
    include_once("wap_buttons.inc.php");

    $session_user_id = wap_adm_start_card($session_id);
    if ($session_user_id)
    {
        echo "<p align=\"center\">";
        echo "<b>" . _("News") . "</b>";
        echo "</p>\n";

        if ($event_news_pc)
        {
            $page_counter     = $event_news_pc;
            $progress_counter = $page_counter * NEWS_PER_PAGE;
        }
        else
        {
            $page_counter     = 0;
            $progress_counter = 0;
        }

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
        $num_news  = $db-> f("num_news");
        $num_pages = ceil($num_news / NEWS_PER_PAGE);

        if ($num_news > 0)
        {
            $q_string  = "SELECT news.topic, news.news_id ";
            $q_string .= "FROM news_range LEFT JOIN news USING (news_id) ";
            $q_string .= "WHERE news_range.range_id='" . $event_id . "' ";
            $q_string .= "AND date < $current_time AND (date + expire) > $current_time ";
            $q_string .= "AND date > $CurrentLogin ";
            $q_string .= "ORDER BY date DESC ";
            $q_string .= "LIMIT $progress_counter, " . NEWS_PER_PAGE;

            $db-> query($q_string);
            $num_entries = $db-> nf();
            $progress_limit = $progress_counter + $num_entries;

            while ($db-> next_record() && $progress_counter < $progress_limit)
            {
                $progress_counter ++;
                $entry_topic = $db-> f("topic");
                $entry_id    = $db-> f("news_id");

                $short_topic = wap_txt_shorten_text($entry_topic, WAP_TXT_LINK_LENGTH);
                echo "<p align=\"left\">\n";
                echo "<anchor>" . wap_txt_encode_to_wml($short_topic) . "\n";
                echo "    <go method=\"post\" href=\"show_news.php\">\n";
                echo "        <postfield name=\"session_id\" value=\"$session_id\"/>\n";
                echo "        <postfield name=\"news_id\" value=\"$entry_id\"/>\n";
                echo "        <postfield name=\"event_id\" value=\"$event_id\"/>\n";
                echo "        <postfield name=\"events_pc\" value=\"$events_pc\"/>\n";
                echo "        <postfield name=\"event_news_pc\" value=\"$page_counter\"/>\n";
                echo "    </go>\n";
                echo "</anchor>\n";
                echo "</p>\n";

                if ($progress_counter == $progress_limit)
                {
                    echo "<p align=\"right\">\n";
                    if ($progress_counter < $num_news)
                    {
                        $page_counter_v = $page_counter + 1;
                        echo "<anchor>" . wap_buttons_forward_page($page_counter_v, $num_pages) . "\n";
                        echo "    <go method=\"post\" href=\"event_news.php\">\n";
                        echo "        <postfield name=\"session_id\" value=\"$session_id\"/>\n";
                        echo "        <postfield name=\"event_id\" value=\"$event_id\"/>\n";
                        echo "        <postfield name=\"events_pc\" value=\"$events_pc\"/>\n";
                        echo "        <postfield name=\"event_news_pc\" value=\"$page_counter_v\"/>\n";
                        echo "    </go>\n";
                        echo "</anchor><br/>\n";
                    }
                    if ($page_counter > 0)
                    {
                        $page_counter_v = $page_counter - 1;
                        echo "<anchor>" . wap_buttons_back_page($page_counter_v, $num_pages) . "\n";
                        echo "    <go method=\"post\" href=\"event_news.php\">\n";
                        echo "        <postfield name=\"session_id\" value=\"$session_id\"/>\n";
                        echo "        <postfield name=\"event_id\" value=\"$event_id\"/>\n";
                        echo "        <postfield name=\"events_pc\" value=\"$events_pc\"/>\n";
                        echo "        <postfield name=\"event_news_pc\" value=\"$page_counter_v\"/>\n";
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
            $t = _("Keine News vorhanden.");
            echo "? " . wap_txt_encode_to_wml($t) . " &#191;";
            echo "</p>\n";
        }

        echo "<p align=\"right\">\n";
        echo "<anchor>" . wap_buttons_back() . "\n";
        echo "    <go method=\"post\" href=\"show_event.php\">\n";
        echo "        <postfield name=\"session_id\" value=\"$session_id\"/>\n";
        echo "        <postfield name=\"event_id\" value=\"$event_id\"/>\n";
        echo "        <postfield name=\"events_pc\" value=\"$events_pc\"/>\n";
        echo "    </go>\n";
        echo "</anchor><br/>\n";

        wap_buttons_menu_link($session_id);
        echo "</p>\n";
    }
    wap_adm_end_card();
?>
