<?php
/**
* Form for user login.
*
* Parameters received via stdin<br/>
* <code>
*   $user_name
* </code>
*
* @author       Florian Hansen <f1701h@gmx.net>
* @version      0.11    10.09.2003  21:23:59
* @access       public
* @modulegroup  wap_modules
* @module       login_form.php
* @package      WAP
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// login_form.php
// Form for user login
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

    include_once("wap_buttons.inc.php");
    include_once("wap_txt.inc.php");
    include_once("wap_adm.inc.php");

    wap_adm_start_card();

        echo "<p align=\"center\">";
        echo "<b>" . _("Login") . "</b>";
        echo "</p>\n";

        echo "<p align=\"left\">\n";

        echo wap_txt_encode_to_wml(_("Username:")) . "<br/>";
        echo "<input type=\"text\" name=\"user_name\" ";
        echo "emptyok=\"false\" value=\"" . stripslashes($user_name);
        echo "\"/><br/>\n";

        echo wap_txt_encode_to_wml(_("Passwort:")) . "<br/>";
        echo "<input type=\"password\" name=\"user_pass\" ";
        echo "emptyok=\"false\"/><br/>\n";
        echo "</p>\n";

        echo "<p align=\"right\">\n";
        echo "<anchor>" . wap_buttons_login() . "\n";
        echo "    <go method=\"post\" href=\"login_index.php\">\n";
        echo "        <postfield name=\"user_name\" value=\"\$(user_name)\"/>\n";
        echo "        <postfield name=\"user_pass\" value=\"\$(user_pass)\"/>\n";
        echo "    </go>\n";
        echo "</anchor><br/>\n";

        wap_buttons_menu_link(FALSE);
        echo "</p>\n";

    wap_adm_end_card();
?>
