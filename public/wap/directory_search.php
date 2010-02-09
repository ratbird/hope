<?php
/**
* Outputs a list of found directory entries.
*
* If the user is not logged in, only persons who work at
* an institution will be displayed.<br/>
* Parameters received via stdin<br/>
* <code>
* 	$session_id
* 	$first_name
* 	$last_name
* 	$directory_search_pc (page counter)
* </code>
*
* @author		Florian Hansen <f1701h@gmx.net>
* @version		0.12	10.09.2003	21:22:41
* @access		public
* @modulegroup	wap_modules
* @module		directory_search.php
* @package		WAP
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// directory_search.php
// Output of directory entires
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
* Maximum of results displayed per page
* @const RESULTS_PER_PAGE
*/
define ("RESULTS_PER_PAGE", 5);

/**
* Maximum of results displayed
* @const NUM_MAX_RESULTS
*/
define ("NUM_MAX_RESULTS", 50);

include_once("wap_adm.inc.php");
include_once("wap_txt.inc.php");
include_once("wap_hlp.inc.php");
include_once("wap_buttons.inc.php");

$session_user_id = wap_adm_start_card($session_id);
if (!$session_expired)
{

	if ($last_name)
		if (strlen($last_name) > 2)
			$last_name_type = "part";
		else
			$last_name_type = "full";
	else
		$last_name_type = FALSE;
	
	if ($first_name)
		if (strlen($first_name) > 2)
			$first_name_type = "part";
		else
			$first_name_type = "full";
	else
		$first_name_type = FALSE;
	
	if ($last_name_type == "part" && $first_name_type)
		$first_name_type = "part";
	
	if ($first_name_type == "part" && $last_name_type)
		$last_name_type = "part";
	
	if ($last_name_type || $first_name_type)
	{
		$first_name = str_replace("%", "\%", $first_name);
		$first_name = str_replace("_", "\_", $first_name);
		$last_name  = str_replace("%", "\%", $last_name);
		$last_name  = str_replace("_", "\_", $last_name);
	
		if ($directory_search_pc)
		{
			$page_counter	 = $directory_search_pc;
			$progress_counter = $page_counter * RESULTS_PER_PAGE;
		}
		else
		{
			$page_counter	 = 0;
			$progress_counter = 0;
		}
	
		$db = new DB_Seminar;
	
		if ($session_user_id)
		{
			$q_string  = "SELECT COUNT(user_id) AS Anzahl ";
			$q_string .= "FROM auth_user_md5 ";
			$q_string .= "WHERE ";
	
			if ($last_name_type == "part")
				$q_string .= "Nachname LIKE '%$last_name%' ";
			elseif ($last_name_type == "full")
				$q_string .= "Nachname = '$last_name' ";
	
			if ($last_name_type && $first_name_type)
				$q_string .= "AND ";
	
			if ($first_name_type == "part")
				$q_string .= "Vorname LIKE '%$first_name%' ";
			elseif ($first_name_type == "full")
				$q_string .= "Vorname = '$first_name' ";
	
			$q_string .= "AND perms NOT IN ('root', 'admin')";
		}
		else
		{
			$q_string  = "SELECT COUNT(DISTINCT user_inst.user_id) AS Anzahl ";
			$q_string .= "FROM auth_user_md5 LEFT JOIN user_inst USING (user_id) ";
			$q_string .= "WHERE ";
	
			if ($last_name_type == "part")
				$q_string .= "auth_user_md5.Nachname LIKE '%$last_name%' ";
			elseif ($last_name_type == "full")
				$q_string .= "auth_user_md5.Nachname = '$last_name' ";
	
			if ($last_name_type && $first_name_type)
				$q_string .= "AND ";
	
			if ($first_name_type == "part")
				$q_string .= "auth_user_md5.Vorname LIKE '%$first_name%' ";
			elseif ($first_name_type == "full")
				$q_string .= "auth_user_md5.Vorname = '$first_name' ";
	
			$q_string .= "AND auth_user_md5.perms NOT IN ('root', 'admin') " ;
			$q_string .= "AND user_inst.inst_perms IN ('autor', 'tutor', 'dozent')";
		}
		$db-> query("$q_string");
		$db-> next_record();
		$num_all_results = $db-> f("Anzahl");
		$num_pages	   = ceil($num_all_results / RESULTS_PER_PAGE);
	
		if (($num_all_results > 0) && ($num_all_results <= NUM_MAX_RESULTS))
		{
			if ($session_user_id)
			{
				$q_string  = "SELECT Vorname, Nachname, user_id, username ";
				$q_string .= "FROM auth_user_md5 ";
					$q_string .= "WHERE ";
	
					if ($last_name_type == "part")
					$q_string .= "Nachname LIKE '%$last_name%' ";
				elseif ($last_name_type == "full")
					$q_string .= "Nachname = '$last_name' ";
	
					if ($last_name_type && $first_name_type)
					$q_string .= "AND ";
	
					if ($first_name_type == "part")
					$q_string .= "Vorname LIKE '%$first_name%' ";
					elseif ($first_name_type == "full")
					$q_string .= "Vorname = '$first_name' ";
	
				$q_string .= "AND perms NOT IN ('root', 'admin') ";
				$q_string .= "ORDER BY Nachname ";
				$q_string .= "LIMIT $progress_counter, " . RESULTS_PER_PAGE;
			}
			else
			{
				$q_string  = "SELECT DISTINCT ";
				$q_string .= "auth_user_md5.Vorname, auth_user_md5.Nachname, ";
				$q_string .= "auth_user_md5.username, auth_user_md5.user_id ";
				$q_string .= "FROM auth_user_md5 LEFT JOIN user_inst USING (user_id) ";
					$q_string .= "WHERE ";
	
					if ($last_name_type == "part")
					$q_string .= "auth_user_md5.Nachname LIKE '%$last_name%' ";
				elseif ($last_name_type == "full")
					$q_string .= "auth_user_md5.Nachname = '$last_name' ";
	
					if ($last_name_type && $first_name_type)
					$q_string .= "AND ";
	
					if ($first_name_type == "part")
					$q_string .= "auth_user_md5.Vorname LIKE '%$first_name%' ";
					elseif ($first_name_type == "full")
					$q_string .= "auth_user_md5.Vorname = '$first_name' ";
	
				$q_string .= "AND auth_user_md5.perms NOT IN ('root', 'admin') ";
				$q_string .= "AND user_inst.inst_perms IN ('autor', 'tutor', 'dozent')";
				$q_string .= "AND user_inst.user_id IS NOT NULL ";
				$q_string .= "ORDER BY auth_user_md5.Nachname ";
				$q_string .= "LIMIT $progress_counter, " . RESULTS_PER_PAGE;
			}
			$db-> query("$q_string");
			$num_entries = $db->nf();
			$progress_limit = $progress_counter + $num_entries;
	
			while ($db-> next_record() && $progress_counter < $progress_limit)
			{
				$progress_counter ++;
				$eintrag  = $db->f("Nachname");
				$eintrag .= ", ";
				$eintrag .= $db->f("Vorname");
				$user_name  = $db->f("username");
	
				echo "<p align=\"left\">\n";
				echo "<anchor>" . wap_txt_encode_to_wml($eintrag) . "\n";
				echo "	<go method=\"post\" href=\"show_user.php\">\n";
				echo "		<postfield name=\"session_id\" value=\"$session_id\"/>\n";
				echo "		<postfield name=\"first_name\" value=\"$first_name\"/>\n";
				echo "		<postfield name=\"last_name\" value=\"$last_name\"/>\n";
				echo "		<postfield name=\"user_name\" value=\"$user_name\"/>\n";
				echo "		<postfield name=\"directory_search_pc\" value=\"$page_counter\"/>\n";
				echo "	</go>\n";
				echo "</anchor>\n";
				echo "</p>\n";
	
				if ($progress_counter == $progress_limit)
				{
					echo "<p align=\"right\">\n";
					if ($progress_counter < $num_all_results)
					{
						$page_counter_v = $page_counter + 1;
						echo "<anchor>" . wap_buttons_forward_page($page_counter_v, $num_pages) . "\n";
						echo "	<go method=\"post\" href=\"directory_search.php\">\n";
						echo "		<postfield name=\"session_id\" value=\"$session_id\"/>\n";
						echo "		<postfield name=\"first_name\" value=\"$first_name\"/>\n";
						echo "		<postfield name=\"last_name\" value=\"$last_name\"/>\n";
						echo "		<postfield name=\"directory_search_pc\" value=\"$page_counter_v\"/>\n";
						echo "	</go>\n";
						echo "</anchor><br/>\n";
					}
	
					if ($page_counter > 0)
					{
						$page_counter_v = $page_counter - 1;
						echo "<anchor>" . wap_buttons_back_page($page_counter_v, $num_pages) . "\n";
						echo "	<go method=\"post\" href=\"directory_search.php\">\n";
						echo "		<postfield name=\"session_id\" value=\"$session_id\"/>\n";
						echo "		<postfield name=\"first_name\" value=\"$first_name\"/>\n";
						echo "		<postfield name=\"last_name\" value=\"$last_name\"/>\n";
						echo "		<postfield name=\"directory_search_pc\" value=\"$page_counter_v\"/>\n";
						echo "	</go>\n";
						echo "</anchor><br/>\n";
					}
					echo "</p>\n";
				}
			}
		}
		elseif ($num_all_results > NUM_MAX_RESULTS)
		{
			echo "<p align=\"left\">\n";
			$t = sprintf(_("Mehr als %s Einträge."), NUM_MAX_RESULTS);
			echo wap_txt_encode_to_wml($t) . "<br/>\n";
			$t = _("Bitte suchen Sie genauer.");
			echo wap_txt_encode_to_wml($t);
			echo "</p>\n";
		}
		else
		{
			echo "<p align=\"left\">";
			$t = _("Keinen Eintrag gefunden.");
			echo "? " . wap_txt_encode_to_wml($t) . " &#191;";
			echo "</p>\n";
		}
	
		echo "<p align=\"right\">\n";
		echo "<anchor>" . wap_buttons_new_search() . "\n";
		echo "	<go method=\"post\" href=\"directory.php\">\n";
		echo "		<postfield name=\"session_id\" value=\"$session_id\"/>\n";
		echo "	</go>\n";
		echo "</anchor><br/>\n";
	
		wap_buttons_menu_link($session_id);
		echo "</p>\n";
	}
	else
	{
		echo "<p align=\"left\">";
		$t = _("Bitte Vor- und/oder Nachnamen eingeben.");
		echo wap_txt_encode_to_wml($t) . "<br/>";
		echo "</p>\n";
	
		echo "<p align=\"right\">\n";
		echo "<anchor>" . wap_buttons_back() . "\n";
		echo "	<go method=\"post\" href=\"directory.php\">\n";
		echo "		<postfield name=\"session_id\" value=\"$session_id\"/>\n";
		echo "	</go>\n";
		echo "</anchor><br/>\n";
	
		wap_buttons_menu_link($session_id);
		echo "</p>\n";
	}
} // session_expired
wap_adm_end_card();
?>
