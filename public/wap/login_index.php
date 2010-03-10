<?php
/**
* Menue of the members area.
*
* Parameters received via stdin<br/>
* <code>
*   $user_name
*   $user_pass
*   $session_id
* </code>
*
* @author       Florian Hansen <f1701h@gmx.net>
* @version      0.12    11.09.2003  16:37:30
* @access       public
* @modulegroup  wap_modules
* @module       login_index.php
* @package      WAP
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// login_index.php
// Menue of the members area
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
    include_once("wap_buttons.inc.php");

    if (!$session_id)
    {
        $session_id = wap_adm_check_user($user_name, $user_pass);
        if ($session_id)
            $wrong_user_data = FALSE;
        else
            $wrong_user_data = TRUE;
    }
    $session_user_id = wap_adm_start_card($session_id);

    if ($session_user_id)
    {
        echo "<p align=\"center\">";
        echo "<b>" . wap_txt_encode_to_wml(_("Menü")) . "</b>";
        echo "</p>";

        echo "<p align=\"center\">\n";
        echo "<anchor>" . wap_txt_encode_to_wml(_("Verzeichnis")) . "\n";
        echo "    <go method=\"post\" href=\"directory.php\">\n";
        echo "        <postfield name=\"session_id\" value=\"$session_id\"/>\n";
        echo "    </go>\n";
        echo "</anchor><br/>\n";
        
        if ($GLOBALS['CALENDAR_ENABLE']) {
            echo "<anchor>" . wap_txt_encode_to_wml(_("Termine")) . "\n";
            echo "    <go method=\"post\" href=\"dates.php\">\n";
            echo "        <postfield name=\"session_id\" value=\"$session_id\"/>\n";
            echo "    </go>\n";
            echo "</anchor><br/>\n";
        }

        echo "<anchor>" . wap_txt_encode_to_wml(_("News")) . "\n";
        echo "    <go method=\"post\" href=\"news.php\">\n";
        echo "        <postfield name=\"session_id\" value=\"$session_id\"/>\n";
        echo "    </go>\n";
        echo "</anchor><br/>\n";

        echo "<anchor>" . wap_txt_encode_to_wml(_("Veranstaltungen")) . "\n";
        echo "    <go method=\"post\" href=\"events.php\">\n";
        echo "        <postfield name=\"session_id\" value=\"$session_id\"/>\n";
        echo "    </go>\n";
        echo "</anchor><br/>\n";

        echo "<anchor>" . wap_txt_encode_to_wml(_("Einrichtungen")) . "\n";
        echo "    <go method=\"post\" href=\"institutes.php\">\n";
        echo "        <postfield name=\"session_id\" value=\"$session_id\"/>\n";
        echo "    </go>\n";
        echo "</anchor><br/>\n";

        echo "<anchor>" . wap_txt_encode_to_wml(_("Kurznachrichten")) . "\n";
        echo "    <go method=\"post\" href=\"sms.php\">\n";
        echo "        <postfield name=\"session_id\" value=\"$session_id\"/>\n";
        echo "    </go>\n";
        echo "</anchor><br/>\n";
        echo "</p>\n";

        echo "<p align=\"right\">\n";
        echo "<anchor>" . wap_buttons_logout() . "\n";
        echo "    <go method=\"post\" href=\"index.php\">\n";
        echo "        <postfield name=\"session_id\" value=\"$session_id\"/>\n";
        echo "    </go>\n";
        echo "</anchor><br/>\n";
        echo "</p>\n";
    }
    elseif ($wrong_user_data)
    {
        echo "<p>";
        echo _("Username oder Passwort nicht korrekt.");
        echo "</p>";

        echo "<p align=\"right\">\n";
        echo "<anchor>" . wap_buttons_login() . "\n";
        echo "    <go method=\"post\" href=\"login_form.php\">\n";
        echo "        <postfield name=\"user_name\" value=\"$user_name\"/>\n";
        echo "    </go>\n";
        echo "</anchor><br/>\n";

        wap_buttons_menu_link(FALSE);
        echo "</p>\n";
    }
    wap_adm_end_card();
?>
