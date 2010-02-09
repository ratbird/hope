<?php
/**
* Form for selection of period of time
*
* Lets the user enter the number of the next few days that defines which
* dates should be displayed.<br/>
* Parameters received via stdin<br/>
* <code>
*	$session_id
* </code>
*
* @author		Florian Hansen <f1701h@gmx.net>
* @version		0.11	10.09.2003	21:21:47
* @access		public
* @modulegroup	wap_modules
* @module		dates.php
* @package		WAP
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// dates.php
// Period of time selection form for dates
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
	* Standard value for the number of days
	* @const NUM_DAYS_PRESET
	*/
	define ("NUM_DAYS_PRESET", 14);

	include_once("wap_adm.inc.php");
	include_once("wap_txt.inc.php");
	include_once("wap_buttons.inc.php");

	$session_user_id = wap_adm_start_card($session_id);
	if ($session_user_id)
	{
		echo "<p align=\"center\">";
		$t = _("Termine");
		echo "<b>" . wap_txt_encode_to_wml($t) . "</b>";
		echo "</p>\n";

		echo "<p>\n";
		$t = _("Für die nächsten Tage:");
		echo wap_txt_encode_to_wml($t) . "&#32;\n";
		echo "<input type=\"text\" name=\"num_days\" emptyok=\"false\" ";
		echo "format=\"*N\" maxlength=\"3\" value=\"" . NUM_DAYS_PRESET . "\"/>\n";
		echo "</p>\n";

		echo "<p align=\"right\">\n";
		echo "<anchor>" . wap_buttons_show() . "\n";
		echo "    <go method=\"post\" href=\"dates_search.php\">\n";
		echo "        <postfield name=\"session_id\" value=\"$session_id\"/>\n";
		echo "        <postfield name=\"num_days\" value=\"\$(num_days)\"/>\n";
		echo "    </go>\n";
		echo "</anchor><br/>\n";

		wap_buttons_menu_link($session_id);
		echo "</p>\n";
	}
	wap_adm_end_card();
?>
