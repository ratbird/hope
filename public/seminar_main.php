<?php
# Lifter001: DONE
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
// vim: noexpandtab
/*
seminar_main.php - Die Eingangs- und Uebersichtsseite fuer ein Seminar
Copyright (C) 2000 Stefan Suchi <suchi@gmx.de>, Ralf Stockmann <rstockm@gwdg.de>

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
require_once('lib/dates.inc.php'); //Funktionen zur Anzeige der Terminstruktur
require_once('config.inc.php');
require_once('lib/visual.inc.php');
require_once 'lib/functions.php';
require_once 'lib/classes/CourseAvatar.class.php';
require_once 'lib/classes/StudygroupAvatar.class.php';

if ($GLOBALS['CHAT_ENABLE']){
	include_once $RELATIVE_PATH_CHAT."/chat_func_inc.php";
	if ($_REQUEST['kill_chat']){
		chat_kill_chat($_REQUEST['kill_chat']);
	}
}
if ($GLOBALS['VOTE_ENABLE']) {
	include_once ("lib/vote/vote_show.inc.php");
}

if (isset($auswahl) && $auswahl!="") {
		//just opened a seminar: we have to initialize the seminar for working with it
		openSem($auswahl);
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

mark_public_course($sem);

$HELP_KEYWORD="Basis.InVeranstaltungKurzinfo";
$CURRENT_PAGE = $SessSemName["header_line"]. " - " . _("Kurzinfo");
Navigation::activateItem('/course/main/info');

// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head

checkObject();

include 'lib/showNews.inc.php';
include 'lib/show_dates.inc.php';

$studygroup_mode = $SEM_CLASS[$SEM_TYPE[$SessSemName["art_num"]]["class"]]["studygroup_mode"];

// list of used modules
$Modules = new Modules;
$modules = $Modules->getLocalModules($SessSemName[1]);

$sem = Seminar::GetInstance($SessSemName[1]);

URLHelper::bindLinkParam("sem_data", $smain_data);

//Auf und Zuklappen Termine
if ($dopen)
	$smain_data["dopen"]=$dopen;

if ($dclose)
	$smain_data["dopen"]='';

//Auf und Zuklappen News
process_news_commands($smain_data);

//calculate a "quarter" year, to avoid showing dates that are older than a quarter year (only for irregular dates)
$quarter_year = 60 * 60 * 24 * 90;

?>
<table width="100%" border=0 cellpadding=0 cellspacing=0>
	<tr>
		<td class="blank" valign="top">
		<div style="padding:0 1.5em 1.5em 1.5em">
	<?
	echo "<h3>".htmlReady($SessSemName["header_line"]). "</h3>";
	if ($SessSemName[3]) {
		echo "<b>" . _("Untertitel:") . " </b>";
		echo htmlReady($SessSemName[3]);
		echo "<br>";
	}

	if (!$studygroup_mode) {
		echo '<b>'. _("Zeit") .':</b><br>';

		$data = getRegularOverview($SessSemName[1], true);		// second parameter set option to "shrink" dates
		if ($data) {
			echo $data . '<br>';
			if ($perm->have_studip_perm('autor', $SessSemName[1]) && $modules['schedule']) {
				echo '<br>';
				echo sprintf(_("Details zu allen Terminen im %sAblaufplan%s"), '<a href="'.URLHelper::getLink('?auswahl='.$SessSemName[1].'&redirect_to=dates.php').'">', '</a>');
				echo '<br>';
			}
		} else {
			echo _("Die Zeiten der Veranstaltung stehen nicht fest."). '<br>';
		}

		$next_date = $sem->getNextDate();
		if ($next_date) {
			echo '<br>';
			echo '<b>'._("Nächster Termin").':</b><br>';
			echo $next_date . '<br>';
		} else if ($first_date = $sem->getFirstDate()) {
			echo '<br>';
			echo '<b>'._("Erster Termin").':</b><br>';
			echo $first_date . '<br>';
		} else {
			echo '<br>';
			echo '<b>'._("Erster Termin").':</b><br>';
			echo _("Die Zeiten der Veranstaltung stehen nicht fest."). '<br>';
		}

	$dozenten = $sem->getMembers('dozent');
	$num_dozenten = count($dozenten);
	$show_dozenten = array();
	foreach($dozenten as $dozent) {
		$show_dozenten[] = '<a href="'.URLHelper::getLink("about.php?username=".$dozent['username']).'">'
							. htmlready($num_dozenten > 10 ? get_fullname($dozent['user_id'], 'no_title_short') : $dozent['fullname'])
							. '</a>';
	}
	printf("<br><b>%s: </b>%s", get_title_for_status('dozent', $num_dozenten), implode(', ', $show_dozenten));

	?>
		<br>
		<br>
	<?
		// Ticket #68
		if (!$perm->have_perm('dozent')) {
			require_once('lib/classes/AuxLockRules.class.php');
			$rule = AuxLockRules::getLockRuleBySemId($SessSemName[1]);
			if (isset($rule)) {
				$show = false;
				foreach ((array)$rule['attributes'] as $val) {
					if ($val == 1) {
						// Es gibt also Zusatzangaben. Nun noch überprüfen ob der Nutzer diese Angaben schon gemacht hat...
						$dbtg = new DB_Seminar($query = "SELECT * FROM datafields as d LEFT JOIN datafields_entries as de USING (datafield_id) WHERE d.object_type = 'usersemdata' AND de.sec_range_id = '".$SessSemName[1]."' AND de.range_id = '".$user->id."'");
						if ($dbtg->num_rows() == 0) {
							$show = true;
						}
						break;
					}
				}

				if ($show) { ?>
					<table cellspacing="1" cellpadding="0" border="0" style="border:1px solid black;background:#FFFFDD">
						<tr>
							<td align="center" valign="center">
								&nbsp;<img src="<?=$GLOBALS['ASSETS_URL']?>/images/ausruf.gif">&nbsp;
							</td>
							<td>
								<?= _("Sie haben noch nicht die für diese Veranstaltung benötigten Zusatzinformationen eingetragen.")?><br>
								<?= _("Um das nochzuholen gehen Sie unter \"TeilnehmerInnen\" auf \"Zusatzangaben\"")?><br>
								<?= _("oder klicken sie auf")?>
								&nbsp;&nbsp;<a href="<?=URLHelper::getLink("teilnehmer_aux.php")?>"><img src="<?=$GLOBALS['ASSETS_URL']?>/images/link_intern.gif" border="0" valign="absmiddle">&nbsp;<?= _("Direkt zu den Zusatzangaben") ?></a>
							</td>
						</tr>
					</table>
				<?
				}
			}
		}
	} else {
		echo '<b>'._('Beschreibung:').' </b><br>'. FixLinks(htmlReady($sem->description)) .'<br><br>';
		echo '<b>'._('Moderiert von:') .'</b> ';
		$all_mods = $sem->getMembers('dozent') + $sem->getMembers('tutor');
		unset($all_mods[md5('studygroup_dozent')]);
		$mods = array();
		foreach($all_mods as $mod) {
			$mods[] = '<a href="'.URLHelper::getLink("about.php?username=".$mod['username']).'">'.htmlready($mod['fullname']).'</a>';
		}
		echo implode(', ', $mods);
	}
?>
		</div>
		</td>
		<td class="blank" align="right" valign="top">
			<? if ($studygroup_mode) : ?>
			<?= StudygroupAvatar::getAvatar($SessSemName[1])->getImageTag(Avatar::NORMAL) ?>
			<? else: ?>
			<?= CourseAvatar::getAvatar($SessSemName[1])->getImageTag(Avatar::NORMAL) ?>
			<? endif; ?>
		</td>
	</tr>
	</table>
<br>

<?php

// Anzeige von News
if (show_news($auswahl, $rechte, 0, $smain_data["nopen"], "100%", object_get_visit($SessSemName[1], "sem"), $smain_data))
	echo"<br>";

// Anzeige von Terminen
$start_zeit=time();
$end_zeit=$start_zeit+1210000;
$name = rawurlencode($SessSemName[0]);
($rechte) ? $show_admin=URLHelper::getLink("admin_dates.php?range_id=$SessSemName[1]&ebene=sem&new_sem=TRUE") : $show_admin=FALSE;
if (!$studygroup_mode) {
	if (show_dates($start_zeit, $end_zeit, $smain_data["dopen"], $auswahl, 0, TRUE, $show_admin))
		echo"<br>";
}

// show chat info
if (($GLOBALS['CHAT_ENABLE']) && ($modules["chat"]))
{
	if (chat_show_info($auswahl))
	echo "<br>";
}

// include and show votes and tests
if ($GLOBALS['VOTE_ENABLE'])
{
	show_votes ($auswahl, $auth->auth["uid"], $perm, YES);
}

	include ('lib/include/html_end.inc.php');
	page_close();
?>
