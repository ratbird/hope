<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
* Export-Subfile that exports data.
*
* This file contains functions to get data from the Stud.IP-db and write it into a file.
*
* @author       Arne Schroeder <schroeder@data.quest.de>
* @access       public
* @modulegroup      export_modules
* @module       export_studipdata_functions
* @package      Export
*/
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// export_studipdata_func.inc.php
// exportfunctions for the Stud.IP database
//
// Copyright (c) 2002 Arne Schroeder <schroeder@data-quest.de>
// Suchi & Berg GmbH <info@data-quest.de>
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

require_once("lib/classes/SemesterData.class.php");
require_once("lib/classes/DataFieldEntry.class.php");
require_once("lib/statusgruppe.inc.php");

/**
* Converts special charakters into unicode format.
*
* This function converts special charakters in the given sring into unicode format UTF-8.
*
* @access   public
* @deprecated
* @param        string  $xml_string string to be converted
* @return       string  converted string
*/
function string_to_unicode ($xml_string)
{
    for ($x=0; $x<strlen($xml_string); $x++)
    {
        $char = substr($xml_string, $x, 1);
        $dosc = ord($char);
        if($dosc < 32 && $dosc != 10) continue;
        $ret .= ($dosc > 127) ? "&#".$dosc.";" : $char;
    }
    return $ret;
}

/**
* Writes the xml-stream into a file or to the screen.
*
* This function writes the xml-stream $object_data into a file or to the screen,
* depending on the content of $output_mode.
*
* @access   public
* @param        string  $object_data    xml-stream
* @param        string  $output_mode    switch for output target
*/
function output_data($object_data, $output_mode = "file", $flush = false)
{
    global $xml_file;
    static $fp;
    if (is_null($fp)) {
        $fp = fopen('php://temp', 'r+');
    }

    fwrite($fp, $object_data);

    if ($flush && is_resource($fp)) {
        rewind($fp);
        if (in_array($output_mode, words('file processor passthrough choose'))) {
            stream_copy_to_stream($fp, $xml_file);
        } elseif ($output_mode == "direct") {
            $out = fopen('php://output', 'w');
            stream_copy_to_stream($fp, $out);
            fclose($out);
        }
        fclose($fp);
    }
}

/**
* Exports data of the given range.
*
* This function calls the functions that export the data sepcified by the given $export_range.
* It calls the function output_data afterwards.
*
* @access   public
* @param        string  $range_id   Stud.IP-range_id for export
*/
function export_range($range_id)
{
    global $db, $o_mode, $range_name,$ex_person_details,$persons, $ex_sem;

    $db=new DB_Seminar;

//    Ist die Range-ID eine Einrichtungs-ID?
    $db->query("SELECT * FROM Institute WHERE Institut_id = '" . $range_id . "'");
    if (($db->next_record()) And ($db->f("Name") != ""))
    {
        $range_name = $db->f("Name");
        output_data ( xml_header(), $o_mode);
        $output_startet = true;
        export_inst( $range_id );

    }

//  Ist die Range-ID eine Fakultaets-ID? Dann auch untergeordnete Institute exportieren!
    $db->query("SELECT * FROM Institute WHERE fakultaets_id = '" . $range_id . "' ");
    while ($db->next_record())
        if (($db->f("Name") != "") And ($db->f("Institut_id") != $range_id))
        {
//          output_data ( xml_header(), $o_mode);
            export_inst( $db->f("Institut_id") );
        }

//    Ist die Range-ID eine Seminar-ID?
    $db->query("SELECT * FROM seminare WHERE Seminar_id = '" . $range_id . "'");
    if (($db->next_record()) And ($db->f("Name") != ""))
    {
        $range_name = $db->f("Name");
        if (!$output_startet)
            output_data ( xml_header(), $o_mode);
        $output_startet = true;
        export_inst( $db->f("Institut_id"), $db->f("Seminar_id") );
    }


//    Ist die Range-ID ein Range-Tree-Item?
    if($range_id != 'root'){
    $tree_object = new RangeTreeObject($range_id);
    $range_name = $tree_object->item_data["name"];
//    Tree-Item ist ein Institut:
    if ($tree_object->item_data['studip_object'] == 'inst')
    {
        if (!$output_startet)
            output_data ( xml_header(), $o_mode);
        $output_startet = true;
        export_inst( $tree_object->item_data['studip_object_id'] );
    }
//    Tree-Item hat Institute als Kinder:
    $inst_array = $tree_object->GetInstKids();

    if (sizeof($inst_array) > 0)
    {
        if (!$output_startet)
            output_data ( xml_header(), $o_mode);
        $output_startet = true;
        while (list($key, $inst_ids) = each($inst_array))
        {
            export_inst($inst_ids);
        }
    }
    }

    $db->query("SELECT sem_tree_id FROM sem_tree WHERE sem_tree_id = '$range_id' ");
    if ($db->next_record() || $range_id=='root'){
        if (!$output_startet)  output_data(xml_header(), $o_mode);
        $output_startet = true;
        if (isset($ex_sem) && $semester = Semester::find($ex_sem)) {
            $args = array('sem_number' => array(SemesterData::GetSemesterIndexById($ex_sem)));
        } else {
            $args = array();
        }
        if ($range_id != 'root') {
            $the_tree = TreeAbstract::GetInstance('StudipSemTree', $args);
            $sem_ids = array_unique($the_tree->getSemIds($range_id, true));
        }
        if (is_array($sem_ids) || $range_id == 'root') {
            if (is_array($sem_ids)) {
                $to_export = DbManager::get()
                        ->query("SELECT DISTINCT Institut_id FROM seminare
                                  WHERE Seminar_id IN('".join("','", $sem_ids)."')")
                        ->fetchAll(PDO::FETCH_COLUMN);
            } else {
                $sem_ids = 'root';
                $to_export = DbManager::get()
                        ->query("SELECT DISTINCT Institut_id FROM seminare INNER JOIN seminar_sem_tree USING(seminar_id)
                                " . ($semester ?
                                   "WHERE seminare.start_time <=".$semester->beginn." AND (".$semester->beginn." <= (seminare.start_time + seminare.duration_time) OR seminare.duration_time = -1)"
                                    : ""))->fetchAll(PDO::FETCH_COLUMN);
            }

            foreach($to_export as $inst) {
                export_inst($inst, $sem_ids);
            }
        }
    }

    if ($ex_person_details && is_array($persons)){
        export_persons(array_keys($persons));
    }
    output_data ( xml_footer(), $o_mode, $flush = true);
}


/**
* Exports a Stud.IP-institute.
*
* This function gets the data of an institute and writes it into $data_object.
* It calls one of the functions export_sem, export_pers or export_teilis and then output_data.
*
* @access   public
* @param        string  $inst_id    Stud.IP-inst_id for export
* @param        string  $ex_sem_id  allows to choose if only a specific lecture is to be exported
*/
function export_inst($inst_id, $ex_sem_id = "all")
{
    global $db, $ex_type, $o_mode, $xml_file, $xml_names_inst, $xml_groupnames_inst, $INST_TYPE;

    $db=new DB_Seminar;

    $db->query("SELECT * FROM Institute WHERE Institut_id = '" . $inst_id . "'");
    $db->next_record();
    $data_object .= xml_open_tag($xml_groupnames_inst["object"], $db->f("Institut_id"));
    while ( list($key, $val) = each($xml_names_inst))
    {
        if ($val == "") $val = $key;
        if (($key == "type") AND ($INST_TYPE[$db->f($key)]["name"] != ""))
            $data_object .= xml_tag($val, $INST_TYPE[$db->f($key)]["name"]);
        elseif ($db->f($key) != "")
            $data_object .= xml_tag($val, $db->f($key));
    }
    reset($xml_names_inst);
    $db->query("SELECT Name, Institut_id, type FROM Institute WHERE Institut_id = '" . $db->f('fakultaets_id') . "' AND fakultaets_id = '" . $db->f('fakultaets_id') . "'");
    $db->next_record();
    {
        if ($db->f("Name") != "")
            $data_object .= xml_tag($xml_groupnames_inst["childobject"], $db->f("Name"), array('key' => $db->f('Institut_id')));
    }
    // freie Datenfelder ausgeben
    $data_object .= export_datafields($inst_id, $xml_groupnames_inst["childgroup2"], $xml_groupnames_inst["childobject2"], 'inst', $db->f("type"));
    output_data( $data_object, $o_mode );
    $data_object = "";

    switch ($ex_type)
    {
    case "veranstaltung":
        export_sem($inst_id, $ex_sem_id);
        break;
    case "person":
        if ($ex_sem_id == "all")
            export_pers($inst_id);
        elseif ($GLOBALS['perm']->have_studip_perm('tutor', $ex_sem_id))
            export_teilis($inst_id, $ex_sem_id);
        else
            $data_object .= xml_tag("message", _("KEINE BERECHTIGUNG!"));
        break;
    default:
        echo "</td></tr>";
        my_error(_("Der gewählte Exportmodus wird nicht unterstützt."));
        echo "</table></td></tr></table>";
        die();
    }

    $data_object .= xml_close_tag($xml_groupnames_inst["object"]);

    output_data($data_object, $o_mode);
    $data_object = "";
}

/**
* Exports lecture-data.
*
* This function gets the data of the lectures at an institute and writes it into $data_object.
* It calls output_data afterwards.
*
* @access   public
* @param        string  $inst_id    Stud.IP-inst_id for export
* @param        string  $ex_sem_id  allows to choose if only a specific lecture is to be exported
*/
function export_sem($inst_id, $ex_sem_id = "all")
{
    global $db, $db2, $range_id, $xml_file, $o_mode, $xml_names_lecture, $xml_groupnames_lecture, $object_counter, $SEM_TYPE, $SEM_CLASS, $filter, $ex_sem, $ex_sem_class,$ex_person_details,$persons;

    $ex_only_homeinst = (int)$_REQUEST['ex_only_homeinst'];

    $db=new DB_Seminar;
    $db2=new DB_Seminar;
    $db3=new DB_Seminar;

    switch ($filter)
    {
        case "seminar":
            $order = " seminare.Name";
        break;
        case "status":
            $order = "seminare.status, seminare.Name";
            $group = "FIRSTGROUP";
            $group_tab_zelle = "status";
            $do_group = true;
        break;
        default:
            $order = "seminare.status, seminare.Name";
            $group = "FIRSTGROUP";
            $group_tab_zelle = "status";
            $do_group = true;
    }

    if (isset($ex_sem) && $semester = Semester::find($ex_sem)){
        $addquery = " AND seminare.start_time <=".$semester->beginn." AND (".$semester->beginn." <= (seminare.start_time + seminare.duration_time) OR seminare.duration_time = -1) ";
    }

    if ($ex_sem_id != "all"){
        if ($ex_sem_id == 'root') {
            $addquery .= " AND EXISTS(SELECT * FROM seminar_sem_tree WHERE seminar_sem_tree.seminar_id=seminare.Seminar_id) ";
        } else {
            if (!is_array($ex_sem_id)) $ex_sem_id = array($ex_sem_id);
            $ex_sem_id = array_flip($ex_sem_id);
        }
    }

    if (!$GLOBALS['perm']->have_perm('root') && !$GLOBALS['perm']->have_studip_perm('admin', $inst_id)) $addquery .= " AND visible=1 ";

    if(count($ex_sem_class)){
        $allowed_sem_types = array();
        foreach(array_keys($ex_sem_class) as $semclassid){
            $allowed_sem_types += array_keys(SeminarCategories::get($semclassid)->getTypes());
        }
        $addquery .= sprintf(" AND seminare.status IN(%s) ", join(",", $allowed_sem_types));
    } else {
        $addquery .= " AND seminare.status NOT IN (" . join(",", studygroup_sem_types()) . ")";
    }

    if($ex_only_homeinst){
        $db->query("SELECT seminare.*,Seminar_id as seminar_id, Institute.Name as heimateinrichtung FROM seminare
                LEFT JOIN Institute ON seminare.Institut_id=Institute.Institut_id
                WHERE seminare.Institut_id = '" . $inst_id . "' " . $addquery . "
                ORDER BY " . $order);
    } else {
        $db->query("SELECT seminare.*,seminar_inst.seminar_id, Institute.Name as heimateinrichtung FROM seminar_inst
                LEFT JOIN seminare USING (Seminar_id)
                LEFT JOIN Institute ON seminare.Institut_id=Institute.Institut_id
                WHERE seminar_inst.Institut_id = '" . $inst_id . "' " . $addquery . "
                ORDER BY " . $order);
    }

    $data_object .= xml_open_tag( $xml_groupnames_lecture["group"] );

    while ($db->next_record())
        {
            if (is_array($ex_sem_id) && !isset($ex_sem_id[$db->f("seminar_id")])) continue;
            $group_string = "";
            if (($do_group) AND ($group != $db->f($group_tab_zelle)))
            {
                if ($group != "FIRSTGROUP")
                    $group_string .= xml_close_tag($xml_groupnames_lecture["subgroup1"]);
                if ($group_tab_zelle == "status")
                    $group_string .= xml_open_tag($xml_groupnames_lecture["subgroup1"], $SEM_TYPE[$db->f($group_tab_zelle)]["name"]);
                else
                    $group_string .= xml_open_tag($xml_groupnames_lecture["subgroup1"], $db->f($group_tab_zelle));
                $group = $db->f($group_tab_zelle);
                if (($do_subgroup) AND ($subgroup == $db->f($subgroup_tab_zelle)))
                    $subgroup = "NEXTGROUP";
            }
            if (($do_subgroup) AND ($subgroup != $db->f($subgroup_tab_zelle)))
            {
                if ($subgroup != "FIRSTGROUP")
                    $group_string = xml_close_tag($xml_groupnames_lecture["subgroup2"]) . $group_string;
                $group_string .= xml_open_tag($xml_groupnames_lecture["subgroup2"], $db->f($subgroup_tab_zelle));
                $subgroup = $db->f($subgroup_tab_zelle);
            }
            $data_object .= $group_string;
            $object_counter++;
            $data_object .= xml_open_tag($xml_groupnames_lecture["object"], $db->f("seminar_id"));
            $sem_obj = new Seminar($db->f("seminar_id"));
            while ( list($key, $val) = each($xml_names_lecture))
            {
                if ($val == "") $val = $key;
                if ($key == "status")
                    $data_object .= xml_tag($val, $SEM_TYPE[$db->f($key)]["name"]);

                elseif ($key == "ort") {
                    $data_object .= xml_tag($val, $sem_obj->getDatesTemplate('dates/seminar_export_location'));

                } elseif (($key == "bereich") AND (($SEM_CLASS[$SEM_TYPE[$db->f("status")]["class"]]["bereiche"]))) {
                    $data_object .= xml_open_tag($xml_groupnames_lecture["childgroup3"]);
                    $pathes = get_sem_tree_path($db->f("seminar_id"));
                    if (is_array($pathes)){
                        foreach ($pathes as $sem_tree_id => $path_name)
                            $data_object .= xml_tag($val, $path_name);
                    } else {
                        $data_object .= xml_tag($val, "n.a.");
                    }
                    $data_object .= xml_close_tag($xml_groupnames_lecture["childgroup3"]);
                                }
                elseif ($key == "admission_turnout")
                {
                        $data_object .= xml_open_tag($val, sprintf ("%s", $db->f("admission_type")) ? _("max.") : _("erw.")) . $db->f($key) . xml_close_tag($val);
                }
                elseif ($key == "teilnehmer_anzahl_aktuell")
                {
                    $db3->query("SELECT COUNT(user_id) FROM seminar_user WHERE seminar_id='".$db->f('seminar_id')."' AND status='autor'");
                    $db3->next_record();
                    $data_object .= xml_tag($val, $db3->f(0));
                }
                elseif ($key == "metadata_dates")
                {
                    $data_object .= xml_open_tag( $xml_groupnames_lecture["childgroup1"] );
                    $vorb = vorbesprechung($db->f("seminar_id"), 'export');
                    if ($vorb != false)
                        $data_object .= xml_tag($val[0], $vorb);
                    if (($first_date = SeminarDB::getFirstDate($db->f("seminar_id")))
                        && count($first_date)) {
                        $really_first_date = new SingleDate($first_date[0]);
                        $data_object .= xml_tag($val[1], $really_first_date->getDatesExport());
                    }
                    $data_object .= xml_tag($val[2], $sem_obj->getDatesExport());
                    $data_object .= xml_close_tag( $xml_groupnames_lecture["childgroup1"] );
                }
                elseif ($key == "Institut_id")
                {
                    $data_object .= xml_tag($val, $db->f('heimateinrichtung') , array('key' => $db->f($key)));
                }
                elseif ($db->f($key) != "")
                    $data_object .= xml_tag($val, $db->f($key));
            }
            $db2->query("SELECT seminar_user.position, auth_user_md5.user_id,auth_user_md5.username, auth_user_md5.Vorname, auth_user_md5.Nachname, user_info.title_front, user_info.title_rear FROM seminar_user
                        LEFT JOIN user_info USING(user_id)
                        LEFT JOIN auth_user_md5 USING(user_id)
                        WHERE (seminar_user.status = 'dozent') AND (seminar_user.Seminar_id = '" . $db->f('seminar_id') . "') ORDER BY seminar_user.position ");
            $data_object .= "<" . $xml_groupnames_lecture["childgroup2"] . ">\n";
            while ($db2->next_record())
                {
                    if ($ex_person_details) $persons[$db2->f('user_id')] = true;
                    $content_string = $db2->f("Vorname") . " " . $db2->f("Nachname");
                    if ($db2->f("title_front") != "")
                        $content_string = $db2->f("title_front") . " " . $content_string;
                    if ($db2->f("title_rear") != "")
                        $content_string = $content_string . ", " . $db2->f("title_rear");
                    $data_object .= xml_tag($xml_groupnames_lecture["childobject2"], $content_string, array('key' => $db2->f('username')));
                }
            $data_object .= xml_close_tag( $xml_groupnames_lecture["childgroup2"] );
        // freie Datenfelder ausgeben
            $data_object .= export_datafields($db->f("seminar_id"), $xml_groupnames_lecture["childgroup4"], $xml_groupnames_lecture["childobject4"], 'sem', $db->f("status"));
            $data_object .= xml_close_tag( $xml_groupnames_lecture["object"] );
            reset($xml_names_lecture);
            output_data($data_object, $o_mode);
            $data_object = "";
        }

    if (($do_subgroup) AND ($subgroup != "FIRSTGROUP"))
        $data_object .= xml_close_tag($xml_groupnames_lecture["subgroup2"]);
    if (($do_group) AND ($group != "FIRSTGROUP"))
        $data_object .= xml_close_tag($xml_groupnames_lecture["subgroup1"]);

    $data_object .= xml_close_tag($xml_groupnames_lecture["group"]);
    output_data($data_object, $o_mode);
    $data_object = "";
}


/**
* Exports member-list for a Stud.IP-lecture.
*
* This function gets the data of the members of a lecture and writes it into $data_object.
* It calls output_data afterwards.
*
* @access   public
* @param        string  $inst_id    Stud.IP-inst_id for export
* @param        string  $ex_sem_id  allows to choose which lecture is to be exported
*/
function export_teilis($inst_id, $ex_sem_id = "no")
{
    global $db, $db2, $range_id, $xml_file, $o_mode, $xml_names_person, $xml_groupnames_person, $xml_names_studiengaenge, $xml_groupnames_studiengaenge, $object_counter, $filter, $SEM_CLASS, $SEM_TYPE, $SessSemName;

    $db=new DB_Seminar;

    if ($filter == "status")
    {
        $db->query ("SELECT name, statusgruppe_id FROM statusgruppen WHERE range_id = '$ex_sem_id' ORDER BY position ASC");
        while ($db->next_record())
        {
            $gruppe[$db->f("statusgruppe_id")] = $db->f("name");
        }
        $gruppe["no"] = _("keiner Funktion oder Gruppe zugeordnet");
    }
    else
    {
        $db->query ("SELECT name, studiengaenge.studiengang_id FROM studiengaenge LEFT JOIN admission_seminar_studiengang USING(studiengang_id) WHERE seminar_id = '$ex_sem_id' ");
        while ($db->next_record())
        {
            $studiengang[$db->f("studiengang_id")] = $db->f("name");
        }
        $studiengang["all"] = _("Alle Studiengänge");
        if ($filter != 'awaiting'){
            if (!$SEM_CLASS[$SEM_TYPE[$SessSemName["art_num"]]["class"]]["workgroup_mode"]){
                $gruppe = array ("dozent" => _("DozentInnen"),
                  "tutor" => _("TutorInnen"),
                  "autor" => _("AutorInnen"),
                  "user" => _("LeserInnen"),
                  "accepted" => _("Vorläufig akzeptierte TeilnehmerInnen"));

            } else {
                $gruppe = array ("dozent" => _("LeiterInnen"),
                  "tutor" => _("Mitglieder"),
                  "autor" => _("AutorInnen"),
                  "user" => _("LeserInnen"),
                  "accepted" => _("Vorläufig akzeptierte TeilnehmerInnen"));
            }
        } else {
            $gruppe['awaiting'] = _("Anmeldeliste");
        }

    }

    $data_object .= xml_open_tag( $xml_groupnames_person["group"] );

    while (list ($key1, $val1) = each ($gruppe))
    {
        if ($filter == "status") // Gruppierung nach Statusgruppen / Funktionen
        {
            if ($key1 == "no")
                $db->query ("SELECT ui.*, aum.*, su.*, FROM_UNIXTIME(su.mkdate) as registration_date, GROUP_CONCAT(studiengaenge.name SEPARATOR ', ') as nutzer_studiengaenge
                    FROM seminar_user su
                    LEFT JOIN auth_user_md5 aum USING ( user_id )
                    LEFT JOIN user_info ui USING ( user_id )
                    LEFT JOIN user_studiengang USING(user_id)
                    LEFT JOIN studiengaenge USING(studiengang_id)
                    WHERE seminar_id = '$ex_sem_id' GROUP BY aum.user_id ORDER BY Nachname");
            else
                $db->query ("SELECT DISTINCT ui.*, aum.*, su.*,FROM_UNIXTIME(su.mkdate) as registration_date, GROUP_CONCAT(studiengaenge.name SEPARATOR ', ') as nutzer_studiengaenge
                    FROM statusgruppe_user
                    LEFT JOIN seminar_user su USING (user_id)
                    LEFT JOIN auth_user_md5 aum USING ( user_id )
                    LEFT JOIN user_info ui USING ( user_id )
                    LEFT JOIN user_studiengang USING(user_id)
                    LEFT JOIN studiengaenge USING(studiengang_id)
                    WHERE statusgruppe_id = '$key1' AND seminar_id = '$ex_sem_id' GROUP BY aum.user_id ORDER BY Nachname");
//
        }
        else // Gruppierung nach Status in der Veranstaltung / Einrichtung
        if ($key1 == 'accepted'){
            $db->query ("SELECT ui.*, aum.*, asu.comment, asu.studiengang_id as admission_studiengang_id,FROM_UNIXTIME(asu.mkdate) as registration_date , GROUP_CONCAT(studiengaenge.name SEPARATOR ', ') as nutzer_studiengaenge
                FROM admission_seminar_user asu
                LEFT JOIN user_info ui USING(user_id)
                LEFT JOIN auth_user_md5 aum USING(user_id)
                LEFT JOIN user_studiengang USING(user_id)
                LEFT JOIN studiengaenge ON(user_studiengang.studiengang_id=studiengaenge.studiengang_id)
                WHERE seminar_id = '$ex_sem_id' AND asu.status = 'accepted'  GROUP BY aum.user_id ORDER BY Nachname");
        } elseif ($key1 == 'awaiting') {
            $db->query("SELECT ui.*, aum.*, asu.comment, asu.studiengang_id as admission_studiengang_id, asu.position as admission_position, GROUP_CONCAT(studiengaenge.name SEPARATOR ', ') as nutzer_studiengaenge
                        FROM admission_seminar_user asu
                        LEFT JOIN user_info ui USING(user_id)
                        LEFT JOIN auth_user_md5 aum USING(user_id)
                        LEFT JOIN user_studiengang USING(user_id)
                        LEFT JOIN studiengaenge ON(user_studiengang.studiengang_id=studiengaenge.studiengang_id)
                        WHERE asu.seminar_id = '$ex_sem_id' AND asu.status != 'accepted'
                        GROUP BY aum.user_id ORDER BY position");
        } else {
            $db->query ("SELECT ui.*, aum.*, su.*,FROM_UNIXTIME(su.mkdate) as registration_date, GROUP_CONCAT(studiengaenge.name SEPARATOR ', ') as nutzer_studiengaenge
                FROM seminar_user su
                LEFT JOIN auth_user_md5 aum USING ( user_id )
                LEFT JOIN user_info ui USING ( user_id )
                LEFT JOIN user_studiengang USING(user_id)
                LEFT JOIN studiengaenge ON(user_studiengang.studiengang_id=studiengaenge.studiengang_id)
                WHERE seminar_id = '$ex_sem_id' AND su.status = '" . $key1 . "'   GROUP BY aum.user_id ORDER BY position, Nachname");
        }

        $data_object_tmp = '';
        $object_counter_tmp = $object_counter;
        if ($db->num_rows())
        {
            $data_object_tmp .= xml_open_tag($xml_groupnames_person["subgroup1"], $val1);
            while ($db->next_record()) {
                if (($key1 != "no") OR ($person_out[$db->f("user_id")] != true)) // Nur Personen ausgeben, die entweder einer Gruppe angehoeren oder zur Veranstaltung gehoeren und noch nicht ausgegeben wurden.
                {
                    $object_counter++;
                    $data_object_tmp .= xml_open_tag($xml_groupnames_person["object"], $db->f("username"));
                    while ( list($key, $val) = each($xml_names_person))
                    {
                        if ($val == "") $val = $key;
                        if (($key == "admission_studiengang_id") AND ($db->f($key) != ""))
                            $data_object_tmp .= xml_tag($val, $studiengang[$db->f($key)]);
                        elseif ($db->f($key) != "")
                            $data_object_tmp .= xml_tag($val, $db->f($key));
                    }
                // freie Datenfelder ausgeben
                    $data_object_tmp .= export_datafields($db->f("user_id"), $xml_groupnames_person["childgroup1"], $xml_groupnames_person["childobject1"], 'user');

                    $data_object_tmp .= export_additional_data($db->f("user_id"), $range_id, $xml_groupnames_person['childgroup2']);

                    // export additional fields

                    $data_object_tmp .= xml_close_tag( $xml_groupnames_person["object"] );
                    reset($xml_names_person);
                    $person_out[$db->f("user_id")] = true;
                }
            }
            $data_object_tmp .= xml_close_tag($xml_groupnames_person["subgroup1"]);
            if ($object_counter_tmp != $object_counter) $data_object .= $data_object_tmp;
        }
    }

    $data_object .= xml_close_tag( $xml_groupnames_person["group"]);

    if ($filter != "status")
    {
        $db->query ("SELECT name, studiengaenge.studiengang_id FROM studiengaenge LEFT JOIN user_studiengang USING(studiengang_id) LEFT JOIN seminar_user USING(user_id) WHERE seminar_id = '$ex_sem_id' ");
        while ($db->next_record())
        {
            $studiengang_count[$db->f("name")] ++;
        }
        if (isset($studiengang_count))
        {
            $data_object .= xml_open_tag($xml_groupnames_studiengaenge["group"]);
            for ($i2 = 0; $i2 < sizeof( $studiengang_count); $i2 ++)
            while (list ($key, $val) = each ($studiengang_count))
            {
                $data_object .= xml_open_tag($xml_groupnames_studiengaenge["object"]);
                $data_object .= xml_tag($xml_names_studiengaenge["name"], $key);
                $data_object .= xml_tag($xml_names_studiengaenge["count"], $val);
                $data_object .= xml_close_tag($xml_groupnames_studiengaenge["object"]);
            }
            $data_object .= xml_close_tag($xml_groupnames_studiengaenge["group"]);
        }
    }

    output_data($data_object, $o_mode);
    $data_object = "";
}

/**
* Exports member-list for a Stud.IP-institute.
*
* This function gets the data of the members of an institute and writes it into $data_object.
* The order of the members depends on the grouping-option $filter.
* It calls output_data afterwards.
*
* @access   public
* @param        string  $inst_id    Stud.IP-inst_id for export
* @param        string  $ex_sem_id  allows to choose which lecture is to be exported
*/
function export_pers($inst_id)
{
    global $db, $db2, $range_id, $xml_file, $o_mode, $xml_names_person, $xml_groupnames_person, $object_counter, $filter;

    $db=new DB_Seminar;

    $group = "FIRSTGROUP";
    $group_tab_zelle = "name";
    $do_group = true;

    $data_object = xml_open_tag( $xml_groupnames_person["group"] );

    $db->query("SELECT statusgruppen.name,aum.user_id,
        aum.Nachname, aum.Vorname, ui.inst_perms, ui.raum,
        ui.sprechzeiten, ui.Telefon, ui.Fax, aum.Email,
        aum.username, info.Home, info.geschlecht, info.title_front, info.title_rear FROM statusgruppen
        LEFT JOIN statusgruppe_user sgu USING(statusgruppe_id)
        LEFT JOIN user_inst ui ON (ui.user_id = sgu.user_id AND ui.Institut_id = range_id AND ui.inst_perms!='user')
        LEFT JOIN auth_user_md5 aum ON (ui.user_id = aum.user_id)
        LEFT JOIN user_info info ON (ui.user_id = info.user_id)
        WHERE range_id = '" . $inst_id . "'
        ORDER BY statusgruppen.position, sgu.position ");

    $data_found = false;

    while ($db->next_record())
    {
        $data_found = true;
        $group_string = "";
        if (($do_group) AND ($group != $db->f($group_tab_zelle)))
        {
            if ($group != "FIRSTGROUP")
                $group_string .= xml_close_tag($xml_groupnames_person["subgroup1"]);
            $group_string .= xml_open_tag($xml_groupnames_person["subgroup1"], $db->f($group_tab_zelle));
            $group = $db->f($group_tab_zelle);
        }
        $data_object .= $group_string;
        $object_counter++;
        $data_object .= xml_open_tag($xml_groupnames_person["object"], $db->f("username"));
        while ( list($key, $val) = each($xml_names_person))
        {
            if ($val == "") $val = $key;
            if ($db->f($key) != "")
                $data_object .= xml_tag($val, $db->f($key));
        }
    // freie Datenfelder ausgeben
        $data_object .= export_datafields($db->f("user_id"), $xml_groupnames_person["childgroup1"], $xml_groupnames_person["childobject1"], 'user');
        $data_object .= xml_close_tag( $xml_groupnames_person["object"] );
        reset($xml_names_person);
        output_data($data_object, $o_mode);
        $data_object = "";
    }

    if (($do_group) AND ($data_found))
        $data_object .= xml_close_tag($xml_groupnames_person["subgroup1"]);

    $data_object .= xml_close_tag( $xml_groupnames_person["group"]);
    output_data($data_object, $o_mode);
    $data_object = "";
}

/**
* Exports list of persons.
*
*
* @access   public
* @param        array   $persons    Stud.IP-user_ids for export
*/
function export_persons($persons)
{
    global $xml_names_person, $xml_groupnames_person, $object_counter, $o_mode, $ex_person_details;

    $db = new DB_Seminar;

    if (is_array($persons)){
        $db->query("SELECT * FROM auth_user_md5
                LEFT JOIN user_info info USING(user_id)
                WHERE auth_user_md5.user_id IN('".join("','", $persons)."')");


        while ($db->next_record()) {
            $object_counter++;
            $data_object = xml_open_tag($xml_groupnames_person["object"], $db->f("username"));
            if ($ex_person_details) $data_object .= xml_tag('id', $db->f('user_id'));
            while ( list($key, $val) = each($xml_names_person)){
                if ($val == "") $val = $key;
                if ($db->f($key) != "") $data_object .= xml_tag($val, $db->f($key));
            }
            // freie Datenfelder ausgeben
            $data_object .= export_datafields($db->f("user_id"), $xml_groupnames_person["childgroup1"], $xml_groupnames_person["childobject1"], 'user');
            $data_object .= xml_close_tag( $xml_groupnames_person["object"] );
            reset($xml_names_person);
            output_data($data_object, $o_mode);
            $data_object = "";
        }
    }
    $data_object = "";
}

/**
* helper function to export custom datafields
*
* only visible datafields are exported (depending on user perms)
* @access   public
* @param    string  $range_id   id for object to export
* @param    string  $childgroup_tag name of outer tag
* @param    string  $childobject_tag    name of inner tags
*/
function export_datafields($range_id, $childgroup_tag, $childobject_tag, $object_type = null, $object_class_hint = null){
    $ret = '';
    $d_fields = false;
    $localEntries = DataFieldEntry::getDataFieldEntries($range_id, $object_type, $object_class_hint);
    if(is_array($localEntries )){
        foreach ($localEntries as $entry){
            if ($entry->structure->accessAllowed($GLOBALS['perm'], $GLOBALS['user']->id) && $entry->getDisplayValue()) {
                if (!$d_fields) $ret .= xml_open_tag( $childgroup_tag );
                $ret .= xml_open_tag($childobject_tag , $entry->getName());
                $ret .= xml_escape($entry->getDisplayValue(false));
                $ret .= xml_close_tag($childobject_tag);
                $d_fields = true;
            }
        }
    }
    if ($d_fields) $ret .= xml_close_tag( $childgroup_tag );
    return $ret;
}

/**
* helper function to export custom datafields
*
* only visible datafields are exported (depending on user perms)
* @access   public
* @param    string  $range_id   id for object to export
* @param    string  $childgroup_tag name of outer tag
* @param    string  $childobject_tag    name of inner tags
 */
 function get_additional_data($user_id, $range_id)
 {
     $collected_data = array();

     $db = new DB_Seminar();

     if(is_array($GLOBALS['TEILNEHMER_VIEW']))
     {
         $db->query("SELECT status FROM seminare WHERE seminar_id = '$range_id'");

         if ($db->next_record()) {
             $sem_class = $GLOBALS['SEM_TYPE'][$db->f('status')]['class'];
             $db->query("SELECT datafield_id FROM teilnehmer_view WHERE seminar_id = $sem_class");

             while ($db->next_record()) {
                 $sem_view_rights[$db->f("datafield_id")] = TRUE;
             }
         }

         $collected_data = array();

         foreach($GLOBALS['TEILNEHMER_VIEW'] as $val) {

             if (isset($sem_view_rights[$val["field"]])) {

                 $user_data = array();

                 switch ($val["table"]) {
                 case "datafields":
                     foreach (DataFieldEntry::getDataFieldEntries($user_id, 'user') as $entry) {
                         if ($entry->getName() == $val["field"] && $entry->getDisplayValue()) {
                             $user_data = array("name" => $val["name"], "content" => $entry->getDisplayValue(false));
                         }
                     }
                     break;

                 case "special":
                     switch ($val["field"]) {
                     case "groups":
                         $db->query("SELECT name FROM statusgruppen a, statusgruppe_user b WHERE a.range_id = '$range_id' AND a.statusgruppe_id = b.statusgruppe_id AND b.user_id = '$user_id'");

                         $zw = array();

                         while ($db->next_record()) {
                             $zw []= $db->f("name")." ";
                         }

                         $user_data = array("name" => $val["name"], "content" => $zw);
                         break;

                     case "user_picture":
                         $user_data = array('name' => 'user_picture', 'content' => true);
                         break;
                     }
                     break;

                 default:
                     $query = "SELECT ".$val["field"]." FROM ".$val["table"]." WHERE user_id = '$user_id'";
                     $db->query($query);

                     if ($db->next_record()) {
                         $content =  $db->f($val["field"]);
                         $user_data = array('name' => $val["field"], "content" => $content);


                         switch ($val["field"]) {
                         case "geschlecht":
                             if ($content == "1")
                                 $content = _("männlich");
                             else if ($content == "2")
                                 $content = _("weiblich");
                             else
                                 $content = _("unbekannt");

                             $user_data = array("name" => $val["name"], "content" => $content);
                             break;

                         case "preferred_language":
                             if (is_null($content) || $content == '')
                                 $content = $GLOBALS['DEFAULT_LANGUAGE'];

                             if ($content == "de_DE")
                                 $content = _("Deutsch");
                             else
                                $content = _("Englisch");

                             $user_data = array("name" => $val["name"], "content" => $content);
                             break;
                         }
                     }
                     break;

                 }

                 // display by default, even if display isn't set in config
                 if (!isset($val['export']) || !empty($val["export"]))
                 {
                     $user_data['export'] = 1;
                 }

                 // display by default, even if display isn't set in config
                 if (!isset($val['display']) || !empty($val['display']))
                 {
                     $user_data['display'] = 1;
                 }

                 $collected_data [$val["field"]]= $user_data;
             }
         }
     }

     return $collected_data;

 }

function export_additional_data($user_id, $range_id, $childgroup_tag)
{
    $ret = '';
  $a_fields = false;

  $additional_data = get_additional_data($user_id, $range_id);

    foreach($additional_data as $val) {
    if ($val['export'])
    {
      if (!$a_fields) $ret .= xml_open_tag($childgroup_tag);

      $childobject_tag = $GLOBALS['xml_groupnames_person']['childobject2'];

      $ret .= xml_open_tag($childobject_tag, $val["name"]);

      if (is_array($val['content']))
      {
        $ret .= xml_escape (implode(',',$val['content']));
      } else
      {
        $ret .= xml_escape($val['content']);
      }
      $ret .= xml_close_tag($childobject_tag);

      $a_fields = true;
    }
  }

  if ($a_fields) $ret .= xml_close_tag($childgroup_tag);

    return $ret;
}

?>
