<?php
/**
* Start page
*
* Lets the user decide either to search the free accessible directory
* or login in order to access the members area.<br/>
* Parameters received via stdin<br/>
* <code>
*   $session_id
* </code>
*
* @author       Florian Hansen <f1701h@gmx.net>
* @version      0.11    10.09.2003  21:23:48
* @access       public
* @modulegroup  wap_modules
* @module       index.php
* @package      WAP
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// index.php
// Start page
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

    include_once("wap_adm.inc.php");
    include_once("wap_txt.inc.php");

    if ($session_id)
    {
        wap_adm_remove_session ($session_id);
    }

    wap_adm_start_card();

            echo "<p align=\"center\">";
            echo "<b>Stud.IP WAP</b>";
            echo "</p>\n";

            echo "<p align=\"center\">\n";
            echo "<anchor>" . wap_txt_encode_to_wml(_("Verzeichnis")) . "\n";
            echo "    <go href=\"directory.php\"/>\n";
            echo "</anchor><br/>\n";


            echo "<anchor>" . wap_txt_encode_to_wml(_("Login")) . "\n";
            echo "    <go href=\"login_form.php\"/>\n";
            echo "</anchor>\n";
            echo "</p>\n";

    wap_adm_end_card();
?>
