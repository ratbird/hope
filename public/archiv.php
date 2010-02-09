<?
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/*
archiv.php - Suchmaske fuer das Archiv
Copyright (C) 2001 Ralf Stockmann <rstockm@gwdg.de>, Cornelis Kater <ckater@gwdg.de>

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

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check("user");

include('lib/seminar_open.php'); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page

if ($druck)
	$_include_stylesheet = 'style_print.css';
elseif (($dump_id) || ($forum_dump_id) || ($wiki_dump_id))
	$_include_stylesheet = 'style_dump.css';
$HELP_KEYWORD = "Basis.Archiv";
$CURRENT_PAGE = _("Archiv");

// Start of Output
include('lib/include/html_head.inc.php'); // Output of html head

require_once('lib/msg.inc.php');
require_once('config.inc.php');
require_once('lib/visual.inc.php');
require_once 'lib/functions.php';
require_once('lib/datei.inc.php');
require_once('lib/log_events.inc.php');

$db=new DB_Seminar;
$db2=new DB_Seminar;
$cssSw=new cssClassSwitcher;
$sess->register("archiv_data");

//Daten des Suchformulars uebernehmen oder loeschen
if ($suche) {
	$archiv_data='';
	$archiv_data["all"]=$all;
	$archiv_data["name"]=$name;
	$archiv_data["sem"]=$sem;
	$archiv_data["inst"]=$inst;
	$archiv_data["fak"]=$fak;
	$archiv_data["desc"]=$desc;
	$archiv_data["doz"]=$doz;
	$archiv_data["pers"]=$pers;
	$archiv_data["perform_search"]=TRUE;
} elseif ((!$open) && (!$delete_id) && (!$show_grants) && (!$hide_grants) && (!$delete_user) && (!$add_user) && (!$new_search) && (!$close) && (!$dump_id) && (!$sortby) && (!$back))
	$archiv_data["perform_search"]=FALSE;

//Anzeige der Zugriffsberechtigten Personen ein/ausschalten
if ($show_grants) {
	$archiv_data["edit_grants"]=TRUE;
	}
if ($hide_grants) {
	$archiv_data["edit_grants"]=FALSE;
	}

if ($open) {
	$archiv_data["open"]=$open;
	}

if (($close) || ($suche)){
	$archiv_data["open"]=FALSE;
	}

if ($sortby)
	$archiv_data["sortby"]=$sortby;

$u_id = $user->id;

//Sicherheitsabfrage
if ($delete_id) {
   	$db->query("SELECT name FROM archiv WHERE seminar_id= '$delete_id'");
	$db->next_record();
	$msg="info§" . sprintf(_("Wollen Sie die Veranstaltung <b>%s</b> wirklich l&ouml;schen? S&auml;mtliche Daten und die mit der Veranstaltung archivierte Dateisammlung werden unwiderruflich gel&ouml;scht!"), htmlReady($db->f("name"))) . " <br>";
	$msg.="<a href=\"". URLHelper::getLink("?delete_really=TRUE&delete_id=$delete_id") ."\">" . makeButton("ja2", "img") . "</a>&nbsp; \n";
	$msg.="<a href=\"". URLHelper::getLink("?back=TRUE") ."\">" . makeButton("nein", "img") . "</a>\n";

}

//Loeschen aus dem Archiv
if (($delete_id) && ($delete_really)){
	if (archiv_check_perm($delete_id) == "admin") {
		$db2->query("SELECT name,archiv_file_id FROM archiv WHERE seminar_id='$delete_id'");
		$db2->next_record();
		$db->query("DELETE FROM archiv WHERE seminar_id = '$delete_id'");
		if ($db->affected_rows()){
			$msg = sprintf("msg§" . _("Die Veranstaltung %s wurde aus dem Archiv gel&ouml;scht") . "§", htmlReady($db2->f("name")));
                        log_event("SEM_DELETE_FROM_ARCHIVE",$delete_id,NULL,$db2->f("name")." (".$db2->f("semester").")"); // ...logging...
		}
		if ($db2->f("archiv_file_id")) {
			if (unlink ($ARCHIV_PATH."/".$db2->f("archiv_file_id"))){
				$msg .= "msg§" . _("Das Zip-Archiv der Veranstaltung wurde aus dem Archiv gel&ouml;scht.") ."§";
			} else {
				$msg.="error§" . _("Das Zip-Archiv der Veranstaltung konnte nicht aus dem Archiv gel&ouml;scht werden.") ."§";
			}
		}
		$db->query("DELETE FROM archiv_user WHERE seminar_id='$delete_id'");
		if ($db->affected_rows()){
			$msg .= sprintf("msg§" . _("Es wurden %s Zugriffsberechtigungen entfernt.") . "§", $db->affected_rows());
		}
	} else {
		$msg="error§" . _("Sie haben leider nicht die notwendige Berechtigung für diese Aktion.");
	}
}

//Loeschen von Archiv-Usern
if ($delete_user) {
	if (archiv_check_perm($d_sem_id) == "admin" || archiv_check_perm($d_sem_id) == "dozent") {
		$db->query("DELETE FROM archiv_user WHERE seminar_id = '$d_sem_id' AND user_id='$delete_user'");
		if ($db->affected_rows()){
			$msg="msg§" . _("Zugriffsberechtigung entfernt") . "§";
		}
	} else {
		$msg="error§" . _("Sie haben leider nicht die notwendige Berechtigung für diese Aktion.");
	}
}

//Eintragen von Archiv_Usern
if ($do_add_user) {
	if (archiv_check_perm($a_sem_id) == "admin" || archiv_check_perm($a_sem_id) == "dozent") {
		$db->query("INSERT IGNORE INTO archiv_user SET seminar_id = '$a_sem_id', user_id='$add_user', status='autor'");
		if ($db->affected_rows()){
			$msg="msg§" . _("Zugriffsberechtigung erteilt") . "§";
		}
	} else {
		$msg="error§" . _("Sie haben leider nicht die notwendige Berechtigung für diese Aktion.");
	}
	$add_user=FALSE;
}


// wollen wir den dump?

if (!empty($dump_id)) {
	if (archiv_check_perm($dump_id)){
		$query = "SELECT dump FROM archiv WHERE archiv.seminar_id = '$dump_id'";
		$db->query ($query);
		if ($db->next_record()) {
			if (!isset($druck)) {
				echo '<div align=center> <a href="'. URLHelper::getLink("?dump_id=".$dump_id."&druck=1") .'" target="_self"><b>' . _("Druckversion") . "</b></a><br><br></div>";
			}
			echo $db->f('dump');
		}
	} else {
		echo _("Sie haben leider nicht die notwendige Berechtigung für diese Aktion.");
	}
}

// oder vielleicht den Forendump?

elseif (!empty($forum_dump_id)) {
	if (archiv_check_perm($forum_dump_id)){
		$query = "SELECT forumdump FROM archiv WHERE archiv.seminar_id = '$forum_dump_id'";
		$db->query ($query);
		if ($db->next_record()) {
			if (!isset($druck)) {
				echo '<div align=center> <a href="'. URLHelper::getLink("?forum_dump_id=".$forum_dump_id."&druck=1") .'" target=_self><b>' . _("Druckversion") . "</b></a><br><br></div>";
			}
			echo $db->f('forumdump');
		}
	} else {
		echo _("Sie haben leider nicht die notwendige Berechtigung für diese Aktion.");
	}
}

// oder vielleicht den Wikidump?

elseif (!empty($wiki_dump_id)) {
	if (archiv_check_perm($wiki_dump_id)){
		$query = "SELECT wikidump FROM archiv WHERE archiv.seminar_id = '$wiki_dump_id'";
		$db->query ($query);
		if ($db->next_record()) {
			if (!isset($druck)) {
				echo '<div align=center> <a href="'. URLHelper::getLink("?wiki_dump_id=".$wiki_dump_id."&druck=1") .'" target=_self><b>' . _("Druckversion") . "</b></a><br><br></div>";
			}
			echo "<table class=blank width=95% align=center><tr><td>";
			echo stripslashes($db->f('wikidump'));
			echo "</td></tr></table>";
		}
	} else {
		echo _("Sie haben leider nicht die notwendige Berechtigung für diese Aktion.");
	}
}

else {

$HELP_KEYWORD="Basis.SuchenArchiv";

// dann eben den Rest...

include('lib/include/header.php');   //hier wird der "Kopf" nachgeladen
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td class="topic" colspan="2">Archiv</td>
	</tr>
	<?
	if ($msg) { ?>
	<tr>
		<td class="blank" colspan="2"><? parse_msg($msg); ?></td>
	</tr>
	<? } ?>
	<tr>
		<td class="blank" width="60%" align="left">
			<blockquote>
			<br>
				<p>
				<form  name="search" method="post" action="<?= URLHelper::getLink() ?>" >
					<table border=0 cellspacing=0 cellpadding=2>
						<tr <? $cssSw->switchClass() ?>>
							<td class="<? echo $cssSw->getClass() ?>" colspan=2>
							<b><font size=-1><?=_("Bitte geben Sie hier Ihre Suchkriterien ein:")?></font></b><br>
							</td>
						</tr>
						<tr <? $cssSw->switchClass() ?>>
							<td class="<? echo $cssSw->getClass() ?>" width="10%">
								<font size=-1><?=_("Name der Veranstaltung:")?></font>
							</td>
							<td class="<? echo $cssSw->getClass() ?>" width="90%">
								<input  type="text"  size=30 maxlength=255 name="name" value="<? echo htmlReady(stripslashes($archiv_data["name"])) ?>">
							</td>
						</tr>
						<tr <? $cssSw->switchClass() ?>>
							<td class="<? echo $cssSw->getClass() ?>" width="10%">
								<font size=-1><?=_("DozentIn der Veranstaltung:")?></font>
							</td>
							<td  class="<? echo $cssSw->getClass() ?>" width="90%">
								<input  type="text"  size=30 maxlength=255 name="doz" value="<? echo htmlReady(stripslashes($archiv_data["doz"])) ?>">
							</td>
						</tr>
						<tr <? $cssSw->switchClass() ?>>
							<td class="<? echo $cssSw->getClass() ?>"  width="10%">
								<font size=-1><?=_("Semester")?> </font>
							</td>
							<td class="<? echo $cssSw->getClass() ?>"  width="90%">
								<font size=-1>
								<select name="sem">
								<option selected value=0><?=_("alle")?></option>
								<?
								$db->query("SELECT DISTINCT semester FROM archiv ORDER BY start_time");
								while ($db->next_record())
									if  ($db->f("semester"))
										if ($db->f("semester") == $archiv_data["sem"])
											echo "<option selected value=\"", $db->f("semester"), "\">", $db->f("semester"), "</option>";
										else
											echo "<option value=\"", $db->f("semester"), "\">", $db->f("semester"), "</option>";
								?>
								</select>
								</font>
							</td>
						</tr>
						<tr <? $cssSw->switchClass() ?>>
							<td class="<? echo $cssSw->getClass() ?>" width="10%">
								<font size=-1><?=_("Heimat-Einrichtung")?> </font>
							</td>
							<td class="<? echo $cssSw->getClass() ?>"  width="90%">
								<font size=-1>
								<select name="inst">
								<option selected value=0><?=_("alle")?></option>
								<?
								$db->query("SELECT DISTINCT heimat_inst_id, Institute.Name FROM archiv LEFT JOIN Institute ON (Institut_id=heimat_inst_id)  ORDER BY Name");
								while ($db->next_record())
									{
									if  (($db->f("Name")) && ($db->f("Name")) !="- - -")
										if ($db->f("heimat_inst_id") == $archiv_data["inst"])
											echo "<option selected value=", $db->f("heimat_inst_id"), ">", htmlReady(my_substr($db->f("Name"),0, 40)), "</option>";
										else
											echo "<option value=", $db->f("heimat_inst_id"), ">", htmlReady(my_substr($db->f("Name"),0, 40)), "</option>";

									}
								?>
								</select>
								</font>
							</td>
						</tr>
						<tr <? $cssSw->switchClass() ?>>
							<td class="<? echo $cssSw->getClass() ?>" width="10%">
								<font size=-1><?=_("Fakultät")?> </font>
							</td>
							<td class="<? echo $cssSw->getClass() ?>"  width="90%">
								<font size=-1>
								<select name="fak">
								<option selected value=0><?=_("alle")?></option>
								<?
								$db->query("SELECT DISTINCT fakultaet FROM archiv ORDER BY fakultaet");
								while ($db->next_record())
									{
									if ($db->f("fakultaet"))
										if ($db->f("fakultaet") == stripslashes($archiv_data["fak"]))
											echo '<option selected value="'. htmlReady($db->f("fakultaet")). '">'. htmlReady(my_substr($db->f("fakultaet"),0, 40)). "</option>";
										else
											echo '<option value="'. htmlReady($db->f("fakultaet")). '">'. htmlReady(my_substr($db->f("fakultaet"),0, 40)). "</option>";

									}
								?>
								</select>
								</font>
							</td>
						</tr>
						<tr <? $cssSw->switchClass() ?>>
							<td class="<? echo $cssSw->getClass() ?>" width="10%">
								<font size=-1><?=_("Beschreibung:")?></font>
							</td>
							<td class="<? echo $cssSw->getClass() ?>" width="90%">
								<input  type="text"  size=30 maxlength=255 name="desc" value="<?echo htmlReady(stripslashes($archiv_data["desc"])) ?>">
							</td>
						</tr>
						<tr <? $cssSw->switchClass() ?>>
							<td class="<? echo $cssSw->getClass() ?>" width="10%">
								<font size=-1><?=_("Suche &uuml;ber <b>alle</b> Felder:")?></font>
							</td>
							<td class="<? echo $cssSw->getClass() ?>" width="90%">
								<input  type="text"  size=30 maxlength=255 name="all" value="<? echo htmlReady(stripslashes($archiv_data["all"])) ?>">
							</td>
						</tr>
						<tr <? $cssSw->switchClass() ?>>
							<td class="<? echo $cssSw->getClass() ?>" width="10%">
								&nbsp;
							</td>
							<td class="<? echo $cssSw->getClass() ?>" width="90%">
								<input  type="checkbox" name="pers" <? if ($archiv_data["pers"]) echo "checked" ?>>
								<font size=-1><?=_("Nur Veranstaltungen anzeigen, an denen ich teilgenommen habe")?></font>
							</td>
						</tr>
					   	<tr <? $cssSw->switchClass() ?>>
					   		<td class="<? echo $cssSw->getClass() ?>" width="10%">
					   			&nbsp;
					   		</td>
					   		<td class="<? echo $cssSw->getClass() ?>" width="90%">
					   			<center>
					   				<input type="IMAGE" border=0 <?=makeButton("suchestarten", "src")?> value="<?=_("Suche starten")?>">
					   			</center>
					   		</td>
						</tr>
					</table>
					<br>
					<input type="hidden" name="suche" value="yes">
				</form>
			</blockquote>
		</td>
		<td class="blank" align="right" valign="top"><br><img src="<?= $GLOBALS['ASSETS_URL'] ?>images/archiv.jpg" border="0">
		</td>
	</tr>

<?

// wollen wir was Suchen?

if ($archiv_data["perform_search"]) {
	//searchstring to short?
	if ((((strlen($archiv_data["all"]) < 4) && ($archiv_data["all"]))
		|| ((strlen($archiv_data["name"]) < 4) && ($archiv_data["name"]))
		|| ((strlen($archiv_data["desc"]) < 4) && ($archiv_data["desc"]))
		|| ((strlen($archiv_data["doz"]) < 4) && ($archiv_data["doz"])))
		&& (!$archiv_data["pers"]) && (!$archiv_data["inst"]) && (!$archiv_data["fak"]))
		$string_too_short = TRUE;
	if ((!$archiv_data["all"]) && (!$archiv_data["name"]) && (!$archiv_data["desc"]) && (!$archiv_data["doz"]) && (!$archiv_data["pers"]) && (!$archiv_data["inst"]) && (!$archiv_data["fak"]))
		$string_too_short = TRUE;

	if (!$archiv_data["sortby"])
		$archiv_data["sortby"]="Name";
	if ($archiv_data["pers"])
		$query ="SELECT archiv.seminar_id, name, untertitel,  beschreibung, start_time, semester, studienbereiche, heimat_inst_id, institute, dozenten, fakultaet, archiv_file_id, forumdump, wikidump FROM archiv LEFT JOIN archiv_user USING (seminar_id) WHERE user_id = '".$user->id."' AND ";
	else
		$query ="SELECT seminar_id, name, untertitel,  beschreibung, start_time, semester, studienbereiche, heimat_inst_id, institute, dozenten, fakultaet, archiv_file_id, forumdump, wikidump FROM archiv WHERE ";
	if ($archiv_data["all"]) {
		$query .= "name LIKE '%".trim($archiv_data["all"])."%'";
		$query .= " OR untertitel LIKE '%".trim($archiv_data["all"])."%'";
		$query .= " OR beschreibung LIKE '%".trim($archiv_data["all"])."%'";
		$query .= " OR start_time LIKE '%".trim($archiv_data["all"])."%'";
		$query .= " OR semester LIKE '%".trim($archiv_data["all"])."%'";
		$query .= " OR studienbereiche LIKE '%".trim($archiv_data["all"])."%'";
		$query .= " OR institute LIKE '%".trim($archiv_data["all"])."%'";
		$query .= " OR dozenten LIKE '%".trim($archiv_data["all"])."%'";
		$query .= " OR fakultaet LIKE '%".trim($archiv_data["all"])."%'";
	} else {
		if ($archiv_data["name"])
			$query .= "name LIKE '%".trim($archiv_data["name"])."%'";
		else
			$query .= "name LIKE '%%'";
		if ($archiv_data["desc"])
			$query .= " AND beschreibung LIKE '%".trim($archiv_data["desc"])."%'";
		else
			$query .= " AND beschreibung LIKE '%%'";
		if ($archiv_data["sem"])
			$query .= " AND semester LIKE '%".trim($archiv_data["sem"])."%'";
		else
			$query .= " AND semester LIKE '%%'";
		if ($archiv_data["inst"])
			$query .= " AND heimat_inst_id LIKE '%".trim($archiv_data["inst"])."%'";
		else
			$query .= " AND heimat_inst_id LIKE '%%'";
		if ($archiv_data["doz"])
			$query .= " AND dozenten LIKE '%".trim($archiv_data["doz"])."%'";
		else
			$query .= " AND dozenten LIKE '%%'";
		if ($archiv_data["fak"])
			$query .= " AND fakultaet LIKE '%".trim($archiv_data["fak"])."%'";
		else
			$query .= " AND fakultaet LIKE '%%'";
	}
	$query .= " ORDER BY ".$archiv_data["sortby"];

	$db->query($query);

	if ((!$db->affected_rows() == 0) && (!$string_too_short)) {
		$hits = $db->affected_rows();

	?>
	<tr>
		<td class="blank" colspan="2">
		<?

		echo "<blockquote><b><font size=-1>";
		printf(_("Es wurden %s Veranstaltungen gefunden."), $hits);
		echo "</font></b></blockquote>";


	 	echo "<br><br><TABLE class=\"blank\"  WIDTH=99% align=center cellspacing=0 border=0>\n";
   	echo "<tr height=28><td  width=\"1%\" class=\"steel\"><img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\" width=1 height=20>&nbsp; </td>\n";
		echo "<td  width=\"29%\" class=\"steel\" align=center valign=bottom><b><a href=\"". URLHelper::getLink("?sortby=Name") ."\">" . _("Name") . "</a></b></td>\n";
		echo "<td  width=\"20%\" class=\"steel\" align=center valign=bottom><b><a href=\"". URLHelper::getLink("?sortby=dozenten") ."\">" . _("DozentIn") . "</a></b></td>\n";
		echo "<td  width=\"20%\" class=\"steel\" align=center valign=bottom><b><a href=\"". URLHelper::getLink("?sortby=institute") ."\">" . _("Einrichtungen") . "</a></b></td>\n";
		echo "<td  width=\"20%\" class=\"steel\" align=center valign=bottom><b><a href=\"". URLHelper::getLink("?sortby=semester") ."\">" . _("Semester") . "</a></b></td>\n";
		echo "<td  width=\"10%\" class=\"steel\" colspan=3 align=center valign=bottom><b>" . _("Aktion") . "</b></td></tr>\n";

		$c=0;
    while ($db->next_record()) {
 			$file_name=_("Dateisammlung") . " ".substr($db->f("name"),0,200).".zip";
	 		$view = 0;
			if ($archiv_data["open"]) {
	 	  	if ($archiv_data["open"] ==$db->f('seminar_id'))
 		  		$class="steelgraulight";
 		  	else
 		  		$class="steel1";
 		  } else {
	 	  	if ($c % 2)
  				$class="steelgraulight";
				else
					$class="steel1";
				$c++;
			}

			echo "<tr><td class=\"$class\" WIDTH=\"1%\" nowrap>&nbsp;";

      		// schon aufgeklappt?
			if ($archiv_data["open"]==$db->f('seminar_id')) {
				echo "<a name=\"anker\"></a><a href=\"". URLHelper::getLink("close=yes") ."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/forumgraurunt.gif\" " . tooltip(_("Zuklappen")) . " border=\"0\" valign=\"top\"></a></td>";
				echo "<td class=\"$class\" width=\"29%\"><font size=\"-1\"><b><a href=\"". URLHelper::getLink("close=yes") ."\">".htmlReady($db->f("name"))."</a></b></font></td>";
			} else {
	      echo "<a href=\"". URLHelper::getLink("?open=" . $db->f('seminar_id')) . "#anker\"><img src=\"".$GLOBALS['ASSETS_URL']."images/forumgrau.gif\" " . tooltip(_("Aufklappen")) . " border=\"0\" valign=\"top\"></a></td>";
				echo "<td class=\"$class\" width=\"29%\"><font size=\"-1\"><a href=\"". URLHelper::getLink("?open=" . $db->f('seminar_id')) . "#anker\">".htmlReady($db->f("name"))."</a></font></td>";
			}
	    echo "<td align=center class=\"$class\">&nbsp;<font size=-1>".htmlReady($db->f("dozenten"))."</font></td>";
	 		echo "<td align=center class=\"$class\">&nbsp;<font size=-1>".htmlReady($db->f("institute"))."</font></td>";
	 		echo "<td align=center class=\"$class\">&nbsp;<font size=-1>".htmlReady($db->f("semester"))."</font></td>";

			if (archiv_check_perm($db->f("seminar_id")))
				$view = 1;
			if ($view == 1) {
				echo "<td class=\"$class\" width=\"3%\">&nbsp;<a href=\"". URLHelper::getLink("?dump_id=".$db->f('seminar_id')) ."\" target=_blank><img src=\"".$GLOBALS['ASSETS_URL']."images/i.gif\" " . tooltip(_("Komplettansicht")) . " border=\"0\"></a></td>";
				echo "<td class=\"$class\" width=\"3%\">&nbsp;";
				if (!$db->f('archiv_file_id')=='') {
					echo '<a href="' . URLHelper::getLink(GetDownloadLink($db->f('archiv_file_id'), $file_name, 1)) .'"><img src="'.$GLOBALS['ASSETS_URL'].'images/files.gif" ' . tooltip(_("Dateisammlung")) . ' border="0"></a>';
				}
				echo "</td><td class=\"$class\" width=\"3%\">&nbsp;";
				if (archiv_check_perm($db->f("seminar_id")) == "admin")
					echo "<a href=\"". URLHelper::getLink("?delete_id=".$db->f('seminar_id')) ."\">&nbsp;<img border=0 src=\"".$GLOBALS['ASSETS_URL']."images/trash.gif\" " . tooltip(_("Diese Veranstaltung aus dem Archiv entfernen")) . "></a>";
				echo "</td>";
			} else
				echo "<td class=\"$class\" width=\"9%\" colspan=\"3\">&nbsp;</td>";

			if ($archiv_data["open"] == $db->f('seminar_id')) {
				echo "</tr><tr><td class=\"steelgraulight\" colspan=8><blockquote>";
				if (!$db->f('untertitel')=='')
					echo "<li><font size=\"-1\"><b>" . _("Untertitel:") . " </b>".htmlReady($db->f('untertitel'))."</font></li>";
				if (!$db->f('beschreibung')=='')
					echo "<li><font size=\"-1\"><b>" . _("Beschreibung:") . " </b>".htmlReady($db->f('beschreibung'))."</font></li>";
				if (!$db->f('fakultaet')=='')
					echo "<li><font size=\"-1\"><b>" . _("Fakult&auml;t:") . " </b>".htmlReady($db->f('fakultaet'))."</font></li>";
				if (!$db->f('studienbereiche')=='')
					echo "<li><font size=-1><b>" . _("Bereich:") . " </b>".htmlReady($db->f('studienbereiche'))."</font></li>";

			// doppelt haelt besser: noch mal die Extras

				if ($view == 1) {
					echo "<br><br><li><a href=\"". URLHelper::getLink("?dump_id=".$db->f('seminar_id')) ."\" target=_blank><font size=\"-1\">" . _("&Uuml;bersicht der Veranstaltungsinhalte") . "</font></a></li>";
					if (!$db->f('forumdump')=='')
						echo "<li><font size=\"-1\"><a href=\"". URLHelper::getLink("?forum_dump_id=".$db->f('seminar_id')) ."\" target=_blank>" . _("Beitr&auml;ge des Forums") . "</a></font></li>";
					if (!$db->f('wikidump')=='')
						echo "<li><font size=\"-1\"><a href=\"". URLHelper::getLink("?wiki_dump_id=".$db->f('seminar_id')) ."\" target=_blank>" . _("Wikiseiten") . "</a></font></li>";
					if (!$db->f('archiv_file_id')=='') {
						echo '<li><font size="-1"><a href="' . URLHelper::getLink(GetDownloadLink($db->f('archiv_file_id'), $file_name, 1)) .'">' . _("Download der Dateisammlung") . '</a></font></li>';
					}
					if (archiv_check_perm($db->f("seminar_id")) == "admin")
						echo "<li><a href=\"". URLHelper::getLink("?delete_id=".$db->f('seminar_id')) ."\"><font size=\"-1\">" . _("Diese Veranstaltung unwiderruflich aus dem Archiv entfernen") . "</font></a></li>";
					if (archiv_check_perm($db->f("seminar_id")) == "admin") {
						if (!$archiv_data["edit_grants"])
							echo "<li><font size=\"-1\"><a href=\"". URLHelper::getLink("?show_grants=yes") ."#anker\">" . _("Zugriffsberechtigungen einblenden") . "</a></font></li>";
						else
							echo "<li><font size=\"-1\"><a href=\"". URLHelper::getLink("?hide_grants=yes") ."#anker\">" . _("Zugriffsberechtigungen ausblenden") . "</a></font></li>";
					}
				} else
					echo "<br><br><li><font size=\"-1\">" . _("Die Veranstaltungsinhalte, Beitr&auml;ge im Forum und das Dateiarchiv sind nicht zug&auml;ngig, da Sie an dieser Veranstaltung nicht teilgenommen haben.") . "</font></li>";

				if ($archiv_data["edit_grants"]) {
					echo "<br><br><hr><b><font size=\"-1\">" . _("Folgende Personen haben Zugriff auf die Daten der Veranstaltung (&Uuml;bersicht, Beitr&auml;ge und Dateiarchiv):") . "</font></b><br><br>";
					$db2->query("SELECT " . $_fullname_sql['full'] . " AS fullname , archiv_user.status, username, archiv_user.user_id FROM archiv_user LEFT JOIN auth_user_md5 USING (user_id) LEFT JOIN user_info USING (user_id) WHERE seminar_id = '".$db->f("seminar_id")."' ORDER BY Nachname");
					while ($db2->next_record()) {
						echo "<font size=\"-1\">".htmlReady($db2->f("fullname")). " (" . _("Status:") . " ". $db2->f("status"). ")</font>";
						if ($db2->f("status") != "dozent")
							echo "<a href=\"". URLHelper::getLink("?delete_user=".$db2->f("user_id")."&d_sem_id=".$db->f("seminar_id")) ,"#anker\"><font size=\"-1\">&nbsp;" . _("Zugriffsberechtigung entfernen") . "</font> <img border=0 src=\"".$GLOBALS['ASSETS_URL']."images/trash.gif\" " . tooltip(_("Dieser Person die Zugriffsberechtigung entziehen")) . "></a>";
						echo "<br>";
					}
					if (($add_user) && (!$new_search)) {
						$db2->query("SELECT " . $_fullname_sql['full'] . " AS fullname, username, auth_user_md5.user_id FROM auth_user_md5 LEFT JOIN user_info USING (user_id) WHERE Vorname LIKE '%$search_exp%' OR Nachname LIKE '%$search_exp%' OR username LIKE '%".trim($search_exp)."%' ORDER BY Nachname");
						if ($db2->affected_rows()) {
							echo "<form action=\"". URLHelper::getLink() ."#anker\">";
							echo "<hr><b><font size=\"-1\">" . _("Person Berechtigung erteilen:") . " </font></b><br><br>";
							echo "<b><font size=\"-1\">" . sprintf(_("Es wurden %s Personen gefunden"), $db2->affected_rows()) . " </font></b><br>";
							echo "<font size=\"-1\">" . _("Bitte w&auml;hlen Sie die Person aus der Liste aus:") . "</font>&nbsp;<br><font size=\"-1\"><select name=\"add_user\">";
							while ($db2->next_record()) {
								echo "<option value=\"".$db2->f("user_id")."\">".htmlReady($db2->f("fullname")). " (".$db2->f("username").") </option>";
							}
							echo "</select></font>";
							echo "<br><font size=\"-1\"><input type=\"SUBMIT\"  name=\"do_add_user\" value=\"" . _("Diese Person hinzuf&uuml;gen") . "\" /></font>";
							echo "&nbsp;<font size=\"-1\"><input type=\"SUBMIT\"  name=\"new_search\" value=\"" . _("Neue Suche") . "\" /></font>";
							echo "<input type=\"HIDDEN\"  name=\"a_sem_id\" value=\"",$db->f("seminar_id"), "\" />";
							echo "</form>";
						}
					}
					if ((($add_user) && (!$db2->affected_rows())) || (!$add_user) || ($new_search)) {
						echo "<form action=\"". URLHelper::getLink() ."#anker\">";
						echo "<hr><b><font size=\"-1\">" . _("Person Berechtigung erteilen:") . " </font></b><br>";
						if (($add_user) && (!$db2->affected_rows())  && (!$new_search))
							echo "<br><b><font size=\"-1\">" . _("Es wurde keine Person zu dem eingegebenem Suchbegriff gefunden!") . "</font></b><br>";
						echo "<font size=\"-1\">" . _("Bitte Namen, Vornamen oder Usernamen eingeben:") . "</font>&nbsp; ";
						echo "<br><input type=\"TEXT\" size=20 maxlength=255 name=\"search_exp\" />";
						echo "&nbsp;<font size=\"-1\"><br><input type=\"SUBMIT\"  name=\"add_user\" value=\"" . _("Suche starten") . "\" /></font>";
						echo "</form>";
					}
				}
				echo "</blockquote></td>";
			}
			echo "</tr>";
		}
		echo "</table><br><br>";
	} else {
		echo "<tr><td class=\"blank\" colspan=2>" . (($string_too_short) ? MessageBox::error(_("Der Suchbegriff ist zu kurz.")) : MessageBox::info(_("Es wurde keine Veranstaltung gefunden.")));
  }
}

?>
		</td>
	</tr>
</table>
<?
}
	include ('lib/include/html_end.inc.php');
	page_close();
 ?>
