<?php
/**
* Displays information about a selected person.
*
* Parameters received via stdin<br/>
* <code>
*	$session_id
*	$first_name
*	$last_name
*	$user_id
*	$directory_search_pc    (page counter)
* </code>
*
* @author		Florian Hansen <f1701h@gmx.net>
* @version		0.11	10.09.2003	21:25:25
* @access		public
* @modulegroup	wap_modules
* @module		show_private.php
* @package		WAP
*/
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// show_private.php
// Personal details
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

wap_adm_start_card();

$db = new DB_Seminar;
$q_string  = "SELECT privatnr, privadr ";
$q_string .= "FROM auth_user_md5 LEFT JOIN user_info ";
$q_string .= "USING (user_id) WHERE username = '" . $user_name . "'";
$db-> query($q_string);
$db-> next_record();

$private_nr  = $db-> f("privatnr");
$private_adr = $db-> f("privadr");

echo "<p align=\"left\">\n";

if ($private_adr)
	echo wap_txt_encode_to_wml($private_adr) . "<br/>\n";

if ($private_nr)
{
	echo wap_txt_encode_to_wml(_("Tel:")) . "&#32;";
	echo wap_txt_encode_to_wml($private_nr) . "<br/>\n";
}

if ($back_to == "show_sms") {
	$postfields_back_to = "		<postfield name=\"sms_id\" value=\"$sms_id\"/>\n";
	$postfields_back_to .= "		<postfield name=\"no_search_link\" value=\"1\"/>\n";
	$postfields_back_to .= "		<postfield name=\"back_to\" value=\"show_sms\"/>\n";
}
else
	$postfields_back_to = "		<postfield name=\"directory_search_pc\" value=\"$directory_search_pc\"/>\n";

echo "</p>\n";

echo "<p align=\"right\">\n";
echo "<anchor>" . wap_buttons_back() . "\n";
echo "	<go method=\"post\" href=\"show_user.php\">\n";
echo "		<postfield name=\"session_id\" value=\"$session_id\"/>\n";
echo "		<postfield name=\"first_name\" value=\"$first_name\"/>\n";
echo "		<postfield name=\"last_name\" value=\"$last_name\"/>\n";
echo "		<postfield name=\"user_name\" value=\"$user_name\"/>\n";
echo $postfields_back_to;
echo "	</go>\n";
echo "</anchor><br/>\n";

if (!$no_search_link) {
	echo "<anchor>" . wap_buttons_new_search() . "\n";
	echo "	<go method=\"post\" href=\"directory.php\">\n";
	echo "		<postfield name=\"session_id\" value=\"$session_id\"/>\n";
	echo "	</go>\n";
	echo "</anchor><br/>\n";
}

wap_buttons_menu_link ($session_id);
echo "</p>\n";

wap_adm_end_card();
?>
