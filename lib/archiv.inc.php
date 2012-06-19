<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*

archiv.inc.php - Funktionen zur Archivierung in Stud.IP
Copyright (C) 2000 Cornelis Kater <ckater@gwdg.de>, Ralf Stockmann <rstockm@gwdg.de>, Stefan Suchi <suchi@gmx.de>

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

require_once ('config.inc.php');
require_once ('lib/dates.inc.php');
require_once ('lib/datei.inc.php');
require_once ('lib/wiki.inc.php'); // getAllWikiPages for dump
require_once ('lib/visual.inc.php');
require_once 'lib/functions.php';
require_once ('lib/language.inc.php');
require_once ('lib/classes/DataFieldEntry.class.php');
require_once ('lib/classes/Modules.class.php');
require_once ('lib/classes/StudipLitList.class.php');
require_once ('lib/classes/SemesterData.class.php');
require_once ('lib/classes/StudipScmEntry.class.php');
require_once ('lib/classes/StudipDocumentTree.class.php');
require_once ('lib/user_visible.inc.php');
require_once ('forum.inc.php');

// Liefert den dump des Seminars
function dump_sem($sem_id, $print_view = false) {
    global $TERMIN_TYP, $SEM_TYPE, $SEM_CLASS,$_fullname_sql,$AUTO_INSERT_SEM;

    $dump = "";
    $db=new DB_Seminar;
    $db2=new DB_Seminar;
    $db3=new DB_Seminar;
    $Modules = new Modules;
    $Modules = $Modules->getLocalModules($sem_id);

    $db2->query ("SELECT * FROM seminare WHERE Seminar_id='$sem_id'");
    $db2->next_record();
    $sem_type = $db2->f('status');

    $sem = Seminar::getInstance($sem_id);

    $dump.="\n<table width=100% border=1 cellpadding=2 cellspacing=0>";
    $dump .= " <tr><td colspan=2 align=left class=\"topic\">";
    $dump .= "<H1 class=\"topic\">&nbsp;".htmlReady($db2->f('Name'),1,1)."</H1>";
    $dump.= "</td></tr>\n";

    //Grunddaten des Seminars, wie in den seminar_main

    if ($db2->f('Untertitel')!="")
        $dump.="<tr><td width=\"15%\"><b>" . _("Untertitel:") . " </b></td><td>".htmlReady($db2->f('Untertitel'),1,1)."</td></tr>\n";

    if ($data = $sem->getDatesExport())
        $dump.="<tr><td width=\"15%\"><b>" . _("Zeit:") . " </b></td><td>" . nl2br($data) . "</td></tr>\n";

    if (get_semester($sem_id))
        $dump.="<tr><td width=\"15%\"><b>" . _("Semester:") . " </b></td><td>".get_semester($sem_id)."</td></tr>\n";

    if (veranstaltung_beginn($sem_id, 'export'))
        $dump.="<tr><td width=\"15%\"><b>" . _("Erster Termin:") . " </b></td><td>".veranstaltung_beginn($sem_id, 'export')."</td></tr>\n";

    if (vorbesprechung($sem_id, 'export'))
        $dump.="<tr><td width=\"15%\"><b>" . _("Vorbesprechung:") . " </b></td><td>".htmlReady(vorbesprechung($sem_id, 'export'))."</td></tr>\n";

    if ($data = $sem->getDatesTemplate('dates/seminar_export_location'))
        $dump .= "<tr><td width=\"15%\"><b>" . _("Ort:") . " </b></td><td>" . nl2br($data) . "</td></tr>\n";

    //wer macht den Dozenten?
    $db->query ("SELECT seminar_user.user_id, " . $_fullname_sql['full'] . " AS fullname, username, status FROM seminar_user LEFT JOIN auth_user_md5 USING (user_id) LEFT JOIN user_info USING (user_id) WHERE seminar_user.Seminar_id = '$sem_id' AND status = 'dozent' ORDER BY position, Nachname");

    $dump.= "<tr><td width=\"15%\"><b>" . get_title_for_status("dozent", $db->affected_rows(), $sem_type) . " </b></td><td>";
    while ($db->next_record())
        $dump.= htmlReady($db->f("fullname")) ."<br>  ";
    $dump.="</td></tr>\n";

    //und wer ist Tutor?
    $db->query ("SELECT seminar_user.user_id, " . $_fullname_sql['full'] . " AS fullname, username, status FROM seminar_user LEFT JOIN auth_user_md5 USING (user_id) LEFT JOIN user_info USING (user_id) WHERE seminar_user.Seminar_id = '$sem_id' AND status = 'tutor' ORDER BY position, Nachname");
    if ($db->affected_rows())
        $dump.="<tr><td width=\"15%\"><b>" . get_title_for_status("tutor", $db->affected_rows(), $sem_type) . " </b></td><td>";
    while ($db->next_record())
        $dump.= htmlReady($db->f("fullname")) ."<br>";
    if ($db->affected_rows())
        $dump.="</td></tr>\n";

    if ($db2->f("status")!="")
        {
        $dump.="<tr><td width=\"15%\"><b>" . _("Typ der Veranstaltung:") . "&nbsp;</b></td><td align=left>";
        $dump.= $SEM_TYPE[$db2->f("status")]["name"]." " . _("in der Kategorie") . " <b>".$SEM_CLASS[$SEM_TYPE[$db2->f("status")]["class"]]["name"]."</b></td></tr>\n";
        }
    if ($db2->f("art")!="")
        {
        $dump.="<tr><td width=\"15%\"><b>" . _("Art der Veranstaltung:") . "&nbsp;</b></td><td align=left>";
        $dump .= htmlReady($db2->f("art"),1,1)."</td></tr>\n";
        }
    if ($db2->f("VeranstaltungsNummer"))
        {
        $dump .="<tr><td width=\"15%\">";
        $dump .="<b>" . _("Veranstaltungsnummer:") . "&nbsp;</b></td><td width=75% align=left>";
        $dump.= htmlReady($db2->f("VeranstaltungsNummer"))."</td></tr>\n";
        }
    if ($db2->f("ects")!="")
        {
        $dump .="<tr><td width=\"15%\">";
        $dump .="<b>" . _("ECTS-Punkte:") . "&nbsp;</b></td><td width=75% align=left>";
        $dump.= htmlReady($db2->f("ects"))."</td></tr>\n";
        }
    if ($db2->f("Beschreibung")!="")
        {
        $dump.="<tr><td width=\"15%\"><b>" . _("Beschreibung:") . "&nbsp;</b></td><td align=left>";
        $dump.= htmlReady($db2->f("Beschreibung"),1,1)."</td></tr>\n";
        }
    if ($db2->f("teilnehmer")!="")
        {
        $dump.="<tr><td width=\"15%\"><b>" . _("TeilnehmerInnen:") . "&nbsp;</b></td><td align=left>";
        $dump.= htmlReady($db2->f("teilnehmer"),1,1)."</td></tr>\n";
        }
    if ($db2->f("vorrausetzungen")!="")
        {
        $dump.="<tr><td width=\"15%\"><b>" . _("Voraussetzungen:") . "&nbsp;</b></td><td align=left>";
        $dump.= htmlReady($db2->f("vorrausetzungen"),1,1)."</td></tr>\n";
        }
    if ($db2->f("lernorga")!="")
        {
        $dump.="<tr><td width=\"15%\"><b>" . _("Lernorganisation:") . "&nbsp;</b></td><td align=left>";
        $dump.= htmlReady($db2->f("lernorga"),1,1)."</td></tr>\n";
        }
    if ($db2->f("leistungsnachweis")!="")
        {
        $dump.="<tr><td width=\"15%\"><b>" . _("Leistungsnachweis:") . "&nbsp;</b></td><td align=left>";
        $dump.= htmlReady($db2->f("leistungsnachweis"),1,1)."</td></tr>\n";
        }

    //add the free adminstrable datafields
    $localEntries = DataFieldEntry::getDataFieldEntries($sem_id);


    foreach ($localEntries as $entry) {
        if (trim($entry->getValue())) {
            $dump.="<tr><td width=\"15%\"><b>" . htmlReady($entry->getName()) . ":&nbsp;</b></td><td align=left>";
            $dump.= $entry->getDisplayValue()."</td></tr>\n";
        }
    }

    if ($db2->f("Sonstiges")!="")   {
        $dump.="<tr><td width=\"15%\"><b>" . _("Sonstiges:") . "&nbsp;</b></td><td align=left>";
        $dump.= htmlReady($db2->f("Sonstiges"),1,1)."</td></tr>\n";
        }

    // Fakultaeten...
    $db3->query("SELECT DISTINCT c.Name FROM seminar_inst a LEFT JOIN  Institute b USING(Institut_id) LEFT JOIN Institute c ON(c.Institut_id=b.fakultaets_id)  WHERE a.seminar_id = '$sem_id'");
    if ($db3->affected_rows() > 0) {
        $dump.= "<tr><td width=\"15%\"><b>" . _("Fakult&auml;t(en):") . "&nbsp;</b></td><td>";
        WHILE ($db3->next_record())
            $dump.= htmlReady($db3->f("Name"))."<br>";
        $dump.= "</td></tr>\n";
        }

    //Studienbereiche
    if ($SEM_CLASS[$SEM_TYPE[$db2->f("status")]["class"]]["bereiche"]) {
        $sem_path = get_sem_tree_path($sem_id);
        $dump .= "<tr><td width=\"15%\"><b>" . _("Studienbereich(e):") . "&nbsp;</b></td><td>";
        if (is_array($sem_path)){
            foreach ($sem_path as $sem_tree_id => $path_name) {
                $dump.= htmlReady($path_name)."<br>";
            }
        }
        $dump.= "</td></tr>\n";
    }



    $iid=$db2->f("Institut_id");
    $db3->query("SELECT Name, url FROM Institute WHERE Institut_id = '$iid'");
    $db3->next_record();
    $dump.="<tr><td width=\"15%\"><b>" . _("Heimat-Einrichtung:") . "&nbsp;</b></td><td>".htmlReady($db3->f("Name"))."</td></tr>\n";
    $db3->query("SELECT Name, url FROM seminar_inst LEFT JOIN Institute USING (institut_id) WHERE seminar_id = '$sem_id' AND Institute.institut_id != '$iid'");
    $cd=$db3->affected_rows();
    if ($db3->affected_rows() == 1)
        $dump.="<tr><td width=\"15%\"><b>" . _("Beteiligte Einrichtung:") . "&nbsp;</b></td><td>";
    else if ($db3->affected_rows() >= 2)
        $dump.="<tr><td width=\"15%\"><b>" . _("Beteiligte Einrichtungen:") . "&nbsp;</b></td><td>";

    while ($db3->next_record()) {
        $cd--;
        $dump.= htmlReady($db3->f("Name"));
        if ($cd >= 1) $dump.=",&nbsp;";
    }
    if ($db3->affected_rows())
        $dump.="</td></tr>\n";

    //Teilnehmeranzahl
    $dump.= "<tr><td width=\"15%\"><b>" . _("max. TeilnehmerInnenanzahl:") . "&nbsp;</b></td><td>".$db2->f("admission_turnout")."&nbsp;</td></tr>\n";

    //Statistikfunktionen

    $db3->query("SELECT count(*) as anzahl FROM seminar_user WHERE Seminar_id = '$sem_id'");
    $db3->next_record();
    $dump.= "<tr><td width=\"15%\"><b>" . _("Anzahl der angemeldeten TeilnehmerInnen:") . "&nbsp;</b></td><td>".$db3->f("anzahl")."</td></tr>\n";

    $db3->query("SELECT count(*) as anzahl FROM px_topics WHERE Seminar_id = '$sem_id'");
    $db3->next_record();
    $dump.= "<tr><td width=\"15%\"><b>" . _("Forenbeiträge:") . "&nbsp;</b></td><td>".$db3->f("anzahl")."</td></tr>\n";

    if ($Modules["documents"]) {
        //do not show hidden documents
        if (!$GLOBALS['perm']->have_studip_perm('tutor', $sem_id)){
             $folder_tree = TreeAbstract::GetInstance('StudipDocumentTree', array('range_id' => $sem_id,'entity_type' => 'sem'));
            $unreadable_folders = $folder_tree->getUnReadableFolders($GLOBALS['user']->id);
        } else {
            $unreadable_folders = array();
        }
        $db3->query("SELECT count(*) as anzahl FROM dokumente
                    WHERE seminar_id = '$sem_id'" . (count($unreadable_folders) ? " AND range_id NOT IN('".join("','", $unreadable_folders)."')" : ""));
        $db3->next_record();
        $docs=$db3->f("anzahl");
    }
    $dump.= "<tr><td width=\"15%\"><b>" . _("Dokumente:") . "&nbsp;</b></td><td>".(int)$docs."</td></tr>\n";

    $dump.= "</table>\n";

    // Ablaufplan
    if ($Modules["schedule"]) {
        $dump.= dumpRegularDatesSchedule($sem_id);
        $dump.= dumpExtraDatesSchedule($sem_id);
    }

    //SCM
    if ($Modules["scm"]) {
        foreach(StudipScmEntry::GetSCMEntriesForRange($sem_id) as $scm){
            if(!empty($scm['content'])) {
                $dump .= "<br>";
                $dump .= "<table width=100% border=1 cellpadding=2 cellspacing=0>";
                $dump .= " <tr><td align=left class=\"topic\">";
                $dump .= "<H2 class=\"topic\">&nbsp;" . htmlReady($scm['tab_name']) . "</H2>";
                $dump .= "</td></tr>\n";
                $dump .= "<tr><td align=\"left\" width=\"100%\"><br>". formatReady($scm['content'],1,1) ."<br></td></tr>\n";
                $dump .= "</table>\n";
            }
        }
    }

    if ($Modules['literature']){
        $lit = StudipLitList::GetFormattedListsByRange($sem_id, false, false);
        if ($lit){
            $dump.="<br>";
            $dump.="<table width=100% border=1 cellpadding=2 cellspacing=0>";
            $dump .= " <tr><td align=left class=\"topic\">";
            $dump .= "<H2 class=\"topic\">&nbsp;" . _("Literaturlisten") . "</H2>";
            $dump.= "</td></tr>\n";
            $dump.="<tr><td align=\"left\" width=\"100%\"><br>". $lit ."<br></td></tr>\n";
            $dump .= "</table>\n";
        }
    }

    // Dateien anzeigen
    if ($Modules["documents"]) {
        //do not show hidden documents
        $unreadable_folders = array();
        if($print_view){
            if($Modules['documents_folder_permissions'] || StudipDocumentTree::ExistsGroupFolders($sem_id)){
                if (!$GLOBALS['perm']->have_studip_perm('tutor', $sem_id)){
                    $folder_tree = TreeAbstract::GetInstance('StudipDocumentTree', array('range_id' => $sem_id,'entity_type' => 'sem'));
                    $unreadable_folders = $folder_tree->getUnReadableFolders($GLOBALS['user']->id);
                }
            }
        }
        $i=0;
        $db->query("SELECT dokument_id, dokumente.description, dokumente.name ,
                filename, dokumente.mkdate, filesize, dokumente.user_id, username, Nachname, dokumente.url
                FROM dokumente LEFT JOIN auth_user_md5 ON auth_user_md5.user_id = dokumente.user_id
                WHERE seminar_id = '$sem_id'" . (count($unreadable_folders) ? " AND range_id NOT IN('".join("','", $unreadable_folders)."')" : ""));
        while($db->next_record()){
            if ($db->f("url")!="")
                $linktxt = _("Hinweis: Diese Datei wurde nicht archiviert, da sie lediglich verlinkt wurde.");
            else
                $linktxt = "";
            $dbresult[$i]=array("mkdate"=>$db->f("mkdate"), "dokument_id"=>$db->f("dokument_id"), "description"=>$linktxt.$db->f("description"),"name" => $db->f("name"), "filename"=>$db->f("filename"), "filesize"=>$db->f("filesize"),"user_id"=> $db->f("user_id"), "username"=>$db->f("username"), "nachname"=>$db->f("Nachname"));
            $i++;
        }

        if (!sizeof($dbresult)==0) {
            $dump.="<br>";
            $dump.="<table width=100% border=1 cellpadding=2 cellspacing=0>";
            $dump .= " <tr><td align=left colspan=3 class=\"topic\">";
            $dump .= "<H2 class=\"topic\">&nbsp;" . _("Dateien:") . "</H2>";
            $dump.= "</td></tr>\n";

            rsort ($dbresult);

            for ($i=0; $i<sizeof($dbresult); $i++) {
                $doc_id = $dbresult[$i]["dokument_id"];
                $sizetmp = $dbresult[$i]["filesize"];
                $sizetmp = ROUND($sizetmp / 1024);
                $size = "(".$sizetmp." KB)";
                $name = ($dbresult[$i]['name'] && $dbresult[$i]['name'] != $dbresult[$i]['filename'] ? $dbresult[$i]['name'] . ' ('.$dbresult[$i]['filename'].')' : $dbresult[$i]["filename"]);
                $dump.="<tr><td width='100%'><b>".htmlReady($name)."</b><br>".htmlReady($dbresult[$i]["description"])."&nbsp;".$size."</td><td>".
                    htmlReady($dbresult[$i]["nachname"]) . "&nbsp;</td><td>&nbsp;".date("d.m.Y", $dbresult[$i]["mkdate"])."</td></tr>\n";
            }
            $dump.="</table>\n";
        }
    }

    // Teilnehmer
    if ($Modules["participants"]) {
        if ($AUTO_INSERT_SEM_PARTICIPANTS_VIEW_PERM || !in_array($sem_id, AutoInsert::getAllSeminars(true))) {
            $gruppe = array("dozent", "tutor", "autor", "user");
            $dump.="<br>";
            foreach ($gruppe as $key) {

            // die eigentliche Teil-Tabelle

                $sortby = "Nachname, Vorname ASC";
                $db->query ("SELECT seminar_user.user_id, " . $_fullname_sql['full'] . " AS fullname, username, status, count(topic_id) AS doll,
                            " . get_ext_vis_query('seminar_user') . " as user_is_visible
                            FROM seminar_user LEFT JOIN px_topics USING (user_id,Seminar_id)
                            LEFT JOIN auth_user_md5 ON (seminar_user.user_id=auth_user_md5.user_id)
                            LEFT JOIN user_info ON (auth_user_md5.user_id=user_info.user_id)
                            WHERE seminar_user.Seminar_id = '$sem_id' AND status = '$key'  GROUP by seminar_user.user_id ORDER BY $sortby");

                if ($db->affected_rows() != 0) {//haben wir in der Personengattung ueberhaupt einen Eintrag?
                    $dump.="<table width=100% border=1 cellpadding=2 cellspacing=0>";
                    $dump .= " <tr><td align=left colspan=4 class=\"topic\">";
                    $dump .= "<H2 class=\"topic\">&nbsp;".get_title_for_status($key, $db->affected_rows(), $sem_type)."</H2>";
                    $dump.= "</td></tr>\n";
                    $dump.="<th width=\"30%%\">" . _("Name") . "</th>";
                    $dump.="<th width=\"10%%\">" . _("Forenbeiträge") . "</th>";
                    $dump.="<th width=10%><b>" . _("Dokumente") . "</b></th></tr>\n";

                    while ($db->next_record()) {
                        $dump.="<tr><td>";
                        $dump.= ($db->f('user_is_visible') ? htmlReady($db->f("fullname")) : _("(unsichtbareR NutzerIn)"));
                        $dump.="</td><td align=center>";
                        $dump.= $db->f("doll");
                        $dump.="</td><td align=center>";

                        $Dokumente = 0;
                        $UID = $db->f("user_id");
                        $db2->query ("SELECT count(dokument_id) AS doll FROM dokumente WHERE dokumente.Seminar_id = '$sem_id' AND dokumente.user_id = '$UID'");
                        while ($db2->next_record()) {
                            $Dokumente += $db2->f("doll");
                        }
                        $dump.= $Dokumente;
                        $dump.="</td>";
                        $dump.="</tr>\n";

                    } // eine Zeile zuende

                    $dump.= "</table>\n";
                }
            } // eine Gruppe zuende
        }
    }

    return $dump;

} // end function dump_sem($sem_id)


/**
 * Returns the regular dates for one seminar.
 * @param  $sem_id the id of the seminar
 * @return the HTML for the schedule table
 */
function dumpRegularDatesSchedule($sem_id)
{
    $db=new DB_Seminar;

    $db->query("SELECT termine.*, themen.title as th_title, themen.description as th_desc FROM termine LEFT JOIN themen_termine USING (termin_id) LEFT JOIN themen USING (issue_id) WHERE range_id='$sem_id' AND date_typ IN " . getPresenceTypeClause() . " ORDER BY date");
    $title = _("Ablaufplan");

    return dumpScheduleTable($db, $title);
}

/**
 * Returns the extra dates for one seminar
 * @param  $sem_id the id of the seminar
 * @return the HTML for the schedule table for the extra dates
 */
function dumpExtraDatesSchedule($sem_id)
{
    $db = new DB_Seminar;

    $db->query("SELECT termine.*, themen.title as th_title, themen.description as th_desc FROM termine LEFT JOIN themen_termine USING (termin_id) LEFT JOIN themen USING (issue_id) WHERE range_id='$sem_id'  AND date_typ NOT IN " . getPresenceTypeClause() . " ORDER BY date");
    $title = _("zus&auml;tzliche Termine");

    return dumpScheduleTable($db, $title);
}

/**
 * Returns the schedule table for one query as HTML.
 * The query has to start like this:
 * SELECT termine.*, themen.title as th_title, themen.description as th_desc FROM termine LEFT JOIN themen_termine USING (termin_id) LEFT JOIN themen USING (issue_id)
 * @param  $db the result of an query for date entries
 * @param  $title the title for the table header
 * @return the HTML for the schedule table
 */
function dumpScheduleTable($db, $title)
{
    if ($db->num_rows()) {
        $dump .= "<br>";
        $dump .= "<table width=100% border=1 cellpadding=2 cellspacing=0>";
        $dump .= dumpDateTableHeader($title);
        $dump .= dumpDateTableRows($db);
        $dump .= "</table>\n";
    }

    return $dump;
}

/**
 * Returns the first row (the header row) for the tables listing dates.
 * @param  $title title to show in first table row
 * @return the HTML for the first table row
 */
function dumpDateTableHeader($title)
{
    $dump .= "<tr><td colspan=2 align=left class=\"topic\">";
    $dump .= "<H2 class=\"topic\">&nbsp;" . $title . "</H2>";
    $dump .= "</td></tr>\n";

    return $dump;
}

/**
 * Returns the HTML table rows for the date entries in $db.
 * The query has to start like this:
 * SELECT termine.*, themen.title as th_title, themen.description as th_desc FROM termine LEFT JOIN themen_termine USING (termin_id) LEFT JOIN themen USING (issue_id)
 * @param  $db the result of an query for date entries
 * @return the HTML for the table rows
 */
function dumpDateTableRows($db)
{
    global $TERMIN_TYP;

    $lastTerminId = NULL;
    while ($db->next_record()) {
        $currentTerminId = $db->f("termin_id");
        if ($lastTerminId != $currentTerminId) {
            $dump .= "<tr align=\"center\"> ";
            $dump .= "<td width=\"25%\" align=\"left\" valign=\"top\">";
            $dump .= strftime("%d. %b. %Y, %H:%M", $db->f("date"));
            $dump .= ' - ' . strftime("%H:%M", $db->f("end_time"));
            $dump .= "&nbsp;(" . $TERMIN_TYP[$db->f("date_typ")]["name"] . ")";
            $dump .= "</td>";
        }
        else {
            $dump .= "<tr><td width=\"25%\"></td>";
        }

        $dump .= "<td width=\"75%\" align=\"left\"> ";
        $dump .= htmlReady($db->f("th_title"), 1, 1);
        if ($db->f("th_desc")) {
            $dump .= "<br/>";
            $dump .= formatReady($db->f("th_desc"), 1, 1);
        }
        $dump .= "&nbsp;</td></tr>\n";

        $lastTerminId = $currentTerminId;
    }

    return $dump;
}


/////// die beiden Funktionen um das Forum zu exportieren

function Export_Kids ($topic_id=0, $level=0) {
// stellt im Treeview alle Postings dar, die NICHT Thema sind

    if (!isset($anfang))
        $anfang = $topic_id;
    $query = "select topic_id, name, author "
        .", mkdate, chdate, description, root_id, username, anonymous from px_topics LEFT JOIN auth_user_md5 USING(user_id) where "
        ." parent_id = '$topic_id'"
        ." order by mkdate";
    $db=new DB_Seminar;
    $db->query($query);
    $lines[$level] = $db->num_rows();
    while ($db->next_record()) {
        $r_topic_id = $db->f("topic_id");
        $r_name = $db->f("name");
        $r_author = (get_config('FORUM_ANONYMOUS_POSTINGS') && $db->f("anonymous")) ? _("anonym") : $db->f("author");
        $r_mkdate = $db->f("mkdate");
        $r_chdate = $db->f("chdate");
        $r_description = forum_parse_edit($db->f("description"), get_config('FORUM_ANONYMOUS_POSTINGS') ? $db->f("anonymous") : false);
        $root_id = $db->f("root_id");
        $username = $db->f("username");

        if ($r_topic_id != $topic_id) {
            $r_name = htmlReady($r_name);
            $zusatz = htmlReady($r_author)." " . _("am") . " ";
            $zusatz .= date("d.m.Y - H:i", $r_mkdate);
            $r_description = formatReady($r_description);
            $forum_dumbkid.="<tr><td class=blank><hr><b>".$r_name."</b> " . _("von") . " ".$zusatz."</td></tr><tr><td class=blank>".$r_description."</td></tr>\n";
        }
        $forum_dumbkid.=Export_Kids($r_topic_id, $level+1);
    }
    return $forum_dumbkid;
}

function Export_Topic ($sem_id) {
    global $SessionSeminar,$SessSemName;

    $datum=0;
    $topic_id=0;
    $fields = array("topic_id", "parent_id", "root_id", "name"
        , "description", "author", "author_host", "mkdate"
        , "chdate", "user_id", "anonymous");
    $query = "select distinct ";
    $comma = "";
    WHILE (list($key,$val)=each($fields)) {
        $query .= $comma."t.".$val;
        $comma = ", ";
        }
    $topicneu = $datum;
    $query .= ", count(*) as count, max(s.chdate) as last from px_topics t LEFT JOIN px_topics s USING(root_id) where t.topic_id = t.root_id AND t.Seminar_id = '$sem_id' group by t.root_id  order by t.mkdate";
    $db=new DB_Seminar;
    $db->query($query);
    IF ($db->num_rows()==0) {  // Das Forum ist leer
        $text = _("Das Forum ist leer");
        $forum_dumb="<table width=\"100%\" border=0 cellpadding=0 cellspacing=0>".$text."</table>";
        }
    ELSE {
        while ($db->next_record()) {
            $r_topic_id = $db->f("topic_id");
            $parent_id = $db->f("parent_id");
            $root_id = $db->f("root_id");
            $name = $db->f("name");
            $description = forum_parse_edit($db->f("description"), $db->f("anonymous"));
            $author = (get_config('FORUM_ANONYMOUS_POSTINGS') && $db->f("anonymous")) ? _("anonym") : $db->f("author");
            $author_host = $db->f("author_host");
            $mkdate = $db->f("mkdate");
            $chdate = $db->f("chdate");
            $user_id = $db->f("user_id");
            $count = $db->f("count");
            $last = $db->f("last");
            $count -=1;
            $zusatz = "<b>".$count."</b> / ".date("d.m.Y - H:i", $last);
            $zusatz = htmlReady($author)."&nbsp;/&nbsp; ".$zusatz;
            $name = htmlReady($name);
            $description = formatReady($description);
            $forum_dumb.="<table class=blank width=\"100%\" border=0 cellpadding=5 cellspacing=0><tr><td><h3>".$name."</h3> " . _("von") . " ".$zusatz."</td></tr><tr><td class=blank>".$description. "</td></tr>";
            $forum_dumb.=Export_Kids($r_topic_id, $level);
            $forum_dumb.="</table><br><br>";
            $neuer_beitrag = FALSE;
        }
    }
    return $forum_dumb;
}



//Funktion zum archivieren eines Seminars, sollte in der Regel vor dem Loeschen ausgfuehrt werden.
function in_archiv ($sem_id) {

    global $SEM_CLASS,$SEM_TYPE, $ARCHIV_PATH, $TMP_PATH, $ZIP_PATH, $ZIP_OPTIONS, $_fullname_sql;

    $hash_secret="frauen";

    $db=new DB_Seminar;
    $db2=new DB_Seminar;
    $semester = new SemesterData;


    //Besorgen der Grunddaten des Seminars

    $db->query("SELECT * FROM seminare WHERE Seminar_id = '$sem_id'");

    $db->next_record();
    $seminar_id = $db->f("Seminar_id");
    $name = $db->f("Name");
    $untertitel = $db->f("Untertitel");
    $beschreibung = $db->f("Beschreibung");
    $start_time = $db->f("start_time");
    $heimat_inst_id = $db->f("Institut_id");

    //Besorgen von einzelnen Daten zu dem Seminar

    $all_semester = $semester->getAllSemesterData();
    for ($i=0; $i<sizeof($all_semester); $i++)
        {
        if (($start_time >= $all_semester[$i]["beginn"]) && ($start_time <= $all_semester[$i]["ende"])) $semester_tmp=$all_semester[$i]["name"];
        }

    //Studienbereiche
    if ($SEM_CLASS[$SEM_TYPE[$db->f("status")]["class"]]["bereiche"]) {
        $sem_path = get_sem_tree_path($seminar_id);
        if (is_array($sem_path)){
            $studienbereiche = join(", ",array_values($sem_path));
        }
    }

    // das Heimatinstitut als erstes
    $db2->query("SELECT Name FROM Institute WHERE Institut_id = '$heimat_inst_id'");
    $db2->next_record();
    $institute = $db2->f("Name");

    // jetzt den Rest
    $db2->query("SELECT Name FROM Institute LEFT JOIN seminar_inst USING (institut_id) WHERE seminar_id = '$seminar_id' AND Institute.Institut_id != '$heimat_inst_id'");
    while ($db2->next_record())
        {
        $institute=$institute.", ".$db2->f("Name");
        }

    $db2->query("SELECT " . $_fullname_sql['full'] . " AS fullname FROM seminar_user  LEFT JOIN auth_user_md5 USING (user_id) LEFT JOIN user_info USING (user_id) WHERE seminar_id = '$seminar_id' AND seminar_user.status='dozent'");
    $db2->next_record();
    $dozenten=$db2->f("fullname");
    while ($db2->next_record())
        {
        $dozenten=$dozenten.", ".$db2->f("fullname");
        }

    $db2->query("SELECT fakultaets_id FROM seminare LEFT JOIN Institute USING (Institut_id) WHERE seminare.Seminar_id = '$seminar_id'");
    $db2->next_record();
    $fakultaet_id=$db2->f("fakultaets_id");

    $db2->query("SELECT DISTINCT c.Name FROM seminar_inst a LEFT JOIN  Institute b USING(Institut_id) LEFT JOIN Institute c ON(c.Institut_id=b.fakultaets_id)  WHERE a.seminar_id = '$seminar_id'");
    $db2->next_record();
    $fakultaet=$db2->f("Name");
    while ($db2->next_record())
        {
        $fakultaet=$fakultaet." | ".$db2->f("Name");
        }

    // Schreiben Datenbank -> Datenbank

    $name = addslashes($name);
    $untertitel = addslashes($untertitel);
    $beschreibung = addslashes($beschreibung);
    $institute = addslashes($institute);
    $studienbereiche = addslashes($studienbereiche);
    $dozenten = addslashes($dozenten);
    $fakultaet = addslashes($fakultaet);

    setTempLanguage();  // use $DEFAULT_LANGUAGE for archiv-dumps
    
    //Dump holen

    $dump = addslashes(dump_sem($sem_id));

    //Forumdump holen

    $forumdump = addslashes(export_topic($sem_id));

    // Wikidump holen
    $wikidump=addslashes(getAllWikiPages($sem_id, $name, FALSE));

    restoreLanguage();
    
    //OK, naechster Schritt: Kopieren der Personendaten aus seminar_user in archiv_user

    $db->query("SELECT * FROM seminar_user WHERE Seminar_id = '$seminar_id'");
    while ($db->next_record())
        {
        $seminar_id=$db->f("Seminar_id");
        $user_id=$db->f("user_id");
        $status=$db->f("status");
        $db2->query("INSERT INTO archiv_user SET seminar_id='$seminar_id', user_id='$user_id', status='$status' ");
        }

    // Eventuelle Vertretungen in der Veranstaltung haben weiterhin Zugriff mit Dozentenrechten
    if (get_config('DEPUTIES_ENABLE')) {
        $deputies = getDeputies($seminar_id);
        // Eintragen ins Archiv mit Zugriffsberechtigung "dozent"
        $query = DBManager::get()->prepare("INSERT INTO archiv_user SET seminar_id=?, user_id=?, status='dozent'");
        foreach ($deputies as $deputy) {
            $query->execute(array($seminar_id, $deputy['user_id']));
        }
    }

    //OK, letzter Schritt: ZIPpen der Dateien des Seminars und Verschieben in eigenes Verzeichnis

    $query = sprintf ("SELECT count(dokument_id) FROM dokumente WHERE seminar_id = '%s' AND url = ''", $seminar_id);
    $db->query ($query);
    $db->next_record();
    if ($db->f(0)) {
        $archiv_file_id = md5(uniqid($hash_secret,1));
        $docs = 0;

        //temporaeres Verzeichnis anlegen
        $tmp_full_path = "$TMP_PATH/$archiv_file_id";
        mkdir($tmp_full_path, 0700);

        $folder_tree =& TreeAbstract::GetInstance('StudipDocumentTree', array('range_id' => $seminar_id));
        if($folder_tree->getNumKids('root')){
            $list = $folder_tree->getKids('root');
        }
        if(is_array($list)){
        //copy documents in the temporary folder-system
        $query = sprintf ("SELECT folder_id, name FROM folder WHERE range_id IN ('%s') ORDER BY name", join("','", $list));
        $db->query ($query);
        $folder = 0;
        while ($db->next_record()) {
            $folder++;
            $temp_folder = $tmp_full_path."/[$folder]_" . prepareFilename($db->f("name"), FALSE);
            mkdir($temp_folder, 0700);
            createTempFolder($db->f("folder_id"), $temp_folder, FALSE);
        }

        //zip all the stuff
        $archiv_full_path = "$ARCHIV_PATH/$archiv_file_id";
        create_zip_from_directory($tmp_full_path, $tmp_full_path);
        @rename($tmp_full_path . '.zip', $archiv_full_path);
        }
        rmdirr($tmp_full_path);
    } else
        $archiv_file_id = "";

    //Reinschreiben von diversem Klumpatsch in die Datenbank
    $db->query("INSERT INTO archiv (seminar_id,name,untertitel,beschreibung,start_time,semester,heimat_inst_id,
                institute,dozenten,fakultaet,dump,archiv_file_id,mkdate,forumdump,wikidump,studienbereiche) VALUES
                ('$seminar_id', '$name', '$untertitel', '$beschreibung', '$start_time', '$semester_tmp', '$heimat_inst_id',
                '$institute', '$dozenten', '$fakultaet', '$dump', '$archiv_file_id', '".time()."','$forumdump','$wikidump',
                '$studienbereiche')");
}


?>
