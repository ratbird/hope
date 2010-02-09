<?php
# Lifter001: DONE
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/*
institut_main.php - Die Eingangsseite fuer ein Institut
Copyright (C) 200 Stefan Suchi <suchi@gmx.de>, Ralf Stockmann <rstockm@gwdg.de>, Cornelis Kater <ckater@gwdg.de>

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Default_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$auth->login_if($again && ($auth->auth["uid"] == "nobody"));

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page
require_once 'lib/dates.inc.php'; //Funktionen zur Anzeige der Terminstruktur
require_once 'lib/datei.inc.php';
require_once 'config.inc.php';
require_once 'lib/visual.inc.php';
require_once 'lib/functions.php';
require_once 'lib/classes/DataFieldEntry.class.php';
require_once 'lib/classes/InstituteAvatar.class.php';

if ($GLOBALS['CHAT_ENABLE']){
	include_once $RELATIVE_PATH_CHAT."/chat_func_inc.php";
	if ($_REQUEST['kill_chat']){
		chat_kill_chat($_REQUEST['kill_chat']);
	}

}
if ($GLOBALS['VOTE_ENABLE']) {
	include_once ("lib/vote/vote_show.inc.php");
}


// hier muessen Seiten-Initialisierungen passieren
if (isset($auswahl) && $auswahl!="") {
	//just opened Einrichtung... here follows the init
	openInst ($auswahl);
} else {
	$auswahl=$SessSemName[1];
}

	// gibt es eine Anweisung zur Umleitung?
	if(isset($redirect_to) && $redirect_to != "") {
		$take_it = 0;

		for ($i = 0; $i < count($i_query); $i++) { // alle Parameter durchwandern
			$parts = explode('=',$i_query[$i]);
			if ($parts[0] == "redirect_to") {
				// aha, wir haben die erste interessante Angabe gefunden
				$new_query = $parts[1];
				$take_it ++;
			} elseif ($take_it) {
				// alle weiteren Parameter mit einsammeln
				if ($take_it == 1) { // hier kommt der erste
					$new_query .= '?';
				} else { // hier kommen alle weiteren
					$new_query .= '&';
				}
				$new_query .= $i_query[$i];
				$take_it ++;
			}

		}
		unset($redirect_to);
		page_close();
		$new_query = preg_replace('/[^0-9a-z+_#?&=.-\/]/i', '', $new_query);
		header('Location: '.URLHelper::getURL($new_query));
		die;
	}

if (get_config('NEWS_RSS_EXPORT_ENABLE') && $SessSemName[1]){
	$rss_id = StudipNews::GetRssIdFromRangeId($SessSemName[1]);
	if($rss_id){
		$_include_additional_header = '<link rel="alternate" type="application/rss+xml" '
									.'title="RSS" href="rss.php?id='.$rss_id.'">';
	}
}


$HELP_KEYWORD="Basis.Einrichtungen";
$CURRENT_PAGE = $SessSemName["header_line"]. " - " ._("Kurzinfo");
Navigation::activateItem('/course/main/info');

// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head

checkObject();

include 'lib/showNews.inc.php';

// list of used modules
$Modules = new Modules;
$modules = $Modules->getLocalModules($SessSemName[1]);

URLHelper::bindLinkParam("inst_data", $institut_main_data);

//Auf und Zuklappen News
process_news_commands($institut_main_data);

?>

<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td class="blank" valign="top">
		<div style="padding:0 1.5em 1.5em 1.5em">
		<ul style="list-style-type:none;padding:0px;">
	<?
	$db = new DB_Seminar();
	$db->query ("SELECT a.*, b.Name AS fakultaet_name  FROM Institute a LEFT JOIN Institute b ON (b.Institut_id = a.fakultaets_id) WHERE a.Institut_id='$auswahl'");
	$db->next_record();

	if ($db->f("Strasse")) {
		echo "<li><b>" . _("Straﬂe:") . " </b>"; echo htmlReady($db->f("Strasse")); echo"</li>";
	}

	if ($db->f("Plz")) {
		echo "<li><b>" . _("Ort:") . " </b>"; echo htmlReady($db->f("Plz")); echo"</li>";
	}

	if ($db->f("telefon")) {
		echo "<li><b>" . _("Tel.:") . " </b>"; echo htmlReady($db->f("telefon")); echo"</li>";
	}

	if ($db->f("fax")) {
		echo "<li><b>" . _("Fax:") . " </b>"; echo htmlReady($db->f("fax")); echo"</li>";
	}

	if ($db->f("url")) {
		echo "<li><b>" . _("Homepage:") . " </b>"; echo formatReady($db->f("url")); echo"</li>";
	}

	if ($db->f("email")) {
		echo "<li><b>" . _("E-Mail:") . " </b>"; echo formatReady($db->f("email")); echo"</li>";
	}

	if ($db->f("fakultaet_name")) {
		echo "<li><b>" . _("Fakult&auml;t:") . " </b>"; echo htmlReady($db->f("fakultaet_name")); echo"</li>";
	}

	$localEntries = DataFieldEntry::getDataFieldEntries($SessSemName[1]);
	foreach ($localEntries as $entry) {
		if ($entry->structure->accessAllowed($perm) && $entry->getValue()) {
			echo "<li><b>" .htmlReady($entry->getName()) . ": </b>";
			echo $entry->getDisplayValue();
			echo "</li>";
		}
	}

?>
	</ul>
	</div>
	</td>
		<td class="blank" align="right" valign="top" style="padding:10px;">
			<?= InstituteAvatar::getAvatar($SessSemName[1])->getImageTag(Avatar::NORMAL) ?>
		</td>
		</tr>
	</table>
<br />
<?php

// Anzeige von News
($rechte) ? $show_admin=TRUE : $show_admin=FALSE;
if (show_news($auswahl,$show_admin, 0, $institut_main_data["nopen"], "100%", object_get_visit($SessSemName[1], "inst"), $institut_main_data))
	echo"<br>";

//show chat info
if (($GLOBALS['CHAT_ENABLE']) && ($modules["chat"])){
	if (chat_show_info($auswahl))
		echo "<br>";
}

// include and show votes and tests
if ($GLOBALS['VOTE_ENABLE']) {
	show_votes ($auswahl, $auth->auth["uid"], $perm, YES);
}

  include ('lib/include/html_end.inc.php');
  // Save data back to database.
  page_close()
 ?>
