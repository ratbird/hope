<?php
# Lifter001: TEST
# Lifter002: TODO
# Lifter005: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*
show_admission.php - Instituts-Mitarbeiter-Verwaltung von Stud.IP
Copyright (C) 2000 Ralf Stockmann <rstockm@gwdg.de>

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

require '../lib/bootstrap.php';

ob_start();
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check("admin");


// Set this to something, just something different...
$hash_secret = "trubatik";
$msg = array();

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page
PageLayout::setTitle(_("Übersicht laufender Anmeldeverfahren / Grupppierung von Veranstaltungen"));
Navigation::activateItem('/tools/show_admission');

require_once('config.inc.php'); //Grunddaten laden
require_once('lib/visual.inc.php'); //htmlReady
require_once('lib/classes/StudipAdmissionGroup.class.php');
require_once('lib/admission.inc.php');

function semadmission_get_data($seminare_condition){
    global $perm;
    $db = new DB_Seminar();
    $db2 = new DB_Seminar();
    $ret = array();
    $sorter = array();
    list($institut_id, $all) = explode('_', $_SESSION['show_admission']['institut_id']);
    if ($institut_id == "all"  && $perm->have_perm("root")){
        $query = "SELECT * FROM seminare WHERE 1 $seminare_condition ORDER BY admission_group DESC, start_time DESC, Name";
    } elseif ($all == 'all'){
        $query = "SELECT seminare.* FROM seminare
        JOIN Institute USING ( Institut_id ) WHERE Institute.fakultaets_id  = '{$institut_id}' $seminare_condition
        GROUP BY seminare.Seminar_id ORDER BY admission_group DESC, start_time DESC, Name";
    }else{
        $query = "SELECT seminare.* FROM seminare
        WHERE seminare.Institut_id = '{$institut_id}' $seminare_condition
        GROUP BY seminare.Seminar_id ORDER BY admission_group DESC, start_time DESC, Name";
    }

    $db->query($query);
    while($db->next_record()){
        $seminar_id = $db->f("Seminar_id");
        $ret[$seminar_id] = $db->Record;
        $query2 = "SELECT COUNT(IF(admission_studiengang_id <> '', user_id, NULL)) as t1,COUNT(IF(admission_studiengang_id = '', user_id, NULL)) as t2  FROM seminar_user WHERE seminar_id='$seminar_id' AND status IN('autor','user')";
        $db2->query($query2);
        $db2->next_record();
        $ret[$seminar_id]['count_teilnehmer'] = $db2->f('t1');
        $ret[$seminar_id]['count_teilnehmer_aux'] = $db2->f('t2');
                $query2 = "SELECT COUNT(IF(status='accepted', 1, NULL)) AS count2,
                    COUNT(IF(status='claiming' OR status='awaiting', 1, NULL)) AS count3
                    FROM admission_seminar_user WHERE seminar_id='$seminar_id' GROUP BY seminar_id";
        $db2->query($query2);
        $db2->next_record();
        $ret[$seminar_id]['count_anmeldung'] = $db2->f("count2");
        $ret[$seminar_id]['count_wartende'] = $db2->f("count3");
        $status = array();
        if($db->f('admission_type') == 3) $status[] = _("gesperrt");
        if($db->f('admission_type') == 2) $status[] = _("Chronologisch");
        if($db->f('admission_type') == 1) $status[] = _("Losverfahren");
        if($db->f('admission_type') == 0) $status[] = _("kein Anmeldeverfahren");
        if($db->f('admission_prelim'))  $status[] = _("vorläufig");
        $ret[$seminar_id]['admission_status_text'] = join('/',$status);
        if ($db->f('admission_group')) {
            if($db->f('admission_group') != $last_group) {
                unset($last_group);
            }
            if (!isset($last_group)) {
                $last_group = $db->f('admission_group');
                $groupname = DbManager::get()->query("SELECT name FROM admission_group WHERE group_id=" . DbManager::get()->quote($last_group))->fetchColumn();
                if(!$groupname) $groupname = _("Gruppe") . ++$groupcount;
            }
            $ret[$seminar_id]['groupname'] = $groupname;
        }
        $sorter[$seminar_id] = $ret[$seminar_id][$_SESSION['show_admission']['sortby']['field']];
    }
    if($_SESSION['show_admission']['sortby']['field'] && count($ret) && count($ret) == count($sorter)){
        array_multisort($sorter, ($_SESSION['show_admission']['sortby']['direction'] ? SORT_ASC : SORT_DESC), $ret);
    }
    return $ret;
}

function semadmission_create_result_xls($data){
    require_once "vendor/write_excel/OLEwriter.php";
    require_once "vendor/write_excel/BIFFwriter.php";
    require_once "vendor/write_excel/Worksheet.php";
    require_once "vendor/write_excel/Workbook.php";

    global $_my_inst;
    $tempfile = null;
    if (count($data)) {
        $tmpfile = $GLOBALS['TMP_PATH'] . '/' . md5(uniqid('write_excel',1));
        // Creating a workbook
        $workbook = new Workbook($tmpfile);
        $head_format =& $workbook->addformat();
        $head_format->set_size(12);
        $head_format->set_bold();
        $head_format->set_align("left");
        $head_format->set_align("vcenter");

        $head_format_merged =& $workbook->addformat();
        $head_format_merged->set_size(12);
        $head_format_merged->set_bold();
        $head_format_merged->set_align("left");
        $head_format_merged->set_align("vcenter");
        $head_format_merged->set_merge();
        $head_format_merged->set_text_wrap();

        $caption_format =& $workbook->addformat();
        $caption_format->set_size(10);
        $caption_format->set_align("left");
        $caption_format->set_align("vcenter");
        $caption_format->set_bold();
        //$caption_format->set_text_wrap();

        $data_format =& $workbook->addformat();
        $data_format->set_size(10);
        $data_format->set_align("left");
        $data_format->set_align("vcenter");

        $caption_format_merged =& $workbook->addformat();
        $caption_format_merged->set_size(10);
        $caption_format_merged->set_merge();
        $caption_format_merged->set_align("left");
        $caption_format_merged->set_align("vcenter");
        $caption_format_merged->set_bold();

        // Creating the first worksheet
        $worksheet1 =& $workbook->addworksheet(_("laufende Anmeldeverfahren"));
        $worksheet1->set_row(0, 20);
        $worksheet1->write_string(0, 0, _("Stud.IP Veranstaltungen") . ' - ' . $GLOBALS['UNI_NAME_CLEAN'] ,$head_format);
        $worksheet1->set_row(1, 20);
        if(!$_SESSION['_default_sem']){
            $semester = _("alle");
        } else {
            $sem_array =& SemesterData::GetSemesterArray();
            $semester = $sem_array[SemesterData::GetSemesterIndexById($_SESSION['_default_sem'])]['name'];
        }
        $worksheet1->write_string(1, 0, sprintf(_("Einrichtung: %s, Semester: %s"),
        $_my_inst[$_SESSION['show_admission']['institut_id']]['name'],
        $semester), $caption_format);

        foreach(range(1,10) as $c) $worksheet1->write_blank(0,$c,$head_format);
        foreach(range(1,10) as $c) $worksheet1->write_blank(1,$c,$head_format);
        $worksheet1->set_column(1, 1, 40);
        foreach(range(2,10) as $c) $worksheet1->set_column(1, $c, 15);

        $row = 2;

        $worksheet1->write_string($row,0, _("Gruppe"), $caption_format);
        $worksheet1->write_string($row,1, _("Veranstaltung"), $caption_format);
        $worksheet1->write_string($row,2, _("Status"), $caption_format);
        $worksheet1->write_string($row,3, _("Kontingent Teilnehmer"), $caption_format);
        $worksheet1->write_string($row,4, _("zusätzliche Teilnehmer"), $caption_format);
        $worksheet1->write_string($row,5, _("Max. Teilnehmer"), $caption_format);
        $worksheet1->write_string($row,6, _("Anmelde & Akzeptiertliste"), $caption_format);
        $worksheet1->write_string($row,7, _("Warteliste"), $caption_format);
        $worksheet1->write_string($row,8, _("Losdatum / Ende Kontingente"), $caption_format);
        $worksheet1->write_string($row,9, _("Anmeldestartzeit"), $caption_format);
        $worksheet1->write_string($row,10, _("Anmeldeendzeit"), $caption_format);

        ++$row;

        $groupcount = 0;
        foreach($data as $seminar_id => $semdata) {
            $teilnehmer = $semdata['count_teilnehmer'];
            $teilnehmer_aux = $semdata['count_teilnehmer_aux'];
            $quota = $semdata['admission_turnout'];
            $count2 = $semdata['count_anmeldung'];
            $count3 = $semdata['count_wartende'];
            // show end date only if it is actually relevant
            $datum = $semdata['admission_type'] == 1 || $semdata['admission_type'] == 2 && $semdata['admission_enable_quota'] ? $semdata['admission_endtime'] : -1;
            $startdatum = $semdata['admission_starttime'];
            $enddatum = $semdata['admission_endtime_sem'];
            $status = $semdata['admission_status_text'];
            $worksheet1->write_string($row, 0, $semdata['groupname'], $data_format);
            $worksheet1->write_string($row, 1, $semdata['Name'], $data_format);
            $worksheet1->write_string($row, 2, $status, $data_format);
            $worksheet1->write_number($row, 3, (int)$teilnehmer, $data_format);
            $worksheet1->write_number($row, 4, (int)$teilnehmer_aux, $data_format);
            $worksheet1->write_number($row, 5, (int)$quota, $data_format);
            $worksheet1->write_number($row, 6, (int)$count2, $data_format);
            $worksheet1->write_number($row, 7, (int)$count3, $data_format);
            $worksheet1->write_string($row, 8, ($datum != -1 ? date("d.m.Y G:i", $datum) : '') , $data_format);
            $worksheet1->write_string($row, 9, ($startdatum != -1 ? date("d.m.Y G:i", $startdatum) : '') , $data_format);
            $worksheet1->write_string($row, 10, ($enddatum != -1 ? date("d.m.Y G:i", $enddatum) : '') , $data_format);
            ++$row;
        }
        $workbook->close();
    }
    return $tmpfile;
}

function semadmission_get_institute($seminare_condition){
    global $perm, $user;
    $db = new DB_Seminar();
    $db2 = new DB_Seminar();
    if($perm->have_perm('root')){
        $db->query("SELECT COUNT(*) FROM seminare WHERE 1 $seminare_condition");
        $db->next_record();
        $_my_inst['all'] = array("name" => _("alle") , "num_sem" => $db->f(0));
        $db->query("SELECT a.Institut_id,a.Name, 1 AS is_fak, count(seminar_id) AS num_sem FROM Institute a
            LEFT JOIN seminare ON(seminare.Institut_id=a.Institut_id $seminare_condition  ) WHERE a.Institut_id=fakultaets_id GROUP BY a.Institut_id ORDER BY is_fak,Name,num_sem DESC");
    } else {
        $db->query("SELECT a.Institut_id,b.Name, IF(b.Institut_id=b.fakultaets_id,1,0) AS is_fak,count(seminar_id) AS num_sem FROM user_inst a LEFT JOIN Institute b USING (Institut_id)
            LEFT JOIN seminare ON(seminare.Institut_id=b.Institut_id $seminare_condition  ) WHERE a.user_id='$user->id' AND a.inst_perms='admin' GROUP BY a.Institut_id ORDER BY is_fak,Name,num_sem DESC");
    }
    while($db->next_record()){
        $_my_inst[$db->f("Institut_id")] = array("name" => $db->f("Name"), "is_fak" => $db->f("is_fak"), "num_sem" => $db->f("num_sem"));
        if ($db->f("is_fak")){
            $_my_inst[$db->f("Institut_id").'_all'] = array("name" => sprintf(_("[Alle unter %s]"),$db->f("Name")), "is_fak" => 'all', "num_sem" => $db->f("num_sem"));
            $db2->query("SELECT a.Institut_id, a.Name,count(seminar_id) AS num_sem FROM Institute a
                LEFT JOIN seminare ON(seminare.Institut_id=a.Institut_id $seminare_condition  ) WHERE fakultaets_id='" . $db->f("Institut_id") . "' AND a.Institut_id!='" .$db->f("Institut_id") . "'
                GROUP BY a.Institut_id ORDER BY a.Name,num_sem DESC");
            $num_inst = 0;
            $num_sem_alle = $db->f("num_sem");
            while ($db2->next_record()){
                if(!$_my_inst[$db2->f("Institut_id")]){
                    ++$num_inst;
                    $num_sem_alle += $db2->f("num_sem");
                }
                $_my_inst[$db2->f("Institut_id")] = array("name" => $db2->f("Name"), "is_fak" => 0 , "num_sem" => $db2->f("num_sem"));
            }
            $_my_inst[$db->f("Institut_id")]["num_inst"] = $num_inst;
            $_my_inst[$db->f("Institut_id").'_all']["num_inst"] = $num_inst;
            $_my_inst[$db->f("Institut_id").'_all']["num_sem"] = $num_sem_alle;
        }
    }
    return $_my_inst;
}

$db=new DB_Seminar;
$db2=new DB_Seminar;
$db3=new DB_Seminar;
$sem_condition = '';
$admission_condition = array();
$cssSw = new cssClassSwitcher();

$cols = array();
if ($ALLOW_GROUPING_SEMINARS) {
    $cols[] = array(1,_("Gruppe"),'admission_group');
}
$cols[] = array(25,_("Veranstaltung"),'Name');
$cols[] = array(10,_("Status"),'admission_status_text');
$cols[] = array(10,_("Anz. Kontingent") ,'count_teilnehmer');
$cols[] = array(10,_("Anz. zusätzlich"),'count_teilnehmer_aux');
$cols[] = array(10,_("Max."),'admission_turnout');
$cols[] = array(10,_("Anmeldeliste") ,'count_anmeldung');
$cols[] = array(10, _("Warteliste"),'count_wartende');
$cols[] = array(10, _("Losdatum") . ' ' . Assets::img('icons/16/grey/info-circle.png', array('title' => 'bei chronologischen Verfahren: Ende der Kontingentierung',
                                'class' => 'text-top')), 'admission_endtime');
$cols[] = array(10, _("Startzeit"),'admission_starttime');
$cols[] = array(10, _("Endzeit"),'admission_endtime_sem');

if(!isset($_SESSION['show_admission']['check_admission'])){
    $_SESSION['show_admission']['check_admission'] = true;
}

if(isset($_REQUEST['choose_institut_x'])){
    if(isset($_REQUEST['select_sem'])){
        $_SESSION['_default_sem'] = $_REQUEST['select_sem'];
    }
    $_SESSION['show_admission']['check_admission'] = isset($_REQUEST['check_admission']);
    $_SESSION['show_admission']['check_prelim'] = isset($_REQUEST['check_prelim']);
    $_SESSION['show_admission']['sem_name_prefix'] = trim(stripslashes($_REQUEST['sem_name_prefix']));
}

if ($_SESSION['_default_sem']){
    $semester = SemesterData::GetInstance();
    $one_semester = $semester->getSemesterData($_SESSION['_default_sem']);
    if($one_semester["beginn"]){
        $sem_condition = "AND seminare.start_time <=".$one_semester["beginn"]." AND (".$one_semester["beginn"]." <= (seminare.start_time + seminare.duration_time) OR seminare.duration_time = -1) ";
    }
}
if($_SESSION['show_admission']['check_admission']) $admission_condition[] = "admission_type > 0";
else $admission_condition[] = "admission_type = 0";
if($_SESSION['show_admission']['check_prelim']) $admission_condition[] = "admission_prelim = 1";
else $admission_condition[] = "admission_prelim <> 1";
if($_SESSION['show_admission']['sem_name_prefix']) $admission_condition[] = "seminare.Name LIKE '".mysql_escape_string($_SESSION['show_admission']['sem_name_prefix'])."%'";

$seminare_condition = "AND (" . join(" AND ", $admission_condition) . ") " .  $sem_condition;

$_my_inst = semadmission_get_institute($seminare_condition);

if (!is_array($_my_inst)){
    $_msg[] = array("info", sprintf(_("Sie wurden noch keinen Einrichtungen zugeordnet. Bitte wenden Sie sich an einen der zust&auml;ndigen %sAdministratoren%s."), "<a href=\"".URLHelper::getLink("dispatch.php/siteinfo/show")."\">", "</a>"));
} else {
    $_my_inst_arr = array_keys($_my_inst);
    if(!$_SESSION['show_admission']['institut_id']){
        $_SESSION['show_admission']['institut_id'] = $_my_inst_arr[0];
    }
    if($_REQUEST['institut_id']){
        $_SESSION['show_admission']['institut_id'] = ($_my_inst[$_REQUEST['institut_id']]) ? $_REQUEST['institut_id'] : $_my_inst_arr[0];
    }
}
if(isset($_REQUEST['admissiongroupdelete_x']) && isset($_REQUEST['group_id'])){
    $msg[] = array('info', _("Wollen Sie die Gruppierung f&uuml;r die ausgew&auml;hlte Gruppe aufl&ouml;sen?")
                            . '<br>' . _("Beachten Sie, dass f&uuml;r bereits eingetragene / auf der Warteliste stehende TeilnehmerInnen keine &Auml;nderungen vorgenommen werden.")
                            . '<form action="'.URLHelper::getLink().'" method="post">'
                            . CSRFProtection::tokenTag()
                            . '<input type="hidden" name="group_sem_x" value="1"><div style="padding:3px;">'
                            . '<input type="hidden" name="group_id" value="'.$_REQUEST['group_id'].'">'
                            . makeButton('ja', 'input', _("Gruppe auflösen"), 'admissiongroupreallydelete')
                            . '&nbsp;'
                            . makeButton('nein', 'input', _("abbrechen"))
                            . '</div></form>');
}
if(isset($_REQUEST['group_sem_x']) && (count($_REQUEST['gruppe']) > 1 || isset($_REQUEST['group_id'])) && !isset($_REQUEST['admissiongroupcancel_x'])){
    if(isset($_REQUEST['group_id'])){
            $group_obj = new StudipAdmissionGroup($_REQUEST['group_id']);
    } else {
        $group_obj = new StudipAdmissionGroup();
        foreach($_REQUEST['gruppe'] as $sid){
            $group_obj->addMember($sid);
        }
    }
    if(isset($_REQUEST['admissiongroupchange_x'])){
        $group_obj->setValue('name', trim(stripslashes($_REQUEST['admission_group_name'])));
        $group_obj->setValue('status', (int)$_REQUEST['admission_group_status']);
        $group_obj->setUniqueMemberValue('admission_type', (int)$_REQUEST['admission_group_type']);
        $group_obj->setUniqueMemberValue('read_level', 3);
        $group_obj->setUniqueMemberValue('write_level', 3);
        $admission_times = array();
        $ok = true;
        $do_admission_update = false;
        if(isset($_REQUEST['admission_change_enable_quota'])){
            $group_obj->setUniqueMemberValue('admission_enable_quota', $_REQUEST['admission_enable_quota']);
        }
        if(isset($_REQUEST['admission_change_endtime'])){
            $admission_times["admission_endtime"] = '-1';
            if (!check_and_set_date($_POST['adm_tag'], $_POST['adm_monat'], $_POST['adm_jahr'], $_POST['adm_stunde'], $_POST['adm_minute'], $admission_times, "admission_endtime")) {
                $msg[] = array("error", _("Bitte geben Sie g&uuml;ltige Zeiten f&uuml;r das Ende Kontingente / Losdatum ein!"));
                $ok = false;
            } else {
                if ($admission_times["admission_endtime"] > 0) {
                    if($admission_times["admission_endtime"] < time()){
                        $msg[] = array("error", _("Das Ende Kontingente / Losdatum liegt in der Vergangenheit."));
                        $ok = false;
                    }
                    foreach($group_obj->getMemberIds() as $semid){
                        if($group_obj->members[$semid]->isAdmissionQuotaChecked()){
                            if( $group_obj->members[$semid]->admission_starttime != -1 && $admission_times["admission_endtime"] < $group_obj->members[$semid]->admission_starttime){
                                $msg[] = array('error', sprintf(_("Das Ende Kontingente / Losdatum kann nicht vor dem Startdatum für Anmeldungen in der Veranstaltung <b>%s</b> liegen."), htmlReady($group_obj->members[$semid]->getName())));
                                $ok = false;
                            }
                            $tmp_first_date = veranstaltung_beginn($semid, 'int');
                            if ($tmp_first_date > 0 && $admission_times["admission_endtime"] > $tmp_first_date){
                                $msg[] = array("error", sprintf(_("Das Ende Kontingente / Losdatum liegt nach dem ersten Veranstaltungstermin am <b>%s</b> der Veranstaltung <b>%s</b>.")
                                , date ("d.m.Y", $tmp_first_date), htmlReady($group_obj->members[$semid]->getName())));
                                $ok = false;
                            }
                        }
                        if ($admission_times["admission_endtime"] > time() && $group_obj->members[$semid]->admission_selection_take_place == 1){
                            $msg[] = array('error', sprintf(_("Das Ende Kontingente / Losdatum kann in der Veranstaltung <b>%s</b> nicht geändert werden, da das Losen bereits durchgeführt wurde / die Kontingentierung bereits aufgehoben wurde."), htmlReady($group_obj->members[$semid]->getName())));
                            $ok = false;
                        }
                    }
                }
            }
        }
        if(isset($_REQUEST['admission_change_starttime'])){
            $admission_times["admission_starttime"] = -1;
            if (!check_and_set_date($_POST['adm_s_tag'], $_POST['adm_s_monat'], $_POST['adm_s_jahr'], $_POST['adm_s_stunde'], $_POST['adm_s_minute'], $admission_times, "admission_starttime")) {
                $msg[] = array("error", _("Bitte geben Sie g&uuml;ltige Zeiten f&uuml;r das Startdatum für Anmeldungen ein!"));
                $ok = false;
            }
        }
        if(isset($_REQUEST['admission_change_endtime_sem'])){
            $admission_times["admission_endtime_sem"] = -1;
            if (!check_and_set_date($_POST['adm_e_tag'], $_POST['adm_e_monat'], $_POST['adm_e_jahr'], $_POST['adm_e_stunde'], $_POST['adm_e_minute'], $admission_times, "admission_endtime_sem")) {
                $msg[] = array("error", _("Bitte geben Sie g&uuml;ltige Zeiten f&uuml;r das Enddatum für Anmeldungen ein!"));
                $ok = false;
            }
        }
        if(isset($_REQUEST['admission_change_turnout'])){
            if($_REQUEST['admission_turnout'] < 1){
                $msg[] = array("error" , _("Wenn Sie die Teilnahmebeschr&auml;nkung benutzen wollen, m&uuml;ssen Sie wenigstens einen Teilnehmer zulassen."));
                $ok = false;
            }
        }
        if($ok){
            if($admission_times["admission_endtime"] > 1){
                $group_obj->setUniqueMemberValue('admission_endtime', $admission_times["admission_endtime"]);
                $msg[] = array('msg', sprintf(_("Das Ende Kontingente / Losdatum wurde in allen Veranstaltungen geändert.")));
            }
            if($admission_times["admission_starttime"] > 1){
                $group_obj->setUniqueMemberValue('admission_starttime', $admission_times["admission_starttime"]);
                $msg[] = array('msg', sprintf(_("Das Startdatum für Anmeldungen wurde in allen Veranstaltungen geändert.")));
            }
            if($admission_times["admission_endtime_sem"] > 1){
                $group_obj->setUniqueMemberValue('admission_endtime_sem', $admission_times["admission_endtime_sem"]);
                $msg[] = array('msg', sprintf(_("Das Enddatum für Anmeldungen wurde in allen Veranstaltungen geändert.")));
            }
            if(isset($_REQUEST['admission_change_turnout'])){
                $do_admission_update = (int)$_REQUEST['admission_turnout'] >= $group_obj->getUniqueMemberValue('admission_turnout');
                $group_obj->setUniqueMemberValue('admission_turnout', (int)$_REQUEST['admission_turnout']);
                $msg[] = array('msg', sprintf(_("Die Teilnehmeranzahl wurde in allen Veranstaltungen auf %s geändert."),(int)$_REQUEST['admission_turnout']));
            }

        }
        foreach($group_obj->getMemberIds() as $semid){
            if( $group_obj->members[$semid]->isAdmissionQuotaChecked() && $group_obj->members[$semid]->admission_endtime < 1 ){
                $msg[] = array('error', sprintf(_("Die Veranstaltung <b>%s</b> hat keinen Eintrag für Ende Kontingente / Losdatum. Gruppierung nicht möglich."), htmlReady($group_obj->members[$semid]->getName())));
                $ok = false;
            }
            if( $group_obj->members[$semid]->admission_turnout < 1 ){
                $msg[] = array('error', sprintf(_("Die Veranstaltung <b>%s</b> hat keinen Eintrag für Teilnehmeranzahl. Gruppierung nicht möglich."), htmlReady($group_obj->members[$semid]->getName())));
                $ok = false;
            }
        }
        if($ok){
            if($group_obj->getValue('status') == 0 && $group_obj->getUniqueMemberValue('admission_type') == 1){
                $group_obj->setValue('status', 1);
                $msg[] = array('info', _("Das Losverfahren kann nur mit der Einstellung <b>Eintrag nur in einer Veranstaltung</b> kombiniert werden."));
            }
            if($group_obj->store()){
                $msg[] = array('msg', sprintf(_("Die Gruppe wurde erstellt / modifiziert.")));
            }
            $contingent = $group_obj->setMinimumContingent();
            if(count($contingent)){
                foreach($contingent as $sem_id) $sem_names[] = $group_obj->members[$sem_id]->getName();
                $msg[] = array('msg', sprintf(_("In den Veranstaltungen <b>%s</b> wurde ein Kontingent mit 100%% für alle Studiengänge eingerichtet."), htmlready(join(", ", $sem_names))));
            }
            if($do_admission_update){
                foreach($group_obj->getMemberIds() as $semid){
                    update_admission($semid);
                }
                $msg[] = array('msg', sprintf(_("Nachrücken in allen Veranstaltungen der Gruppe durchgeführt.")));
            }
        }
    }
    if($_REQUEST['admissiongroupreallydelete_x']){
        if($group_obj->delete()){
            $msg[] = array('msg', sprintf(_("Die Gruppe wurde aufgelöst.")));
            unset($group_obj);
        }
    }
}

if(isset($_REQUEST['sortby'])){
    foreach($cols as $col){
        if($_REQUEST['sortby'] == $col[2]){
            if($_SESSION['show_admission']['sortby']['field'] == $_REQUEST['sortby']){
                $_SESSION['show_admission']['sortby']['direction'] = (int)!$_SESSION['show_admission']['sortby']['direction'];
            } else {
                $_SESSION['show_admission']['sortby']['field'] = $_REQUEST['sortby'];
                $_SESSION['show_admission']['sortby']['direction'] = 0;
            }
            break;
        }
    }
}

if ($_REQUEST['cmd'] == 'send_excel_sheet'){
    $tmpfile = basename(semadmission_create_result_xls(semadmission_get_data($seminare_condition)));
    if($tmpfile){
        header('Location: ' . getDownloadLink( $tmpfile, _("LaufendeAnmeldeverfahren.xls"), 4));
        page_close();
        die;
    }
}

// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head
ob_end_flush();
?>
<table border=0 bgcolor="#000000" align="center" cellspacing="0" cellpadding="0" width="100%">
<?
if(count($msg)) parse_msg_array($msg, 'blank' , 1, false);
?>
<?
if(is_object($group_obj)){
    ?>
    <tr>
        <td class="blank" width="100%">
        <form action="<?=URLHelper::getLink()?>" name="Formular" method="post">
        <?= CSRFProtection::tokenTag() ?>
        <div class="steel1" style="margin:10px;padding:5px;border: 1px solid;">
        <div style="font-weight:bold;"><?=_("Gruppierte Veranstaltungen bearbeiten")?></div>
        <div>
        <?=_("Gruppierte Veranstaltungen müssen ein identisches Anmeldeverfahren benutzen.")?>
        <?=_("Alle Einstellungen die Sie an dieser Stelle vornehmen können, werden in allen Veranstaltungen dieser Gruppe gesetzt, wenn Sie die entsprechende Option auswählen.")?>
        <?=_("Veranstaltungen dieser Gruppe, die noch kein Anmeldeverfahren eingestellt haben, werden automatisch mit einem Kontingent für alle Studiengänge versehen.")?>
        <br><br>
        <b><?=_("Veranstaltungen in dieser Gruppe:")?></b>
        <ol>
        <?
        $distinct_members = array();
        foreach($group_obj->members as $member){
            $distinct_members += $member->getMembers('autor') + $member->getAdmissionMembers('awaiting') +  $member->getAdmissionMembers('accepted') + $member->getAdmissionMembers('claiming');?>
            <li>
                <?= $member->getNumber() ? htmlReady('('. $member->getNumber() .')') : '' ?>
                <?= htmlReady($member->getName() .' - ('. $member->getStartSemesterName() .')')?>
            </li>
            <input type="hidden" name="gruppe[]" value="<?=$member->getId();?>">
        <?}?>
        </ol>
        <ul style="list-style: none; margin:0px;padding:0px;">
        <? if(count($distinct_members)  > 0 ) :?>
        <li style="margin-top:5px;">
        <span style="display:block;float:left;width:200px;"><?=_("Anzahl aller Anmeldungen:")?></span><?=count($distinct_members)?>
        </li>
        <? endif;?>
        <li style="margin-top:5px;">
        <span style="display:block;float:left;width:200px;"><?=_("Name der Gruppe (optional):")?></span>
        <input type="text" name="admission_group_name" value="<?=htmlReady($group_obj->getValue('name'))?>" size="80" >
        </li>
        <li style="margin-top:5px;">
        <span style="display:block;float:left;width:200px;"><?=_("Typ der Gruppe:")?></span>
        <input style="vertical-align:top" type="radio" name="admission_group_status" <?=($group_obj->getValue('status') == 0 ? 'checked' : '')?> value="0">
        &nbsp;
        <?=_("Eintrag in einer Veranstaltung und einer Warteliste")?>
        &nbsp;
        <input style="vertical-align:top" type="radio" name="admission_group_status" <?=($group_obj->getValue('status') == 1 ? 'checked' : '')?> value="1">
        &nbsp;
        <?=_("Eintrag nur in einer Veranstaltung")?>
        </li>
        <li style="margin-top:5px;">
        <span style="display:block;float:left;width:200px;"><?=_("Anmeldeverfahren der Gruppe:")?></span>
        <input style="vertical-align:top" type="radio" onChange="semadmission_toggle_endtime();" id="admission_group_type_2" name="admission_group_type" <?=(!$group_obj->getUniqueMemberValue('admission_type') || $group_obj->getUniqueMemberValue('admission_type') == 2 ? 'checked' : '')?> value="2">
        &nbsp;
        <?=_("chronologische Anmeldung")?>
        &nbsp;
        <input style="vertical-align:top" type="radio" onChange="semadmission_toggle_endtime();" id="admission_group_type_1" name="admission_group_type" <?=($group_obj->getUniqueMemberValue('admission_type') == 1 ? 'checked' : '')?> value="1">
        &nbsp;
        <?=_("Losverfahren")?>
        </li>
        <hr>
        <li style="margin-top:5px;">
        <span style="display:block;float:left;width:200px;"><?=_("Prozentuale Kontingentierung:")?></span>
        <input style="vertical-align:top" type="checkbox" name="admission_change_enable_quota" value="1">
        &nbsp;
        <?=_("ändern")?>
        &nbsp;
        <?
        $group_admission_enable_quota = $group_obj->getUniqueMemberValue('admission_enable_quota');
        is_null($group_admission_enable_quota) OR settype($group_admission_enable_quota, 'integer');
        ?>
        <input style="vertical-align:top" onChange="semadmission_toggle_endtime();" type="radio" id="admission_enable_quota_1" name="admission_enable_quota" <?=($group_admission_enable_quota === 1 ? 'checked' : '')?> value="1">
        &nbsp;
        <?=_("aktiviert")?>
        &nbsp;
        <input style="vertical-align:top" onChange="semadmission_toggle_endtime();" type="radio" id="admission_enable_quota_0" name="admission_enable_quota" <?=($group_admission_enable_quota === 0 ? 'checked' : '')?> value="0">
        &nbsp;
        <?=_("deaktiviert")?>
        <?
        echo '&nbsp;(' . _("aktuelle Einstellung:") . '&nbsp;' . (!is_null($group_admission_enable_quota) ? _("identische Einstellung in allen Veranstaltungen") : _("unterschiedliche Einstellung in allen Veranstaltungen") ) . ')';
        ?>
        </li>
        <li style="margin-top:5px;">
        <span style="display:block;float:left;width:200px;"><?=_("Startdatum für Anmeldungen:")?></span>
        <input style="vertical-align:top" type="checkbox" name="admission_change_starttime" value="1">
        &nbsp;
        <?=_("ändern")?>
        &nbsp;
        <?
        $group_admission_start_date = $group_obj->getUniqueMemberValue('admission_starttime');
        ?>
        <input type="text" style="vertical-align:middle" name="adm_s_tag" size=2 maxlength=2 value="<? if (!is_null($group_admission_start_date) && $group_admission_start_date != -1) echo date("d",$group_admission_start_date); else echo _("tt") ?>">.
        <input type="text" style="vertical-align:middle" name="adm_s_monat" size=2 maxlength=2 value="<? if (!is_null($group_admission_start_date) && $group_admission_start_date != -1) echo date("m",$group_admission_start_date); else echo _("mm") ?>">.
        <input type="text" style="vertical-align:middle" name="adm_s_jahr" size=4 maxlength=4 value="<? if (!is_null($group_admission_start_date) && $group_admission_start_date != -1) echo date("Y",$group_admission_start_date); else echo _("jjjj") ?>">
        <?=_("um");?>
        &nbsp;<input type="text" style="vertical-align:middle"  name="adm_s_stunde" size=2 maxlength=2 value="<? if (!is_null($group_admission_start_date) && $group_admission_start_date != -1) echo date("H",$group_admission_start_date); else echo "00" ?>">:
        <input type="text" style="vertical-align:middle"  name="adm_s_minute" size=2 maxlength=2 value="<? if (!is_null($group_admission_start_date) && $group_admission_start_date != -1) echo date("i",$group_admission_start_date); else  echo "00" ?>">&nbsp;<?=_("Uhr");?>
        <?=Termin_Eingabe_javascript(20,0,(!is_null($group_admission_start_date) && $group_admission_start_date != -1 ? $group_admission_start_date : 0));
        echo '&nbsp;(' . _("aktuelle Einstellung:") . '&nbsp;' . (!is_null($group_admission_start_date) ? _("identisches Datum in allen Veranstaltungen") : _("unterschiedliches Datum in allen Veranstaltungen") ) . ')';
        ?>
        </li>
        <li style="margin-top:5px;">
        <span style="display:block;float:left;width:200px;"><?=_("Enddatum für Anmeldungen:")?></span>
        <input style="vertical-align:top" type="checkbox" name="admission_change_endtime_sem" value="1">
        &nbsp;
        <?=_("ändern")?>
        &nbsp;
        <?
        $group_admission_end_date = $group_obj->getUniqueMemberValue('admission_endtime_sem');
        ?>
        <input style="vertical-align:middle" type="text" name="adm_e_tag" size=2 maxlength=2 value="<? if (!is_null($group_admission_end_date) && $group_admission_end_date != -1) echo date("d",$group_admission_end_date); else echo _("tt") ?>">.
        <input style="vertical-align:middle"  type="text" name="adm_e_monat" size=2 maxlength=2 value="<? if (!is_null($group_admission_end_date) && $group_admission_end_date != -1) echo date("m",$group_admission_end_date); else echo _("mm") ?>">.
        <input style="vertical-align:middle" type="text" name="adm_e_jahr" size=4 maxlength=4 value="<? if (!is_null($group_admission_end_date) && $group_admission_end_date != -1) echo date("Y",$group_admission_end_date); else echo _("jjjj") ?>">
        <?=_("um");?>
        &nbsp;<input style="vertical-align:middle" type="text" name="adm_e_stunde" size=2 maxlength=2 value="<? if (!is_null($group_admission_end_date) && $group_admission_end_date != -1) echo date("H",$group_admission_end_date); else echo "23" ?>">:
        <input style="vertical-align:middle" type="text" name="adm_e_minute" size=2 maxlength=2 value="<? if (!is_null($group_admission_end_date) && $group_admission_end_date != -1) echo date("i",$group_admission_end_date); else  echo "59" ?>">&nbsp;<?=_("Uhr");?>
        <?=Termin_Eingabe_javascript(21,0,(!is_null($group_admission_end_date) && $group_admission_end_date != -1 ? $group_admission_end_date : 0));
        echo '&nbsp;(' . _("aktuelle Einstellung:") . '&nbsp;' . (!is_null($group_admission_end_date) ? _("identisches Datum in allen Veranstaltungen") : _("unterschiedliches Datum in allen Veranstaltungen") ) . ')';
        ?>
        </li>
        <?
        $no_admission_end = ($group_obj->getUniqueMemberValue('admission_type') != 1 && $group_admission_enable_quota === 0) ? 'disabled="disabled"' : '';
        $group_admission_end = ($group_admission_end = $group_obj->getUniqueMemberValue('admission_endtime')) || '-1';
        ?>
        <li id="admission_endtime" style="margin-top:5px;">
        <span style="display:block;float:left;width:200px;"><?=_("Ende Kontingente / Losdatum:")?></span>
        <input <?=$no_admission_end?> style="vertical-align:top" type="checkbox" <?=($group_admission_end == -1 ? 'checked' : '')?> name="admission_change_endtime" value="1">
        &nbsp;
        <?=_("ändern")?>
        &nbsp;
        <input <?=$no_admission_end?> type="text" style="vertical-align:middle" name="adm_tag" size=2 maxlength=2 value="<? if (!is_null($group_admission_end) && $group_admission_end != -1) echo date("d",$group_admission_end); else echo _("tt") ?>">.
        <input <?=$no_admission_end?> type="text" style="vertical-align:middle" name="adm_monat" size=2 maxlength=2 value="<? if (!is_null($group_admission_end) && $group_admission_end != -1) echo date("m",$group_admission_end); else echo _("mm") ?>">.
        <input <?=$no_admission_end?> type="text" style="vertical-align:middle" name="adm_jahr" size=4 maxlength=4 value="<? if (!is_null($group_admission_end) && $group_admission_end != -1) echo date("Y",$group_admission_end); else echo _("jjjj") ?>">
        <?=_("um");?>
        &nbsp;<input <?=$no_admission_end?> type="text" style="vertical-align:middle" name="adm_stunde" size=2 maxlength=2 value="<? if (!is_null($group_admission_end) && $group_admission_end != -1) echo date("H",$group_admission_end); else echo "23" ?>">:
        <input <?=$no_admission_end?> type="text" style="vertical-align:middle" name="adm_minute" size=2 maxlength=2 value="<? if (!is_null($group_admission_end) && $group_admission_end != -1) echo date("i",$group_admission_end); else  echo "59" ?>">&nbsp;<?=_("Uhr");?>
        <?=Termin_Eingabe_javascript(22,0,(!is_null($group_admission_end) && $group_admission_end != -1 ? $group_admission_end : 0));
        echo '&nbsp;(' . _("aktuelle Einstellung:") . '&nbsp;' . (!is_null($group_admission_end) ? _("identisches Datum in allen Veranstaltungen") : _("unterschiedliches Datum in allen Veranstaltungen") ) . ')';
        ?></li>
        <li style="margin-top:5px;">
        <span style="display:block;float:left;width:200px;"><?=_("max. Teilnehmer:")?></span>
        <input style="vertical-align:top" type="checkbox"  <?=(!is_null($group_obj->getUniqueMemberValue('admission_turnout')) && !$group_obj->getUniqueMemberValue('admission_turnout')  ? 'checked' : '')?> name="admission_change_turnout" value="1">
        &nbsp;
        <?=_("ändern")?>
        &nbsp;
        <input style="vertical-align:middle" name="admission_turnout" type="text" size="3" value="<?=$group_obj->getUniqueMemberValue('admission_turnout')?>">
        <?
        echo '&nbsp;(' . _("aktuelle Einstellung:") . '&nbsp;' . (!is_null($group_obj->getUniqueMemberValue('admission_turnout')) ? _("identische Anzahl in allen Veranstaltungen") : _("unterschiedliche Anzahl in allen Veranstaltungen") ) . ')';
        ?>
        </li>
        <li style="margin-top:5px;">
        <span style="padding-left:200px;">
        <?=makeButton('uebernehmen', 'input', _("Einstellungen übernehmen"), 'admissiongroupchange')?>
        &nbsp;
        <?=makeButton('loeschen', 'input', _("Gruppe auflösen"), 'admissiongroupdelete')?>
        &nbsp;
        <?=makeButton('abbrechen', 'input', _("Eingabe abbrechen"), 'admissiongroupcancel')?>
        </span>
        </li>
        </ul>
        </div>
        </div>
        <input type="hidden" name="group_sem_x" value="1">
        <?=(!$group_obj->isNew() ? '<input type="hidden" name="group_id" value="'.$group_obj->getId().'">' : '');?>
        </form>
        <script type="text/javascript">
        // <![CDATA[
        function semadmission_toggle_endtime(){
            admission_endtime_needed = $F('admission_group_type_1') == 1 || ($F('admission_group_type_2') == 2 && $F('admission_enable_quota_1') == 1);
            $('admission_endtime').select('input').collect(function(s){s.disabled = !admission_endtime_needed});
        }
        // ]]>
        </script>

    <?
} else {
    if (is_array($_my_inst)) {
    ?>
        <tr>
            <form action="<?=URLHelper::getLink()?>" method="post">
            <?= CSRFProtection::tokenTag() ?>
            <td class="blank" width="100%" >
                <div style="font-weight:bold;margin:10px;">
                <?=_("Bitte w&auml;hlen Sie eine Einrichtung aus:")?>
                </div>
                <div style="margin-left:10px;">
                <select name="institut_id" style="vertical-align:middle;">
                <?
                reset($_my_inst);
                while (list($key,$value) = each($_my_inst)){
                    printf ("<option %s value=\"%s\" style=\"%s\">%s (%s)</option>\n",
                        ($key == $_SESSION['show_admission']['institut_id']) ? "selected" : "" , $key,($value["is_fak"] ? "font-weight:bold;" : ""),
                        htmlReady($value["name"]), $value["num_sem"]);

                    if ($value["is_fak"] == 'all'){
                        $num_inst = $value["num_inst"];
                        for ($i = 0; $i < $num_inst; ++$i){
                            list($key,$value) = each($_my_inst);
                            printf("<option %s value=\"%s\">&nbsp;&nbsp;&nbsp;&nbsp;%s (%s)</option>\n",
                                ($key == $_SESSION['show_admission']['institut_id']) ? "selected" : "", $key,
                                htmlReady($value["name"]), $value["num_sem"]);
                        }
                    }
                }
                list($institut_id,) = explode('_', $_SESSION['show_admission']['institut_id']);
                if($institut_id == 'all') $institut_id = 'root';
                ?>
                    </select>&nbsp;
                    <?=SemesterData::GetSemesterSelector(array('name'=>'select_sem', 'style'=>'vertical-align:middle;'), $_SESSION['_default_sem'])?>
                    <?=makeButton("auswaehlen","input",_("Einrichtung auswählen"), "choose_institut")?>
                </div>
                <div style="margin:10px;">
                <b><?=_("Angezeigte Veranstaltungen einschränken:")?></b>
                <span style="margin-left:10px;">
                <input type="checkbox" name="check_admission" <?=$_SESSION['show_admission']['check_admission'] ? 'checked' : ''?> value="1" style="vertical-align:middle;">&nbsp;<?=_("Anmeldeverfahren")?>
                <input type="checkbox" name="check_prelim" <?=$_SESSION['show_admission']['check_prelim'] ? 'checked' : ''?> value="1" style="vertical-align:middle;">&nbsp;<?=_("vorläufige Teilnahme")?>
                </span>
                </div>
                <div style="margin-top:10px;margin-left:10px;">
                <b><?=_("Präfix des Veranstaltungsnamens:")?></b>
                <span style="margin-left:10px;">
                <input type="test" name="sem_name_prefix" value="<?=htmlReady($_SESSION['show_admission']['sem_name_prefix'])?>" style="vertical-align:middle;" size="20">
                </span>
                </div>
                <div style="margin-bottom: 5px;margin-right:5px;" align="right">
                <a href="<?=URLHelper::getLink('?cmd=send_excel_sheet')?>">
                <?= Assets::img('icons/16/blue/file-xls.png', array('class' => 'text-top')) ?>
                <?= _("Download als Excel Datei") ?></a></div>
            </td>
            </form>
        </tr>
<?}?>
    <tr>
        <td class="blank">
<?
    $data = semadmission_get_data($seminare_condition);
    $tag = 0;
    if (count($data)) {
        echo "\n<table width=\"99%\" align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">";
        echo "\n<tr style=\"font-size:80%\">";
                foreach($cols as $col){
                    echo "\n<th width=\"{$col[0]}%\" style=\"white-space:nowrap;\">";
                    if($col[1]){
                        echo '<a class="tree" href="' . URLHelper::getLink('?sortby='. $col[2]). '">'.$col[1];
                        if($col[2] == $_SESSION['show_admission']['sortby']['field']){
                            echo Assets::img($_SESSION['show_admission']['sortby']['direction'] ? 'dreieck_up.png' : 'dreieck_down.png', array('style' => 'vertical-align:middle;'));
                        }
                        echo '</a>';
                    }
                    echo "\n</th>";
                }
                echo "\n</tr>";
        printf("\n<form action=\"%s\" method=\"post\">\n",URLHelper::getLink());
        echo CSRFProtection::tokenTag();
    } elseif ($institut_id) {
        echo "\n<table width=\"99%\" align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">";
        parse_msg ("info§"._("Im gew&auml;hlten Bereich existieren keine teilnahmebeschr&auml;nkten Veranstaltungen")."§", "§", "steel1",2, FALSE);
    }
    foreach($data as $seminar_id => $semdata) {
        $teilnehmer = $semdata['count_teilnehmer'];
        if($teilnehmer){
            $teilnehmer .= '&nbsp;<a href="'.URLHelper::getLink('export.php', array('range_id' => $seminar_id, 'ex_type' => 'person', 'xslt_filename' => _("TeilnehmerInnen") . ' '. $semdata['Name'],'format' => 'csv', 'choose' => 'csv-teiln', 'o_mode'=> 'passthrough')).'">';
            $teilnehmer .= Assets::img('icons/16/blue/download.png', tooltip(_("Teilnehmerliste downloaden"))) .'</a>';
        }
        $cssSw->switchClass();
        $teilnehmer_aux = $semdata['count_teilnehmer_aux'];
        $quota = $semdata['admission_turnout'];
        $count2 = $semdata['count_anmeldung'];
        if($count2){
            $count2 .= '&nbsp;<a href="'.URLHelper::getLink('export.php', array('range_id' => $seminar_id, 'ex_type' => 'person', 'xslt_filename' => _("Anmeldungen") . ' '. $semdata['Name'], 'format' => 'csv', 'choose' => 'csv-warteliste', 'filter' => 'accepted', 'o_mode' => 'passthrough')).'">';
            $count2 .= Assets::img('icons/16/blue/download.png', tooltip(_("Anmeldeliste downloaden"))) .'</a>';
        }
        $count3 = $semdata['count_wartende'];
        if($count3){
            $count3 .= '&nbsp;<a href="'.URLHelper::getLink('export.php', array('range_id' => $seminar_id, 'ex_type' => 'person', 'xslt_filename' => _("Warteliste") . ' '. $semdata['Name'], 'format' => 'csv', 'choose' =>'csv-warteliste', 'filter' => 'awaiting', 'o_mode' => 'passthrough')).'">';
            $count3 .= Assets::img('icons/16/blue/download.png', tooltip(_("Warteliste downloaden"))) .'</a>';
        }
        // show end date only if it is actually relevant
        $datum = $semdata['admission_type'] == 1 || $semdata['admission_type'] == 2 && $semdata['admission_enable_quota'] ? $semdata['admission_endtime'] : -1;
        $status = $semdata['admission_status_text'];
        echo "<tr>";
        if ($ALLOW_GROUPING_SEMINARS) {
            if (!$semdata['admission_group']) {
                printf("<td class=\"%s\" align=\"center\">",$cssSw->getClass());
                unset($last_group);
                printf("<input type=\"checkbox\" name=\"gruppe[]\" value=\"%s\">",$seminar_id);
                echo '</td>';
            } else {
                echo '<td class="'.$cssSw->getClass().'" align="center">';
                printf("<a title=\"%s\" href=\"".URLHelper::getLink('show_admission.php',array('group_id' => $semdata['admission_group'], 'group_sem_x'=>1))."\">%s</a>",
                    _("Gruppe bearbeiten"), htmlReady($semdata['groupname']));
                echo '</td>';
            }
        }

        $url = getUrlToSeminar($semdata);

        printf ("<td class=\"%s\">
        <a title=\"%s\" href=\"$url\">
                %s%s%s
                </a></td>
                <td class=\"%s\" align=\"center\">
                <a title=\"%s\" href=\"".URLHelper::getLink('admin_admission.php?select_sem_id=%s')."\">%s</a></td>
                <td class=\"%s\" align=\"center\">%s</td>
                <td class=\"%s\" align=\"center\">%s</td>
                <td class=\"%s\" align=\"center\">%s</td>
                <td class=\"%s\" align=\"center\">%s</td>
                <td class=\"%s\" align=\"center\">%s</td>
                <td class=\"%s\" align=\"center\">%s</td>
                <td class=\"%s\" align=\"center\">%s</td>
                <td class=\"%s\" align=\"center\">%s</td>",
                $cssSw->getClass(),
                _("Teilnehmerliste aufrufen"),
                $semdata['VeranstaltungsNummer'] ? htmlready('('. $semdata['VeranstaltungsNummer'] . ')') .' ' : '',
                htmlready(substr($semdata['Name'], 0, 50)), (strlen($semdata['Name'])>50) ? "..." : "",
                $cssSw->getClass(),
                _("Zugangsbeschränkungen aufrufen"),
                $seminar_id,
                $status,
                $cssSw->getClass(),
                $teilnehmer,
                $cssSw->getClass(),
                $teilnehmer_aux,
                $cssSw->getClass(),
                $quota,
                $cssSw->getClass(),
                $count2,
                $cssSw->getClass(),
                $count3,
                ($datum != -1) ? ($datum < time() ? "steelgroup4" : "steelgroup1") : $cssSw->getClass(),
                ($datum != -1) ? date("d.m.Y, G:i", $datum) : "",
                ($semdata['admission_starttime'] != -1) ? ($semdata['admission_starttime'] < time() ? "steelgroup4" : "steelgroup1") : $cssSw->getClass(),
                ($semdata['admission_starttime'] != -1) ? date("d.m.Y, G:i", $semdata['admission_starttime']) : "",
                ($semdata['admission_endtime_sem']!= -1) ? ($semdata['admission_endtime_sem'] < time() ? "steelgroup4" : "steelgroup1") : $cssSw->getClass(),
                ($semdata['admission_endtime_sem'] != -1) ? date("d.m.Y, G:i", $semdata['admission_endtime_sem']) : "");
        print ("</tr>");
    }

    if (count($data) && $ALLOW_GROUPING_SEMINARS) {
        echo '<tr><td align="left" colspan="2">'. "\n";
        echo makeButton("gruppieren", 'input', _("Markierte Veranstaltungen gruppieren"), 'group_sem');
        echo "</form>\n";
        echo "</td></tr>\n";
    }
    echo "</table>";
}
?>
<br>&nbsp;
</td>
</tr>
</table>
<?php
include ('lib/include/html_end.inc.php');
page_close();

/**
 * Returns the URL to the participants page of the seminar.
 * If no participants page is available the URL to the main page is returned.
 * @param  $seminar_id the id of the seminar
 * @return string the url
 */
function getUrlToSeminar($semdata)
{
    $modules = new Modules();
    $activated_modules = $modules->getLocalModules($semdata['Seminar_id'], 'sem', $semdata['modules'], $semdata['status']);
    if ($activated_modules["participants"]) {
        return URLHelper::getLink("teilnehmer.php?cid=". $semdata['Seminar_id']);
    }
    else {
        return URLHelper::getLink("seminar_main.php?cid=" . $semdata['Seminar_id']);
    }
}

?>
