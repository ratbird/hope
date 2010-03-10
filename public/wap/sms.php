<?php
/**
* Outputs a list of new short messages.
*
* Only new messages since last login to the web-interface are displayed.
*
* Parameters received via stdin<br/>
* <code>
*   $session_id
*   $sms_pc     (page counter)
* </code>
*
* @author       Florian Hansen <f1701h@gmx.net>
* @version      0.12    18.09.2003  11:23:21
* @access       public
* @modulegroup  wap_modules
* @module       dates_search.php
* @package      WAP
*/
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// sms.php
// List of new short messages
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
* Maximum of short messages displayed per page
* @const SMS_PER_PAGE
*/
define ("SMS_PER_PAGE", 5);

include_once("wap_adm.inc.php");
include_once("wap_txt.inc.php");
include_once("wap_hlp.inc.php");
include_once("wap_buttons.inc.php");

$session_user_id = wap_adm_start_card($session_id);
if ($session_user_id)
{
    echo "<p align=\"center\">";
    echo "<b>" . wap_txt_encode_to_wml(_("Kurznachrichten")) . "</b>";
    echo "</p>\n";

    if ($sms_pc)
    {
        $page_counter    = $sms_pc;
        $progress_counter = $page_counter * SMS_PER_PAGE;
    }
    else
    {
        $page_counter    = 0;
        $progress_counter = 0;
    }

    wap_hlp_get_global_user_var($session_user_id, "CurrentLogin");

    $db = new DB_Seminar();
    $q_string  = "SELECT COUNT(message_user.message_id) AS num_sms ";
    $q_string .= "FROM message_user LEFT JOIN message USING (message_id) ";
    $q_string .= "WHERE message_user.user_id = '" . $session_user_id . "' ";
    $q_string .= "AND message_user.snd_rec = 'rec' ";
    $q_string .= "AND message_user.deleted = '0' ";
    $q_string .= "AND message.mkdate > $CurrentLogin";

    $db->query($q_string);
    $db->next_record();
    $num_sms   = $db->f("num_sms");
    $num_pages = ceil($num_sms / SMS_PER_PAGE);

    if ($num_sms > 0)
    {
        $q_string  = "SELECT message_user.message_id, auth_user_md5.* ";
        $q_string .= "FROM message_user LEFT JOIN message USING (message_id) ";
        $q_string .= "LEFT JOIN auth_user_md5 ON (message.autor_id = auth_user_md5.user_id) ";
        $q_string .= "WHERE message_user.user_id = '" . $session_user_id . "' ";
        $q_string .= "AND message_user.snd_rec = 'rec' ";
        $q_string .= "AND message_user.deleted = '0' ";
        $q_string .= "AND message.mkdate > $CurrentLogin ";
        $q_string .= "LIMIT $progress_counter, " . SMS_PER_PAGE;

        $db->query($q_string);
        $num_entries = $db->nf();
        $progress_limit = $progress_counter + $num_entries;

        if (!isset($sms_pc))
        {
            echo "<p align=\"center\">";
            if ($num_sms > 1)
                $t = sprintf(_("%s neue Nachrichten."), $num_sms);
            else
                $t = sprintf(_("%s neue Nachricht."), $num_sms);
            echo wap_txt_encode_to_wml($t);
            echo "</p>\n";
        }

        while ($db-> next_record() && $progress_counter < $progress_limit)
        {
            $progress_counter ++;
            $entry_sender = $db->f("Nachname");
            $entry_id    = $db->f("message_id");

            $short_sender = wap_txt_shorten_text($entry_sender, WAP_TXT_LINK_LENGTH - 3);
            echo "<p align=\"left\">\n";
            echo "<anchor>" . sprintf ("%02d ", $progress_counter);
            echo wap_txt_encode_to_wml($short_sender) . "\n";
            echo "  <go method=\"post\" href=\"show_sms.php\">\n";
            echo "      <postfield name=\"session_id\" value=\"$session_id\"/>\n";
            echo "      <postfield name=\"sms_id\" value=\"$entry_id\"/>\n";
            echo "      <postfield name=\"sms_pc\" value=\"$page_counter\"/>\n";
            echo "  </go>\n";
            echo "</anchor>\n";
            echo "</p>\n";

            if ($progress_counter == $progress_limit)
            {
                echo "<p align=\"right\">\n";
                if ($progress_counter < $num_sms)
                {
                    $page_counter_v = $page_counter + 1;
                    echo "<anchor>" . wap_buttons_forward_page($page_counter_v, $num_pages) . "\n";
                    echo "  <go method=\"post\" href=\"sms.php\">\n";
                    echo "      <postfield name=\"session_id\" value=\"$session_id\"/>\n";
                    echo "      <postfield name=\"sms_pc\" value=\"$page_counter_v\"/>\n";
                    echo "  </go\n>";
                    echo "</anchor><br/>\n";
                }
                if ($page_counter > 0)
                {
                    $page_counter_v = $page_counter - 1;
                    echo "<anchor>" . wap_buttons_back_page($page_counter_v, $num_pages) . "\n";
                    echo "  <go method=\"post\" href=\"sms.php\">\n";
                    echo "      <postfield name=\"session_id\" value=\"$session_id\"/>\n";
                    echo "      <postfield name=\"sms_pc\" value=\"$page_counter_v\"/>\n";
                    echo "  </go>\n";
                    echo "</anchor><br/>\n";
                }
                echo "</p>\n";
            }
        }
    }
    else
    {
        echo "<p align=\"left\">";
        echo "? ";
        $t = _("Keine neuen Kurznachrichten seit letztem Web-Besuch.");
        echo wap_txt_encode_to_wml($t) . " &#191;";
        echo "</p>\n";
    }

    echo "<p align=\"right\">\n";
    wap_buttons_menu_link($session_id);
    echo "</p>\n";
}
wap_adm_end_card();
?>
