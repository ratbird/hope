<?php
# Lifter001: TEST
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/*
show_bereich.php - Anzeige von Veranstaltungen eines Bereiches oder Institutes
Copyright (C) 2000 Cornelis Kater <ckater@gwdg.de>

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
ob_start();
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page
require_once('lib/visual.inc.php');
require_once 'lib/classes/SemBrowse.class.php';


	$db=new DB_Seminar;
	$intro_text = $head_text = '';
	if ($id){
		URLHelper::bindLinkParam('id',$id);
		URLHelper::bindLinkParam('level',$level);
	}

	$level = Request::get('level');
	$id = Request::get('id');

	$group_by = Request::int('group_by', 0);

	$sem_browse_obj = new SemBrowse(array('group_by' => 0));
	$sem_browse_obj->sem_browse_data['default_sem'] = "all";
	$sem_browse_obj->sem_number = false;
	$sem_browse_obj->target_url="details.php";	//teilt der nachfolgenden Include mit, wo sie die Leute hinschicken soll
	$sem_browse_obj->target_id="sem_id"; 		//teilt der nachfolgenden Include mit, wie die id die &uuml;bergeben wird, bezeichnet werden soll
	$sem_browse_obj->sem_browse_data['level'] = $level;
	switch ($level) {
		case "sbb":
			$the_tree =& TreeAbstract::GetInstance("StudipSemTree", array('visible_only' => !$GLOBALS['perm']->have_perm(get_config('SEM_VISIBILITY_PERM'))));
			$bereich_typ = _("Studienbereich");
			$head_text = _("Übersicht aller Veranstaltungen eines Studienbereichs");
			$intro_text = sprintf(_("Alle Veranstaltungen, die dem Studienbereich: <br><b>%s</b><br> zugeordnet wurden."),
							htmlReady($the_tree->getShortPath($id)));
			$sem_ids = $the_tree->getSemIds($id, false);
			if (is_array($sem_ids)){
				$sem_browse_obj->sem_browse_data['search_result'] = array_flip($sem_ids);
			} else {
				$sem_browse_obj->sem_browse_data['search_result'] = array();
			}
			$sem_browse_obj->show_result = true;
			$sem_browse_obj->sem_browse_data['sset'] = false;
			$sem_browse_obj->sem_browse_data['start_item_id'] = $id;
			break;
		case "s":
			$bereich_typ=_("Einrichtung");
			$db->query("SELECT Name FROM Institute WHERE Institut_id='".$id."'");
			$db->next_record();
			$head_text = _("Übersicht aller Veranstaltungen einer Einrichtung");
			$intro_text = sprintf(_("Alle Veranstaltungen der Einrichtung <b>%s</b>"), htmlReady($db->f("Name")));
			$db->query("SELECT seminar_inst.seminar_id FROM seminar_inst
			LEFT JOIN seminare ON (seminar_inst.seminar_id=seminare.Seminar_id)
			WHERE seminar_inst.Institut_id='".$id."'" . (!$GLOBALS['perm']->have_perm(get_config('SEM_VISIBILITY_PERM')) ? " AND seminare.visible='1'" : ""));

			$sem_browse_obj->sem_browse_data['search_result'] = array();
			while ($db->next_record()){
				$sem_browse_obj->sem_browse_data['search_result'][$db->f("seminar_id")] = true;
			}
			$sem_browse_obj->show_result = true;
			break;
	}

if (isset($_REQUEST['send_excel'])){
	$tmpfile = basename($sem_browse_obj->create_result_xls());
	if($tmpfile){
		header('Location: ' . getDownloadLink( $tmpfile, _("Veranstaltungsübersicht.xls"), 4));
		page_close();
		die;
	}
}
ob_end_flush();
// Start of Output
$HELP_KEYWORD="Basis.Informationsseite";
$CURRENT_PAGE = ($level == "s" ? $SessSemName["header_line"]." - " : "").$head_text;
if (($SessSemName[1]) && ($SessSemName["class"] == "inst")) {
	Navigation::activateItem('/course/main/courses');
}

include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head

?>
<body>
<table width="100%" border=0 cellpadding=2 cellspacing=0>
<tr>
	<td class="blank" valign="top"><br />&nbsp;<font size="-1"><? echo $intro_text ?></font><br /><br />
<?
$sem_browse_obj->print_result();
?>
</td><td class="blank" width="270" align="right" valign="top">
<?
$goup_by_links = "";
for ($i = 0; $i < count($sem_browse_obj->group_by_fields); ++$i){
	if($group_by != $i){
		$group_by_links .= "<a href=\"".URLHelper::getLink("",array('group_by'=>$i))."\"><img src=\"".$GLOBALS['ASSETS_URL']."images/blank.gif\" width=\"10\" height=\"20\" border=\"0\">";
	} else {
		$group_by_links .= "<img src=\"".$GLOBALS['ASSETS_URL']."images/forumrot.gif\" border=\"0\" align=\"bottom\">";
	}
	$group_by_links .= "&nbsp;" . $sem_browse_obj->group_by_fields[$i]['name'];
	if($group_by != $i){
		$group_by_links .= "</a>";
	}
	$group_by_links .= "<br>";
}
$infobox[] = 	array(	"kategorie" => _("Anzeige gruppieren:"),
						"eintrag" => array(array(	"icon" => "blank.gif",
													"text" => $group_by_links))
				);
if (($EXPORT_ENABLE) AND ($level == "s") AND ($perm->have_perm("tutor")))
{
	include_once($PATH_EXPORT . "/export_linking_func.inc.php");
	$infobox[] = 	array(	"kategorie" => _("Daten ausgeben:"),
							"eintrag" => array(array(	"icon" => "blank.gif",
														"text" => export_link($SessSemName[1], "veranstaltung", $SessSemName[0])),
												array( 'icon' => 'blank.gif',
														"text" => '<a href="'.$PHP_SELF.'?send_excel=1&group_by='.(int)$group_by.'"><img src="'.$GLOBALS['ASSETS_URL'].'images/xls-icon.gif" align="absbottom" border="0">&nbsp;'._("Download als Excel Tabelle").'</a>')

														)
					);
}
if (($EXPORT_ENABLE) AND ($level == "sbb") AND ($perm->have_perm("tutor")))
{
	include_once($PATH_EXPORT . "/export_linking_func.inc.php");
	$infobox[] = 	array(	"kategorie" => _("Daten ausgeben:"),
							"eintrag" => array(array(	"icon" => "blank.gif",
														"text" => export_link($id, "veranstaltung", $id)),
												array( 'icon' => 'blank.gif',
														"text" => '<a href="'.$PHP_SELF.'?send_excel=1&group_by='.(int)$group_by.'"><img src="'.$GLOBALS['ASSETS_URL'].'images/xls-icon.gif" align="absbottom" border="0">&nbsp;'._("Download als Excel Tabelle").'</a>')

														)
					);
}
print_infobox ($infobox,"browse.jpg");
?>
</tr>
<tr>
	<td class="blank" colspan="2">&nbsp;
	</td>
</tr>
</table>

<?
include ('lib/include/html_end.inc.php');
page_close();
