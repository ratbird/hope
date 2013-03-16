<?php
# Lifter001: TEST
# Lifter002: TODO
# Lifter003: TEST
# Lifter005: TODO
# Lifter007: TODO
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

use Studip\Button, Studip\LinkButton;

require '../lib/bootstrap.php';

unregister_globals();

ob_start();
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check("admin");


// Set this to something, just something different...
$hash_secret = "trubatik";
$msg = array();

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page
PageLayout::setTitle(_("�bersicht laufender Anmeldeverfahren / Grupppierung von Veranstaltungen"));
Navigation::activateItem('/tools/show_admission');

require_once('config.inc.php'); //Grunddaten laden
require_once('lib/visual.inc.php'); //htmlReady
require_once('lib/admission.inc.php');

function semadmission_get_data($seminare_condition){
    global $perm;
    $ret = array();
    $sorter = array();
    list($institut_id, $all) = explode('_', $_SESSION['show_admission']['institut_id']);
    
    // Prepare count statements
    $query = "SELECT SUM(admission_studiengang_id <> '') AS t1,
                     SUM(admission_studiengang_id = '') AS t2
              FROM seminar_user
              WHERE seminar_id = ? AND status IN ('user', 'autor')";
    $count0_statement = DBManager::get()->prepare($query);

    $query = "SELECT SUM(status = 'accepted') AS count2,
                     SUM(status IN ('claiming', 'awaiting')) AS count3
              FROM admission_seminar_user
              WHERE seminar_id = ?
              GROUP BY seminar_id";
    $count1_statement = DBManager::get()->prepare($query);

    // Prepare group name statement
    $query = "SELECT name FROM admission_group WHERE group_id = ?";
    $name_statement = DBManager::get()->prepare($query);

    // Prepare seminar statement
    $parameters = array();
    if ($institut_id == 'all'  && $perm->have_perm('root')) {
        $query = "SELECT *
                  FROM seminare
                  WHERE 1 {$seminare_condition}
                  ORDER BY admission_group DESC, start_time DESC, Name";
    } elseif ($all == 'all') {
        $query = "SELECT seminare.*
                  FROM seminare
                  JOIN Institute USING (Institut_id)
                  WHERE Institute.fakultaets_id = ? {$seminare_condition}
                  GROUP BY seminare.Seminar_id
                  ORDER BY admission_group DESC, start_time DESC, Name";
        $parameters[] = $institut_id;
    } else {
        $query = "SELECT seminare.*
                  FROM seminare
                  WHERE seminare.Institut_id = ? {$seminare_condition}
                  GROUP BY seminare.Seminar_id
                  ORDER BY admission_group DESC, start_time DESC, Name";
        $parameters[] = $institut_id;
    }
    $statement = DBManager::get()->prepare($query);
    $statement->execute($parameters);

    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $seminar_id = $row['Seminar_id'];
        $ret[$seminar_id] = $row;

        $count0_statement->execute(array($seminar_id));
        $counts = $count0_statement->fetch(PDO::FETCH_ASSOC);
        $count0_statement->closeCursor();

        $ret[$seminar_id]['count_teilnehmer']     = 0 + $counts['t1'];
        $ret[$seminar_id]['count_teilnehmer_aux'] = 0 + $counts['t2'];

        $count1_statement->execute(array($seminar_id));
        $counts = $count1_statement->fetch(PDO::FETCH_ASSOC);
        $count1_statement->closeCursor();

        $ret[$seminar_id]['count_anmeldung'] = $counts['count2'];
        $ret[$seminar_id]['count_wartende']  = $counts['count3'];

        $status = array();
        if ($row['admission_type'] == 3) {
            $status[] = _('gesperrt');
        } else if ($row['admission_type'] == 2) {
            $status[] = _('Chronologisch');
        } else if ($row['admission_type'] == 1) {
            $status[] = _('Losverfahren');
        } else if ($row['admission_type'] == 0) {
            $status[] = _("kein Anmeldeverfahren");
        }
        if ($row['admission_prelim']) {
            $status[] = _('vorl�ufig');
        }
        $ret[$seminar_id]['admission_status_text'] = join('/', $status);
        if ($row['admission_group']) {
            if ($row['admission_group'] != $last_group) {
                unset($last_group);
            }
            if (!isset($last_group)) {
                $last_group = $row['admission_group'];
                
                $name_statement->execute(array($last_group));
                $groupname = $name_statement->fetchColumn();
                $name_statement->closeCursor();

                if (!$groupname) {
                    $groupname = _('Gruppe') . $groupcount;
                    $groupcount += 1;
                }
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
        $worksheet1->write_string($row,4, _("zus�tzliche Teilnehmer"), $caption_format);
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

function semadmission_get_institute($seminare_condition) {
    global $perm, $user;

    // Prepare institute statement
    $query = "SELECT a.Institut_id, a.Name, COUNT(seminar_id) AS num_sem
              FROM Institute AS a
              LEFT JOIN seminare ON (seminare.Institut_id = a.Institut_id {$seminare_condition})
              WHERE fakultaets_id = ? AND a.Institut_id != fakultaets_id
              GROUP BY a.Institut_id
              ORDER BY a.Name, num_sem DESC";
    $institute_statement = DBManager::get()->prepare($query);

    $parameters = array();
    if ($perm->have_perm('root')) {
        $query = "SELECT COUNT(*) FROM seminare WHERE 1 {$seminare_condition}";
        $statement = DBManager::get()->query($query);
        $num_sem = $statement->fetchColumn();

        $_my_inst['all'] = array(
            'name'    => _('alle'),
            'num_sem' => $num_sem
        );
        $query = "SELECT a.Institut_id, a.Name, 1 AS is_fak, COUNT(seminar_id) AS num_sem
                  FROM Institute AS a
                  LEFT JOIN seminare ON (seminare.Institut_id = a.Institut_id {$seminare_condition})
                  WHERE a.Institut_id = fakultaets_id
                  GROUP BY a.Institut_id
                  ORDER BY is_fak, Name, num_sem DESC";
    } else {
        $query = "SELECT a.Institut_id, b.Name, b.Institut_id = b.fakultaets_id AS is_fak,
                         COUNT(seminar_id) AS num_sem
                  FROM user_inst AS s
                  LEFT JOIN Institute AS b USING (Institut_id)
                  LEFT JOIN seminare ON (seminare.Institut_id = b.Institut_id {$seminare_condition})
                  WHERE a.user_id = ? AND a.inst_perms = 'admin'
                  GROUP BY a.Institut_id
                  ORDER BY is_fak, Name, num_sem DESC";
        $parameters[] = $user->id;
    }
    $statement = DBManager::get()->prepare($query);
    $statement->execute($parameters);
    $temp = $statement->fetchAll(PDO::FETCH_ASSOC);

    foreach ($temp as $row) {
        $_my_inst[$row['Institut_id']] = array(
            'name'    => $row['Name'],
            'is_fak'  => $row['is_fak'],
            'num_sem' => $Row['num_sem']
        );
        if ($row["is_fak"]) {
            $_my_inst[$row['Institut_id'] . '_all'] = array(
                'name'    => sprintf(_('[Alle unter %s]'), $row['Name']),
                'is_fak'  => 'all',
                'num_sem' => $row['num_sem']
            );

            $num_inst = 0;
            $num_sem_alle = $row['num_sem'];

            $institute_statement->execute(array($row['Institut_id']));
            while ($institute = $institute_statement->fetch(PDO::FETCH_ASSOC)) {
                if(!$_my_inst[$institute['Institut_id']]) {
                    $num_inst += 1;
                    $num_sem_alle += $institute['num_sem'];
                }
                $_my_inst[$institute['Institut_id']] = array(
                    'name'    => $institute['Name'],
                    'is_fak'  => 0,
                    'num_sem' => $institute["num_sem"]
                );
            }
            $_my_inst[$row['Institut_id']]['num_inst']          = $num_inst;
            $_my_inst[$row['Institut_id'] . '_all']['num_inst'] = $num_inst;
            $_my_inst[$row['Institut_id'] . '_all']['num_sem']  = $num_sem_alle;
        }
    }
    return $_my_inst;
}

$sem_condition = '';
$admission_condition = array();

$cols = array();
if ($ALLOW_GROUPING_SEMINARS) {
    $cols[] = array(1,_("Gruppe"),'admission_group');
}
$cols[] = array(25,_("Veranstaltung"),'Name');
$cols[] = array(10,_("Status"),'admission_status_text');
$cols[] = array(10,_("Anz. Kontingent") ,'count_teilnehmer');
$cols[] = array(10,_("Anz. zus�tzlich"),'count_teilnehmer_aux');
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

if(Request::submitted('choose_institut')){
    if(Request::get('select_sem')){
        $_SESSION['_default_sem'] = Request::get('select_sem');
    }
    $_SESSION['show_admission']['check_admission'] = Request::option('check_admission');
    $_SESSION['show_admission']['check_prelim'] = Request::option('check_prelim');
    $_SESSION['show_admission']['sem_name_prefix'] = trim(Request::get('sem_name_prefix'));
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
    if(Request::option('institut_id')){
        $_SESSION['show_admission']['institut_id'] = ($_my_inst[Request::option('institut_id')]) ? Request::option('institut_id') : $_my_inst_arr[0];
    }
}
if(Request::submitted('admissiongroupdelete') && Request::option('group_id')){
    $msg[] = array('info', _("Wollen Sie die Gruppierung f&uuml;r die ausgew&auml;hlte Gruppe aufl&ouml;sen?")
                            . '<br>' . _("Beachten Sie, dass f&uuml;r bereits eingetragene / auf der Warteliste stehende TeilnehmerInnen keine &Auml;nderungen vorgenommen werden.")
                            . '<form action="'.URLHelper::getLink().'" method="post">'
                            . CSRFProtection::tokenTag()
                            . '<input type="hidden" name="group_sem_x" value="1"><div style="padding:3px;">'
                            . '<input type="hidden" name="group_id" value="'.Request::option('group_id').'">'
                            . Button::createAccept(_('JA!'), 'admissiongroupreallydelete', array('title' => _("Gruppe aufl�sen")))
                            . '&nbsp;'
                            . Button::createCancel(_('NEIN!'), array('title' => _('abbrechen')))
                            . '</div></form>');
}
$gruppe = Request::quotedArray('gruppe');
if(Request::submitted('group_sem') && ($gruppe > 1 || Request::option('group_id')) && !Request::submitted('admissiongroupcancel')){
    if(Request::option('group_id')){
            $group_obj = new StudipAdmissionGroup(Request::option('group_id'));
    } else {
        $group_obj = new StudipAdmissionGroup();
        
        foreach($gruppe as $sid){
            $group_obj->addMember($sid);
        }
    }
    if(Request::submitted('admissiongroupchange')){
        $group_obj->setValue('name', trim(Request::get('admission_group_name')));
        $group_obj->setValue('status', (int)Request::int('admission_group_status'));
        $group_obj->setUniqueMemberValue('admission_type', (int)Request::int('admission_group_type'));
        $group_obj->setUniqueMemberValue('read_level', 3);
        $group_obj->setUniqueMemberValue('write_level', 3);
        $admission_times = array();
        $ok = true;
        $do_admission_update = false;
        if(Request::get('admission_change_enable_quota')){
            $group_obj->setUniqueMemberValue('admission_enable_quota', Request::quoted('admission_enable_quota'));
        }
        if(Request::get('admission_change_endtime')){
            $admission_times["admission_endtime"] = '-1';
            if (!check_and_set_date(Request::option('adm_tag'), Request::option('adm_monat'), Request::option('adm_jahr'), Request::option('adm_stunde'), Request::option('adm_minute'), $admission_times, "admission_endtime")) {
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
                                $msg[] = array('error', sprintf(_("Das Ende Kontingente / Losdatum kann nicht vor dem Startdatum f�r Anmeldungen in der Veranstaltung <b>%s</b> liegen."), htmlReady($group_obj->members[$semid]->getName())));
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
                            $msg[] = array('error', sprintf(_("Das Ende Kontingente / Losdatum kann in der Veranstaltung <b>%s</b> nicht ge�ndert werden, da das Losen bereits durchgef�hrt wurde / die Kontingentierung bereits aufgehoben wurde."), htmlReady($group_obj->members[$semid]->getName())));
                            $ok = false;
                        }
                    }
                }
            }
        }
        if(Request::get('admission_change_starttime')){
            $admission_times["admission_starttime"] = -1;
            if (!check_and_set_date(Request::option('adm_s_tag'), Request::option('adm_s_monat'), Request::option('adm_s_jahr'), Request::option('adm_s_stunde'), Request::option('adm_s_minute'), $admission_times, "admission_starttime")) {
                $msg[] = array("error", _("Bitte geben Sie g&uuml;ltige Zeiten f&uuml;r das Startdatum f�r Anmeldungen ein!"));
                $ok = false;
            }
        }
        if(Request::get('admission_change_endtime_sem')){
            $admission_times["admission_endtime_sem"] = -1;
            if (!check_and_set_date(Request::option('adm_e_tag'), Request::option('adm_e_monat'), Request::option('adm_e_jahr'), Request::option('adm_e_stunde'), Request::option('adm_e_minute'), $admission_times, "admission_endtime_sem")) {
                $msg[] = array("error", _("Bitte geben Sie g&uuml;ltige Zeiten f&uuml;r das Enddatum f�r Anmeldungen ein!"));
                $ok = false;
            }
        }
        if(Request::get('admission_change_turnout')){
            if(Request::quoted('admission_turnout') < 1){
                $msg[] = array("error" , _("Wenn Sie die Teilnahmebeschr&auml;nkung benutzen wollen, m&uuml;ssen Sie wenigstens einen Teilnehmer zulassen."));
                $ok = false;
            }
        }
        if($ok){
            if($admission_times["admission_endtime"] > 1){
                $group_obj->setUniqueMemberValue('admission_endtime', $admission_times["admission_endtime"]);
                $msg[] = array('msg', sprintf(_("Das Ende Kontingente / Losdatum wurde in allen Veranstaltungen ge�ndert.")));
            }
            if($admission_times["admission_starttime"] > 1){
                $group_obj->setUniqueMemberValue('admission_starttime', $admission_times["admission_starttime"]);
                $msg[] = array('msg', sprintf(_("Das Startdatum f�r Anmeldungen wurde in allen Veranstaltungen ge�ndert.")));
            }
            if($admission_times["admission_endtime_sem"] > 1){
                $group_obj->setUniqueMemberValue('admission_endtime_sem', $admission_times["admission_endtime_sem"]);
                $msg[] = array('msg', sprintf(_("Das Enddatum f�r Anmeldungen wurde in allen Veranstaltungen ge�ndert.")));
            }
            if(Request::get('admission_change_turnout')){
                $do_admission_update = (int)Request::int('admission_turnout') >= $group_obj->getUniqueMemberValue('admission_turnout');
                $group_obj->setUniqueMemberValue('admission_turnout', (int)Request::int('admission_turnout'));
                $msg[] = array('msg', sprintf(_("Die Teilnehmeranzahl wurde in allen Veranstaltungen auf %s ge�ndert."),(int)Request::int('admission_turnout')));
            }

        }
        foreach($group_obj->getMemberIds() as $semid){
            if( $group_obj->members[$semid]->isAdmissionQuotaChecked() && $group_obj->members[$semid]->admission_endtime < 1 ){
                $msg[] = array('error', sprintf(_("Die Veranstaltung <b>%s</b> hat keinen Eintrag f�r Ende Kontingente / Losdatum. Gruppierung nicht m�glich."), htmlReady($group_obj->members[$semid]->getName())));
                $ok = false;
            }
            if( $group_obj->members[$semid]->admission_turnout < 1 ){
                $msg[] = array('error', sprintf(_("Die Veranstaltung <b>%s</b> hat keinen Eintrag f�r Teilnehmeranzahl. Gruppierung nicht m�glich."), htmlReady($group_obj->members[$semid]->getName())));
                $ok = false;
            }
        }
        if($ok){
            if($group_obj->getValue('status') == 0 && $group_obj->getUniqueMemberValue('admission_type') == 1){
                $group_obj->setValue('status', 1);
                $msg[] = array('info', _("Das Losverfahren kann nur mit der Einstellung <b>Eintrag nur in einer Veranstaltung</b> kombiniert werden."));
            }
            if($group_obj->getUniqueMemberValue('admission_type') == 2 && $group_obj->getUniqueMemberValue('admission_enable_quota') == 0) {
                $group_obj->setUniqueMemberValue('admission_endtime', -1);
                $group_obj->setUniqueMemberValue('admission_selection_take_place', 0);
            }
            if($group_obj->store()){
                $msg[] = array('msg', sprintf(_("Die Gruppe wurde erstellt / modifiziert.")));
            }
            $contingent = $group_obj->setMinimumContingent();
            if(count($contingent)){
                foreach($contingent as $sem_id) $sem_names[] = $group_obj->members[$sem_id]->getName();
                $msg[] = array('msg', sprintf(_("In den Veranstaltungen <b>%s</b> wurde ein Kontingent mit 100%% f�r alle Studieng�nge eingerichtet."), htmlready(join(", ", $sem_names))));
            }
            if($do_admission_update){
                foreach($group_obj->getMemberIds() as $semid){
                    update_admission($semid);
                }
                $msg[] = array('msg', sprintf(_("Nachr�cken in allen Veranstaltungen der Gruppe durchgef�hrt.")));
            }
        }
    }
    if(Request::submitted('admissiongroupreallydelete')){
        if($group_obj->delete()){
            $msg[] = array('msg', sprintf(_("Die Gruppe wurde aufgel�st.")));
            unset($group_obj);
        }
    }
}

if(Request::get('sortby')){
    foreach($cols as $col){
        if(Request::quoted('sortby') == $col[2]){
            if($_SESSION['show_admission']['sortby']['field'] == Request::quoted('sortby')){
                $_SESSION['show_admission']['sortby']['direction'] = (int)!$_SESSION['show_admission']['sortby']['direction'];
            } else {
                $_SESSION['show_admission']['sortby']['field'] = Request::quoted('sortby');
                $_SESSION['show_admission']['sortby']['direction'] = 0;
            }
            break;
        }
    }
}

if (Request::option('cmd') == 'send_excel_sheet'){
    $tmpfile = basename(semadmission_create_result_xls(semadmission_get_data($seminare_condition)));
    if($tmpfile){
        header('Location: ' . getDownloadLink( $tmpfile, _("LaufendeAnmeldeverfahren.xls"), 4));
        page_close();
        die;
    }
}
if (in_array(Request::get('cmd') , words('download_all_members download_multi_members')) && $group_obj = StudipAdmissionGroup::find(Request::option('group_id'))) {
    $liste = array();
    $multi_members = $all_participants = array();
    foreach($group_obj->members as $member){
        $participants = $member->getMembers('user') + $member->getMembers('autor') + $member->getAdmissionMembers('awaiting') +  $member->getAdmissionMembers('accepted') + $member->getAdmissionMembers('claiming');
        $all_participants += $participants;
        foreach (array_keys($participants) as $one) {
            $multi_members[$one][] = $member->getName() . ($member->getNumber() ? ' '. $member->getNumber() : '');
        }
        foreach($participants as $user_id => $part) {
            $liste[] = array($part['username'], $part['Vorname'], $part['Nachname'], $part['Email'], $member->getName() . ($member->getNumber() ? ' '. $member->getNumber() : ''), $part['status']);
        }
    }
    if (Request::get('cmd') == 'download_all_members') {
        $caption = array(_("Nutzername"), _("Vorname"), _("Nachname"), _("Email"), _("Veranstaltung"), _("Status"));
        if (count($liste)) {
            $tmpfile = $GLOBALS['TMP_PATH'] . '/' . md5(uniqid('write_excel',1));
            array_to_csv($liste, $tmpfile, $caption);
            header('Location: ' . getDownloadLink( basename($tmpfile), _("Gruppenteilnehmerliste.csv"), 4));
            page_close();
            die;
        }
    } else {
        $liste = array();
        $multi_members = array_filter($multi_members, create_function('$a', 'return count($a) > 1;'));
        $c = 0;
        $max_count = array();
        foreach($multi_members as $user_id => $courses) {
            $member = $all_participants[$user_id];
            $liste[$c] = array($member['username'], $member['Vorname'], $member['Nachname'], $member['Email']);
            foreach ($courses as  $one) {
                $liste[$c][] = $one;
            }
            $max_count[] = count($courses);
            $c++;
        }
        $caption = array(_("Nutzername"), _("Vorname"), _("Nachname"), _("Email"));
        foreach(range(1,max($max_count)) as $num) {
            $caption[] = _("Veranstaltung") . ' ' . $num;
        }
        if (count($liste)) {
            $tmpfile = $GLOBALS['TMP_PATH'] . '/' . md5(uniqid('write_excel',1));
            array_to_csv($liste, $tmpfile, $caption);
            header('Location: ' . getDownloadLink( basename($tmpfile), _("Gruppenmehrfacheintr�ge.csv"), 4));
            page_close();
            die;
        }
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
        <div class="table_row_even" style="margin:10px;padding:5px;border: 1px solid;">
        <div style="font-weight:bold;"><?=_("Gruppierte Veranstaltungen bearbeiten")?></div>
        <div>
        <?=_("Gruppierte Veranstaltungen m�ssen ein identisches Anmeldeverfahren benutzen.")?>
        <?=_("Alle Einstellungen die Sie an dieser Stelle vornehmen k�nnen, werden in allen Veranstaltungen dieser Gruppe gesetzt, wenn Sie die entsprechende Option ausw�hlen.")?>
        <?=_("Veranstaltungen dieser Gruppe, die noch kein Anmeldeverfahren eingestellt haben, werden automatisch mit einem Kontingent f�r alle Studieng�nge versehen.")?>
        <br><br>
        <b><?=_("Veranstaltungen in dieser Gruppe:")?></b>
        <ol>
        <?
        $distinct_members = array();
        $multi_members = array();
        foreach($group_obj->members as $member){
            $all_members = $member->getMembers('user') + $member->getMembers('autor') + $member->getAdmissionMembers('awaiting') +  $member->getAdmissionMembers('accepted') + $member->getAdmissionMembers('claiming');
            foreach (array_keys($all_members) as $one) {
                $multi_members[$one]++;
            }
            $distinct_members = array_merge($distinct_members,array_keys($all_members));
            ?><li>
                <?= $member->getNumber() ? htmlReady('('. $member->getNumber() .')') : '' ?>
                <?= htmlReady($member->getName() .' - ('. $member->getStartSemesterName() .')')?>
            </li>
            <input type="hidden" name="gruppe[]" value="<?=$member->getId();?>">
        <?}
        $multi_members = array_filter($multi_members, create_function('$a', 'return $a > 1;'));
        ?>
        </ol>
        <ul style="list-style: none; margin:0px;padding:0px;">
        <? if(count($distinct_members)  > 0 ) :?>
        <li style="margin-top:5px;">
        <span style="display:block;float:left;width:200px;"><?=_("Anzahl aller Anmeldungen:")?></span><?=count($distinct_members)?>
        <a href="<?php echo UrlHelper::getLink('', array('group_id' => $group_obj->getId(), 'cmd' => 'download_all_members'))?>" title="<?php echo _("Download")?>"><?php echo Assets::img('icons/16/blue/file-xls.png', array('style' => 'vertical-align:bottom'))?></a>
        </li>
        <? endif;?>
        <? if(count($multi_members)  > 0 ) :?>
        <li style="margin-top:5px;">
        <span style="display:block;float:left;width:200px;"><?=_("Mehrfachanmeldungen:")?></span><?=count($multi_members)?>
        <a href="<?php echo UrlHelper::getLink('', array('group_id' => $group_obj->getId(), 'cmd' => 'download_multi_members'))?>" title="<?php echo _("Download")?>"><?php echo Assets::img('icons/16/blue/file-xls.png', array('style' => 'vertical-align:bottom'))?></a>
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
        <li style="margin-top:5px;" class="semadmission_toggle_endtime">
        <span style="display:block;float:left;width:200px;"><?=_("Anmeldeverfahren der Gruppe:")?></span>
        <input style="vertical-align:top" type="radio" name="admission_group_type" <?=($group_obj->getUniqueMemberValue('admission_type') != 1? 'checked' : '')?> value="2">
        &nbsp;
        <?=_("chronologische Anmeldung")?>
        &nbsp;
        <input style="vertical-align:top" type="radio" name="admission_group_type" <?=($group_obj->getUniqueMemberValue('admission_type') == 1 ? 'checked' : '')?> value="1">
        &nbsp;
        <?=_("Losverfahren")?>
        </li>
        <hr>
        <li style="margin-top:5px;" class="semadmission_toggle_endtime semadmission_changeable">
        <span style="display:block;float:left;width:200px;"><?=_("Prozentuale Kontingentierung:")?></span>
        <input style="vertical-align:top" type="checkbox" name="admission_change_enable_quota" value="1">
        &nbsp;
        <?=_("�ndern")?>
        &nbsp;
        <?
        $group_admission_enable_quota = $group_obj->getUniqueMemberValue('admission_enable_quota');
        is_null($group_admission_enable_quota) OR settype($group_admission_enable_quota, 'integer');
        ?>
        <input style="vertical-align:top" type="radio" name="admission_enable_quota" <?=($group_admission_enable_quota === 1 ? 'checked' : '')?> value="1">
        &nbsp;
        <?=_("aktiviert")?>
        &nbsp;
        <input style="vertical-align:top" type="radio" name="admission_enable_quota" <?=($group_admission_enable_quota === 0 ? 'checked' : '')?> value="0">
        &nbsp;
        <?=_("deaktiviert")?>
        <?
        echo '&nbsp;(' . _("aktuelle Einstellung:") . '&nbsp;' . (!is_null($group_admission_enable_quota) ? _("identische Einstellung in allen Veranstaltungen") : _("unterschiedliche Einstellung in allen Veranstaltungen") ) . ')';
        ?>
        </li>
        <li style="margin-top:5px;" class="semadmission_changeable">
        <span style="display:block;float:left;width:200px;"><?=_("Startdatum f�r Anmeldungen:")?></span>
        <input style="vertical-align:top" type="checkbox" name="admission_change_starttime" value="1">
        &nbsp;
        <?=_("�ndern")?>
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
        <li style="margin-top:5px;" class="semadmission_changeable">
        <span style="display:block;float:left;width:200px;"><?=_("Enddatum f�r Anmeldungen:")?></span>
        <input style="vertical-align:top" type="checkbox" name="admission_change_endtime_sem" value="1">
        &nbsp;
        <?=_("�ndern")?>
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
        $group_admission_end = $group_obj->getUniqueMemberValue('admission_endtime') OR $group_admission_end = '-1';
        ?>
        <li id="admission_endtime" style="margin-top:5px;" class="semadmission_changeable">
        <span style="display:block;float:left;width:200px;"><?=_("Ende Kontingente / Losdatum:")?></span>
        <input <?=$no_admission_end?> style="vertical-align:top" type="checkbox" <?=($group_admission_end == -1 ? 'checked' : '')?> name="admission_change_endtime" value="1">
        &nbsp;
        <?=_("�ndern")?>
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
        <li style="margin-top:5px;" class="semadmission_changeable">
        <span style="display:block;float:left;width:200px;"><?=_("max. Teilnehmer:")?></span>
        <input style="vertical-align:top" type="checkbox"  <?=(!is_null($group_obj->getUniqueMemberValue('admission_turnout')) && !$group_obj->getUniqueMemberValue('admission_turnout')  ? 'checked' : '')?> name="admission_change_turnout" value="1">
        &nbsp;
        <?=_("�ndern")?>
        &nbsp;
        <input style="vertical-align:middle" name="admission_turnout" type="text" size="3" value="<?=$group_obj->getUniqueMemberValue('admission_turnout')?>">
        <?
        echo '&nbsp;(' . _("aktuelle Einstellung:") . '&nbsp;' . (!is_null($group_obj->getUniqueMemberValue('admission_turnout')) ? _("identische Anzahl in allen Veranstaltungen") : _("unterschiedliche Anzahl in allen Veranstaltungen") ) . ')';
        ?>
        </li>
        <li style="margin-top:5px;">
        <span style="padding-left:200px;">
        <?= Button::create(_('�bernehmen'), 'admissiongroupchange', array('title' => _("Einstellungen �bernehmen"))) ?>
        &nbsp;
        <?= Button::create(_('L�schen'), 'admissiongroupdelete', array('title' => _("Gruppe aufl�sen"))) ?>
        &nbsp;
        <?= Button::createCancel(_('Abbrechen'), 'admissiongroupcancel', array('title' => _("Eingabe abbrechen"))) ?>
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
        jQuery(function ($) {
                $('.semadmission_toggle_endtime input').change(function (){
                        var admission_endtime_needed = $('input[name=admission_group_type]:checked').val() == 1
                                                || ($('input[name=admission_group_type]:checked').val() == 2
                                                && $('input[name=admission_enable_quota]:checked').val() == 1);
                        $('#admission_endtime input').attr('disabled', !admission_endtime_needed);
                });
                $('li.semadmission_changeable input[type!=checkbox]').change(function (){
                        $(this).prevAll('input:checkbox').attr('checked', true);
                })
        });
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
                    <?= Button::create(_('Ausw�hlen'), 'choose_institut', array('title' => _("Einrichtung ausw�hlen"))) ?>
                </div>
                <div style="margin:10px;">
                <b><?=_("Angezeigte Veranstaltungen einschr�nken:")?></b>
                <span style="margin-left:10px;">
                <input type="checkbox" name="check_admission" <?=$_SESSION['show_admission']['check_admission'] ? 'checked' : ''?> value="1" style="vertical-align:middle;">&nbsp;<?=_("Anmeldeverfahren")?>
                <input type="checkbox" name="check_prelim" <?=$_SESSION['show_admission']['check_prelim'] ? 'checked' : ''?> value="1" style="vertical-align:middle;">&nbsp;<?=_("vorl�ufige Teilnahme")?>
                </span>
                </div>
                <div style="margin-top:10px;margin-left:10px;">
                <b><?=_("Pr�fix des Veranstaltungsnamens:")?></b>
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
        <td class="blank" style="padding: 5px;">
<?
    $data = semadmission_get_data($seminare_condition);
    $tag = 0;
    if (count($data)) {
        printf("\n<form action=\"%s\" method=\"post\">\n",URLHelper::getLink());
        echo CSRFProtection::tokenTag();

        echo "\n<table class=\"default zebra\">";
        echo "\n<thead><tr style=\"font-size:80%\">";
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
        echo "\n</tr></thead><tbody>";
    } elseif ($institut_id) {
        echo "\n<table width=\"99%\" align=\"center\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">";
        parse_msg ("info�"._("Im gew&auml;hlten Bereich existieren keine teilnahmebeschr&auml;nkten Veranstaltungen")."�", "�", "table_row_even",2, FALSE);
    }
    foreach($data as $seminar_id => $semdata) {
        $teilnehmer = $semdata['count_teilnehmer'];
        if($teilnehmer){
            $teilnehmer .= '&nbsp;<a href="'.URLHelper::getLink('export.php', array('range_id' => $seminar_id, 'ex_type' => 'person', 'xslt_filename' => _("TeilnehmerInnen") . ' '. $semdata['Name'],'format' => 'csv', 'choose' => 'csv-teiln', 'o_mode'=> 'passthrough')).'">';
            $teilnehmer .= Assets::img('icons/16/blue/download.png', tooltip(_("Teilnehmerliste downloaden"))) .'</a>';
        }
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
                echo "<td align=\"center\">";
                unset($last_group);
                printf("<input type=\"checkbox\" name=\"gruppe[]\" value=\"%s\">",$seminar_id);
                echo '</td>';
            } else {
                echo '<td align="center">';
                printf("<a title=\"%s\" href=\"".URLHelper::getLink('show_admission.php',array('group_id' => $semdata['admission_group'], 'group_sem_x'=>1))."\">%s</a>",
                    _("Gruppe bearbeiten"), htmlReady($semdata['groupname']));
                echo '</td>';
            }
        }

        $url = getUrlToSeminar($semdata);

        printf ("<td>
        <a title=\"%s\" href=\"$url\">
                %s%s%s
                </a></td>
                <td align=\"center\">
                <a title=\"%s\" href=\"".URLHelper::getLink('admin_admission.php?select_sem_id=%s')."\">%s</a></td>
                <td align=\"center\">%s</td>
                <td align=\"center\">%s</td>
                <td align=\"center\">%s</td>
                <td align=\"center\">%s</td>
                <td align=\"center\">%s</td>
                <td class=\"%s\" align=\"center\">%s</td>
                <td class=\"%s\" align=\"center\">%s</td>
                <td class=\"%s\" align=\"center\">%s</td>",
                _("Teilnehmerliste aufrufen"),
                $semdata['VeranstaltungsNummer'] ? htmlready('('. $semdata['VeranstaltungsNummer'] . ')') .' ' : '',
                htmlready(substr($semdata['Name'], 0, 50)), (strlen($semdata['Name'])>50) ? "..." : "",
                _("Zugangsbeschr�nkungen aufrufen"),
                $seminar_id,
                $status,
                $teilnehmer,
                $teilnehmer_aux,
                $quota,
                $count2,
                $count3,
                ($datum != -1) ? ($datum < time() ? "steelgroup4" : "steelgroup1") : '',
                ($datum != -1) ? date("d.m.Y, G:i", $datum) : "",
                ($semdata['admission_starttime'] != -1) ? ($semdata['admission_starttime'] < time() ? "steelgroup4" : "steelgroup1") : '',
                ($semdata['admission_starttime'] != -1) ? date("d.m.Y, G:i", $semdata['admission_starttime']) : "",
                ($semdata['admission_endtime_sem']!= -1) ? ($semdata['admission_endtime_sem'] < time() ? "steelgroup4" : "steelgroup1") : '',
                ($semdata['admission_endtime_sem'] != -1) ? date("d.m.Y, G:i", $semdata['admission_endtime_sem']) : "");
        print ("</tr>");
    }

    if (count($data) && $ALLOW_GROUPING_SEMINARS) {
        echo '</tbody><tfoot><tr><td align="left" colspan="11">'. "\n";
        echo Button::create(_('Gruppieren'), 'group_sem', array('title' => _("Markierte Veranstaltungen gruppieren")));
        echo "</td></tr></tfoot>\n";
        echo "</table>";
        echo "</form>\n";
    } else {
        echo "</table>";
    }
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
