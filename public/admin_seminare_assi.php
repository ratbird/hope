<?
# Lifter001: TODO - URLHelper installed, but still no multi-tab-compatibilty
# Lifter002: TODO
# Lifter003: TEST
# Lifter005: TODO - md5 hash
# Lifter007: TODO
# Lifter010: TODO
/*
admin_seminare_assi.php - Seminar-Assisten von Stud.IP.
Copyright (C) 2000 Stefan Suchi <suchi@gmx.de>, Ralf Stockmann <rstockm@gwdg.de>, Cornelis Kater <ckater@gwdg.de>

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
require_once 'lib/resources/lib/RoomRequest.class.php';

page_open(array('sess' => 'Seminar_Session', 'auth' => 'Seminar_Auth', 'perm' => 'Seminar_Perm', 'user' => 'Seminar_User'));

require_once ('lib/msg.inc.php');       //Funktionen fuer Nachrichtenmeldungen
require_once 'lib/functions.php';       //noch mehr Stuff
require_once ('lib/visual.inc.php');        //Aufbereitungsfunktionen
require_once ('lib/dates.inc.php');     //Terminfunktionen
require_once ('lib/log_events.inc.php');
require_once ('lib/classes/StudipSemTreeSearch.class.php');
require_once ('lib/classes/DataFieldEntry.class.php');
require_once ('lib/classes/SeminarCategories.class.php');
require_once 'lib/classes/Seminar.class.php';
require_once ('lib/deputies_functions.inc.php');

$sem_create_perm = (in_array(get_config('SEM_CREATE_PERM'), array('root','admin','dozent')) ? get_config('SEM_CREATE_PERM') : 'dozent');

$perm->check($sem_create_perm);

// Set this to something, just something different...
$hash_secret = "nirhtak";

include ('lib/seminar_open.php');   //hier werden die sessions initialisiert

if ($GLOBALS['RESOURCES_ENABLE']) {
    include_once ($GLOBALS['RELATIVE_PATH_RESOURCES']."/lib/VeranstaltungResourcesAssign.class.php");
    include_once ($GLOBALS['RELATIVE_PATH_RESOURCES']."/lib/ResourcesUserRoomsList.class.php");
    require_once 'vendor/trails/trails.php';
    require_once 'app/controllers/course/room_requests.php';
    $resAssign = new VeranstaltungResourcesAssign();
}

function redirect_to_course_admin($course_id) {
    $course = new Seminar($course_id);
    $sem_class = $GLOBALS['SEM_CLASS'][$GLOBALS['SEM_TYPE'][$course->status]['class']];
    if ($sem_class) {
        $nav = array_shift($sem_class->getNavigationForSlot("admin", $course_id));
        if ($nav) {
            header("Location: " . URLHelper::getUrl($nav->getURL()));
        } else {
            header("Location: ".URLHelper::getUrl("seminar_main.php"));
        }
    } else {
        header("Location: " . URLHelper::getUrl('dispatch.php/course/basicdata/view/' . $course_id));
    }
    page_close();
    die();
}

//cancel
if (Request::submitted('cancel')) {
    redirect_to_course_admin($SessSemName[1]);
}

// Get a database connection and Stuff
$cssSw = new cssClassSwitcher;
$Modules = new Modules;
$semester = new SemesterData;


# init of study area selection
$study_areas = Request::getArray('study_area_selection', array());

$area_selection = new StudipStudyAreaSelection();

if (isset($study_areas['last_selected'])) {
    $area_selection->setSelected((string) $study_areas['last_selected']);
}

if (isset($study_areas['showall'])) {
    $area_selection->setShowAll((boolean) $study_areas['showall']);
}

if (isset($study_areas['areas'])) {
    $area_selection->setAreas((array) $study_areas['areas']);
} else if (isset($_SESSION['sem_create_data']['sem_bereich'])) {
    $area_selection->setAreas((array) $_SESSION['sem_create_data']['sem_bereich']);
}

if (isset($study_areas['selected'])) {
    $area_selection->setSelected($study_areas['selected']);
}


$user_id = $auth->auth["uid"];
$errormsg='';

$deputies_enabled = get_config('DEPUTIES_ENABLE');
$default_deputies_enabled = get_config('DEPUTIES_DEFAULTENTRY_ENABLE');
$cmd = Request::option('cmd');
$form = Request::option('form');
$start_level = Request::option('start_level');
$cp_id = Request::option('cp_id');
//verbotene Kategorien checken
if (($cmd == 'do_copy' && SeminarCategories::GetBySeminarId($cp_id)->course_creation_forbidden)
    || ( $form && (SeminarCategories::Get($_SESSION['sem_create_data']['sem_class']) === false || SeminarCategories::Get($_SESSION['sem_create_data']['sem_class'])->course_creation_forbidden))){
    unset($cmd);
    $start_level = '';
    unset($form);
    $_SESSION['sem_create_data'] = '';
    $errormsg = "error�" . sprintf(_("Veranstaltungen dieser Kategorie d�rfen in dieser Installation nicht angelegt werden!"));
}

//letze angelegte Veranstaltung kopieren
if (Request::get('start_from_backup') && isset($_SESSION['sem_create_data_backup']['timestamp'])) {
    $_SESSION['sem_create_data'] = $_SESSION['sem_create_data_backup'];
    unset($_SESSION['sem_create_data']['room_requests']);
    $_SESSION['sem_create_data']['timestamp'] = time();
    unset($_SESSION['sem_create_data']['level']);
    unset($_SESSION['sem_create_data']['sem_entry']);
}
// Kopieren einer vorhandenen Veranstaltung
//
if (!empty($cmd) && ($cmd == 'do_copy') && $perm->have_studip_perm('tutor',$cp_id)) {
    if(LockRules::Check($cp_id, 'seminar_copy')) {
        $lockdata = LockRules::getObjectRule($cp_id);
        $errormsg = 'error�' . _("Die Veranstaltung kann nicht kopiert werden.").'�';
        if ($lockdata['description']){
            $errormsg .= "info�" . formatLinks($lockdata['description']).'�';
        }
        unset($cmd);
        $start_level = '';
        unset($form);
        $_SESSION['sem_create_data'] = '';
    } else {
        // Eintr�ge in generischen Datenfelder auslesen und zuweisen
        $query = "SELECT datafield_id, datafields_entries.content AS value, datafields.name, datafields.type
                  FROM datafields_entries
                  LEFT JOIN datafields USING (datafield_id)
                  WHERE range_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($cp_id));
        $s_d_fields = $statement->fetchGrouped(PDO::FETCH_ASSOC);

        // Beteiligte Einrichtungen finden und zuweisen
        $query = "SELECT institut_id FROM seminar_inst WHERE seminar_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($cp_id));
        $sem_bet_inst = $statement->fetchAll(PDO::FETCH_COLUMN);

        // Veranstaltungsgrunddaten finden
        $_SESSION['sem_create_data'] = array(
            'sem_datafields' => $s_d_fields,
            'sem_bet_inst'   => $sem_bet_inst,
        );

        // Termine
        $term_turnus = array();
        foreach(SeminarCycleDate::findBySeminar($cp_id) as $cycle) {
            $term_turnus[] = $cycle->toArray();
        }
        $_SESSION['sem_create_data']['term_turnus'] = $term_turnus;
        $_SESSION['sem_create_data']['turnus_count'] = count($term_turnus);
        $_SESSION['sem_create_data']['term_art'] = count($term_turnus) > 0 ? 0 : 1;
        // Nutzerdom�nen
        $_SESSION['sem_create_data']['sem_domain'] = UserDomain::getUserDomainsForSeminar($cp_id);

        if ($_SESSION['sem_create_data']['term_art'] == 1) { //unregelmaessige Veranstaltung oder Block -> Termine kopieren
            // Sitzungen
            $query = "SELECT `date`, end_time, raum
                      FROM termine
                      WHERE range_id = ? AND date_typ = 1 ORDER BY `date`";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($cp_id));
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $db2_start_date = $row['date'];
                $db2_end_date   = $row['end_time'];
                $db2_raum       = $row['raum'];

                $_SESSION['sem_create_data']['term_tag'][$db2_term_count] = intval(date('j', $db2_start_date));
                $_SESSION['sem_create_data']['term_monat'][$db2_term_count] = intval(date('n', $db2_start_date));
                $_SESSION['sem_create_data']['term_jahr'][$db2_term_count] = intval(date('Y', $db2_start_date));
                $_SESSION['sem_create_data']['term_start_stunde'][$db2_term_count] = intval(date('G', $db2_start_date));
                $_SESSION['sem_create_data']['term_start_minute'][$db2_term_count] = intval(date('i', $db2_start_date));
                $_SESSION['sem_create_data']['term_end_stunde'][$db2_term_count] = intval(date('G', $db2_end_date));
                $_SESSION['sem_create_data']['term_end_minute'][$db2_term_count] = intval(date('i', $db2_end_date));
                $_SESSION['sem_create_data']['term_room'][$db2_term_count] = $db2_raum ?: '';

                $db2_term_count++;
            }
            $_SESSION['sem_create_data']['term_count'] = $db2_term_count;
            // Vorbesprechung
            //      $db2->query('SELECT * FROM termine WHERE range_id=\'' . $cp_id. '\' AND date_typ=\'2\' ORDER by date');
            //      if ($db2->next_record()) {
            //          $_SESSION['sem_create_data']['sem_vor_termin'] = $db2->f('date');
            //          $_SESSION['sem_create_data']['sem_vor_end_termin']  = $db2->f('end_time');
            //          if ($db2->f('raum'))
            //              $_SESSION['sem_create_data']['sem_vor_raum'] = $db2->f('raum');
            //      } else {
            $_SESSION['sem_create_data']['sem_vor_end_termin'] = -1;
            $_SESSION['sem_create_data']['sem_vor_termin'] = -1;
            //      }
        } else {
            // Keine Vorbesprechungstermine kopieren
            $_SESSION['sem_create_data']['sem_vor_end_termin'] = -1;
            $_SESSION['sem_create_data']['sem_vor_termin'] = -1;
        }

        for ($i = 0;$i < $_SESSION['sem_create_data']['turnus_count']; $i++) {
            $_SESSION['sem_create_data']['term_turnus_start_stunde'][$i] = $term_turnus[$i]['start_hour'];
            $_SESSION['sem_create_data']['term_turnus_start_minute'][$i] = $term_turnus[$i]['start_minute'];
            $_SESSION['sem_create_data']['term_turnus_end_stunde'][$i] = $term_turnus[$i]['end_hour'];
            $_SESSION['sem_create_data']['term_turnus_end_minute'][$i] = $term_turnus[$i]['end_minute'];
            $_SESSION['sem_create_data']['term_turnus_date'][$i] = $term_turnus[$i]['weekday'];
            $_SESSION['sem_create_data']['term_turnus_desc'][$i] = $term_turnus[$i]['description'];
            $_SESSION['sem_create_data']['term_turnus_week_offset'][$i] = $term_turnus[$i]['week_offset'];
            $_SESSION['sem_create_data']['term_turnus_cycle'][$i] = $term_turnus[$i]['cycle'];
            $_SESSION['sem_create_data']['term_turnus_sws'][$i] = $term_turnus[$i]['sws'];
        }

        // Sonstiges
        $query = "SELECT * FROM seminare WHERE Seminar_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($cp_id));
        $seminar = $statement->fetch(PDO::FETCH_ASSOC);

        $_SESSION['sem_create_data']['sem_id'] = $seminar['Seminar_id'];
        $_SESSION['sem_create_data']['sem_nummer'] = $seminar['VeranstaltungsNummer'];
        $_SESSION['sem_create_data']['sem_inst_id'] = $seminar['Institut_id'];
        $_SESSION['sem_create_data']['sem_name'] = $seminar['Name'];
        $_SESSION['sem_create_data']['sem_untert'] = $seminar['Untertitel'];
        $_SESSION['sem_create_data']['sem_status'] = $seminar['status'];
        $class = $SEM_TYPE[$_SESSION['sem_create_data']['sem_status']]['class'];
        $_SESSION['sem_create_data']['sem_class'] = $class;
        $_SESSION['sem_create_data']['sem_desc'] = $seminar['Beschreibung'];
        $_SESSION['sem_create_data']['sem_room'] = $seminar['Ort'];
        $_SESSION['sem_create_data']['sem_sonst'] = $seminar['Sonstiges'];
        $_SESSION['sem_create_data']['sem_pw'] = $seminar['Passwort'];
        $_SESSION['sem_create_data']['sem_sec_lese'] = $seminar['Lesezugriff'];
        $_SESSION['sem_create_data']['sem_sec_schreib'] = $seminar['Schreibzugriff'];
        $_SESSION['sem_create_data']['sem_start_time'] = $seminar['start_time'];
        $_SESSION['sem_create_data']['sem_duration_time'] = $seminar['duration_time'];
        $_SESSION['sem_create_data']['sem_art'] = $seminar['art'];
        $_SESSION['sem_create_data']['sem_teiln'] = $seminar['teilnehmer'];
        $_SESSION['sem_create_data']['sem_voraus'] = $seminar['vorrausetzungen'];
        $_SESSION['sem_create_data']['sem_orga'] = $seminar['lernorga'];
        $_SESSION['sem_create_data']['sem_leistnw'] = $seminar['leistungsnachweis'];
        $_SESSION['sem_create_data']['sem_ects'] = $seminar['ects'];
        //$_SESSION['sem_create_data']['sem_admission_date'] = $seminar['admission_endtime'];
        $_SESSION['sem_create_data']['sem_admission_date'] = -1;
        $_SESSION['sem_create_data']['sem_turnout'] = $seminar['admission_turnout'];
        //$_SESSION['sem_create_data']['sem_admission'] = $seminar['admission_type'];
        $_SESSION['sem_create_data']['sem_payment'] = $seminar['admission_prelim'];
        $_SESSION['sem_create_data']['sem_paytxt'] = $seminar['admission_prelim_txt'];
        //$_SESSION['sem_create_data']['sem_admission_start_date'] = $seminar['admission_starttime'];
        //$_SESSION['sem_create_data']['sem_admission_end_date'] = $seminar['admission_endtime_sem'];
        $_SESSION['sem_create_data']['sem_admission_start_date'] = -1;
        $_SESSION['sem_create_data']['sem_admission_end_date'] = -1;
        $_SESSION['sem_create_data']['timestamp'] = time(); // wichtig, da sonst beim ersten Aufruf sofort sem_create_data resetted wird!
        // eintragen der sem_tree_ids
        $_SESSION['sem_create_data']['sem_bereich'] = get_seminar_sem_tree_entries($cp_id);

        // Modulkonfiguration �bernehmen
        $_SESSION['sem_create_data']['modules_list'] = $Modules->getLocalModules($cp_id,'sem');
        $_SESSION['sem_create_data']['sem_modules'] = $seminar['modules'];

        // Pluginkonfiguration �bernehmen
        $enabled_plugins = PluginEngine::getPlugins('StandardPlugin', $cp_id);

        foreach ($enabled_plugins as $plugin) {
            $_SESSION['sem_create_data']['enabled_plugins'][] = $plugin->getPluginId();
        }

        // Dozenten und Tutoren eintragen
        $_SESSION['sem_create_data']['sem_doz'] = get_seminar_dozent($cp_id);
        if ($deputies_enabled) {
            if (!$_SESSION['sem_create_data']['sem_dep'] = getDeputies($cp_id)) {
                unset($_SESSION['sem_create_data']['sem_dep']);
            }
        }
        if (!$_SESSION['sem_create_data']['sem_tut'] = get_seminar_tutor($cp_id)) {
            unset($_SESSION['sem_create_data']['sem_tut']);
        }
    }
}

//Assi-Modus an und gesetztes Object loeschen solange keine Veranstaltung angelegt
if (!$_SESSION['sem_create_data']["sem_entry"]) {
    $_SESSION['links_admin_data']["assi"]=TRUE;
    closeObject();
} else
    $_SESSION['links_admin_data']["assi"]=FALSE;

if (($auth->lifetime != 0 && ((time() - $_SESSION['sem_create_data']["timestamp"]) >$auth->lifetime*60)) || (Request::option('new_session')))
    {
    $_SESSION['sem_create_data']='';
    $_SESSION['links_admin_data']='';
    $_SESSION['sem_create_data']["sem_start_termin"]=-1;
    $_SESSION['sem_create_data']["sem_vor_termin"]=-1;
    $_SESSION['sem_create_data']["sem_vor_end_termin"]=-1;
    $_SESSION['sem_create_data']["sem_admission_date"]=-1;
    $_SESSION['sem_create_data']["sem_admission_ratios_changed"]=FALSE;
    $_SESSION['sem_create_data']["sem_admission_start_date"]=-1;
    $_SESSION['sem_create_data']["sem_admission_end_date"]=-1;

    # reset study area selection
    $area_selection = new StudipStudyAreaSelection();
    $_SESSION['sem_create_data']["sem_bereich"] = array();

    if ($GLOBALS['ASSI_SEMESTER_PRESELECT']){
        if ($_SESSION['_default_sem']){
            $one_sem = $semester->getSemesterData($_SESSION['_default_sem']);
            if ($one_sem["vorles_ende"] > time()) $_SESSION['sem_create_data']['sem_start_time'] = $one_sem['beginn'];
        }
    }
    $_SESSION['sem_create_data']["timestamp"]=time();
    }
else
    $_SESSION['sem_create_data']["timestamp"]=time();

//wenn das Seminar bereits geschrieben wurde und wir trotzdem frisch reinkommen, soll die Variable geloescht werden
if (($_SESSION['sem_create_data']["sem_entry"]) && (!$form))
    {
    $_SESSION['sem_create_data']='';
    $_SESSION['sem_create_data']["sem_start_termin"]=-1;
    $_SESSION['sem_create_data']["sem_vor_termin"]=-1;
    $_SESSION['sem_create_data']["sem_vor_end_termin"]=-1;
    }

//empfangene Variablen aus diversen Formularen auswerten
if ($start_level) { //create defaults
    $class = Request::int('class');
    if (SeminarCategories::Get($class) === false || SeminarCategories::Get($class)->course_creation_forbidden) {
        $start_level = '';
        unset($form);
        $_SESSION['sem_create_data'] = '';
        $errormsg = "error�" . sprintf(_("Veranstaltungen dieser Kategorie d�rfen in dieser Installation nicht angelegt werden!"));
    } else {
        if (!array_key_exists('sem_class', $_SESSION['sem_create_data'])) {
            $_SESSION['sem_create_data']['sem_class'] = $class;
        }
        if (!array_key_exists('sem_modules', $_SESSION['sem_create_data'])){
            foreach ($SEM_TYPE as $key => $val) {
                if ($val['class'] == $class) {
                    $_SESSION['sem_create_data']['modules_list'] = $Modules->getLocalModules('', 'sem', false, $key);
                    break;
                }
            }
        }

        //add default values from config.inc.php ($SEM_CLASS)
        if ($SEM_CLASS[$class]['turnus_default'] && !array_key_exists('term_art', $_SESSION['sem_create_data'])) {
            $_SESSION['sem_create_data']['term_art'] = $SEM_CLASS[$class]['turnus_default'];
        }

        if ($SEM_CLASS[$class]['default_read_level'] && !array_key_exists('sem_sec_lese', $_SESSION['sem_create_data'])) {
            $_SESSION['sem_create_data']['sem_sec_lese'] = $SEM_CLASS[$class]['default_read_level'];
        }

        if ($SEM_CLASS[$class]['default_write_level'] && !array_key_exists('sem_sec_schreib', $_SESSION['sem_create_data'])) {
            $_SESSION['sem_create_data']['sem_sec_schreib'] = $SEM_CLASS[$class]['default_write_level'];
        }

        if ($SEM_CLASS[$class]['admission_prelim_default'] && !array_key_exists('sem_payment', $_SESSION['sem_create_data'])) {
            $_SESSION['sem_create_data']['sem_payment'] = $SEM_CLASS[$class]['admission_prelim_default'];
        }

        if ($SEM_CLASS[$class]['admission_type_default'] && !array_key_exists('sem_admission', $_SESSION['sem_create_data'])) {
            $_SESSION['sem_create_data']['sem_admission'] = $SEM_CLASS[$class]['admission_type_default'];
        }

        //if current user is 'dozent' add to list of lecturers
        if ($auth->auth['perm'] == 'dozent') {
            $_SESSION['sem_create_data']['sem_doz'][$user->id] = 1;
            if ($deputies_enabled && $default_deputies_enabled) {
                // Add my own deputies.
                $_SESSION['sem_create_data']['sem_dep'] = getDeputies($user->id);
            }
        }
    }
}

if ($form == 1 && Request::isPost()) {
    $_SESSION['sem_create_data']["sem_name"]=Request::quoted('sem_name');
    $_SESSION['sem_create_data']["sem_untert"]=Request::quoted('sem_untert');
    $_SESSION['sem_create_data']["sem_nummer"]=Request::quoted('sem_nummer');
    $_SESSION['sem_create_data']["sem_ects"]=Request::quoted('sem_ects');
    $_SESSION['sem_create_data']["sem_desc"]=Request::quoted('sem_desc');
    $_SESSION['sem_create_data']["sem_inst_id"]=Request::option('sem_inst_id');
    $_SESSION['sem_create_data']["term_art"]=Request::int('term_art');
    $_SESSION['sem_create_data']["sem_start_time"]=Request::option('sem_start_time');
    $_SESSION['sem_create_data']["sem_domain"] = array();

    # pre-select Heimatinstitut
    if (!isset($study_areas['last_selected']) &&
        !isset($study_areas['selected'])) {

        #   1.) get the ID of the faculty of the chosen institute
        $stmt = DBManager::get()->prepare('SELECT fakultaets_id FROM Institute '.
                                          'WHERE Institut_id = ?');
        $stmt->execute(array($_SESSION['sem_create_data']["sem_inst_id"]));
        $row = $stmt->fetch();

        #   2.) get the sem_tree ID of that faculty
        $stmt = DBManager::get()->prepare('SELECT sem_tree_id FROM sem_tree '.
                                          'WHERE studip_object_id = ?');
        $stmt->execute(array($row['fakultaets_id']));
        $row = $stmt->fetch();
        #   3.) pre-select that ID
        if ($row !== FALSE) {
            $area_selection->setSelected($row['sem_tree_id']);
        }
    }

    if (isset($_SESSION['_default_sem'])){
        $one_sem = $semester->getSemesterDataByDate($_SESSION['sem_create_data']["sem_start_time"]);
        $_SESSION['_default_sem'] = $one_sem['semester_id'];
    }
    if ((Request::option('sem_duration_time') == 0) || (Request::option('sem_duration_time') == -1))
        $_SESSION['sem_create_data']["sem_duration_time"]=Request::option('sem_duration_time');
    else
        $_SESSION['sem_create_data']["sem_duration_time"]=Request::option('sem_duration_time') - Request::option('sem_start_time');

    $_SESSION['sem_create_data']["sem_turnout"]=Request::quoted('sem_turnout');

    //Anmeldeverfahren festlegen
    $sem_admission = Request::quoted('sem_admission');
    if (($_SESSION['sem_create_data']["sem_admission"] = $sem_admission) && $_SESSION['sem_create_data']["sem_admission"] != 3) {
        if(!is_array($_SESSION['sem_create_data']["sem_studg"]) || !count($_SESSION['sem_create_data']["sem_studg"])) $_SESSION['sem_create_data']["sem_studg"]['all'] = array('name' => _("Alle Studieng�nge"), 'ratio' => 100);
    } else {
        $_SESSION['sem_create_data']["sem_studg"] = array();
    }

    //accept only temporaly?
    $_SESSION['sem_create_data']["sem_payment"]=Request::option('sem_payment');
    $sem_bet_inst = Request::optionArray('sem_bet_inst');
    if ($sem_bet_inst)
        {
        foreach ($sem_bet_inst as $tmp_array)
                $tmp_create_data_bet_inst[]=$tmp_array;
        $_SESSION['sem_create_data']["sem_bet_inst"]=$tmp_create_data_bet_inst;
        }
    $i=0;
    $_SESSION['sem_create_data']["sem_status"]=Request::option('sem_status');
    $_SESSION['sem_create_data']["sem_art"]=Request::quoted('sem_art');
    }

if ($form == 2 && Request::isPost()) {

    # evaluate study area selection
    # action: add
    if (isset($study_areas['add'])) {
        foreach ($study_areas['add'] as $key => $value) {
            $area_selection->add($key);
      }
    }

    # action: remove
    else if (isset($study_areas['remove'])) {
        foreach ($study_areas['remove'] as $key => $value) {
            $area_selection->remove($key);
        }
    }

    # action: switch show all
    else if (isset($study_areas['showall_button'])) {
        $area_selection->toggleShowAll();
    }

    # action: search
    else if (isset($study_areas['search_key']) &&
             $study_areas['search_key'] != '') {
        $area_selection->setSearchKey($study_areas['search_key']);
    }

    $_SESSION['sem_create_data']["sem_bereich"] = $area_selection->getAreaIDs();


    if (!$_SESSION['sem_create_data']["sem_admission"]) {
        $_SESSION['sem_create_data']["sem_sec_lese"]=Request::option('sem_sec_lese');
        $_SESSION['sem_create_data']["sem_sec_schreib"]=Request::option('sem_sec_schreib');
    } else {
        $_SESSION['sem_create_data']["sem_sec_lese"]=3;
        $_SESSION['sem_create_data']["sem_sec_schreib"]=3;
    }
    }

if ($form == 3 && Request::isPost())
    {
    if ($_SESSION['sem_create_data']["term_art"] == 0)
        {
        //Arrays fuer Turnus loeschen
        $_SESSION['sem_create_data']["term_turnus_date"]='';
        $_SESSION['sem_create_data']["term_turnus_start_stunde"]='';
        $_SESSION['sem_create_data']["term_turnus_start_minute"]='';
        $_SESSION['sem_create_data']["term_turnus_end_stunde"]='';
        $_SESSION['sem_create_data']["term_turnus_end_minute"]='';
        $_SESSION['sem_create_data']["term_turnus_desc"]='';
        $_SESSION['sem_create_data']["term_turnus_week_offset"]='';
        $_SESSION['sem_create_data']["term_turnus_cycle"]='';
        $_SESSION['sem_create_data']["term_turnus_sws"]='';

        //evtl. Raumanfragen l�schen
        if (is_array($_SESSION['sem_create_data']['room_requests'])) {
            foreach($_SESSION['sem_create_data']['room_requests'] as $key => $request) {
                if (strpos($key, 'cycle') !== false) {
                    unset($_SESSION['sem_create_data']['room_requests'][$key]);
                }
            }
        }
        //Alle eingegebenen Turnus-Daten in Sessionvariable uebernehmen
        $term_turnus_date = Request::optionArray('term_turnus_date');
        $term_turnus_start_stunde = Request::optionArray('term_turnus_start_stunde');
        $term_turnus_start_minute =Request::optionArray('term_turnus_start_minute');
        $term_turnus_end_stunde =Request::optionArray('term_turnus_end_stunde');
        $term_turnus_end_minute = Request::optionArray('term_turnus_end_minute');
        $term_turnus_desc_chooser = Request::quotedArray('term_turnus_desc_chooser');
        $term_turnus_desc = Request::quotedArray('term_turnus_desc');
        $term_turnus_week_offset = Request::optionArray('term_turnus_week_offset');
        $term_turnus_cycle = Request::optionArray('term_turnus_cycle');
        $term_turnus_sws = Request::getArray('term_turnus_sws');

        for ($i=0; $i<$_SESSION['sem_create_data']["turnus_count"]; $i++) {

            $_SESSION['sem_create_data']["term_turnus_date"][$i]=$term_turnus_date[$i];
            $_SESSION['sem_create_data']["term_turnus_start_stunde"][$i] = (strlen($term_turnus_start_stunde[$i]))? intval($term_turnus_start_stunde[$i]) : '';
            $_SESSION['sem_create_data']["term_turnus_start_minute"][$i] = (strlen($term_turnus_start_minute[$i]))? intval($term_turnus_start_minute[$i]) : '';
            $_SESSION['sem_create_data']["term_turnus_end_stunde"][$i] = (strlen($term_turnus_end_stunde[$i]))? intval($term_turnus_end_stunde[$i]) : '';
            $_SESSION['sem_create_data']["term_turnus_end_minute"][$i] = (strlen($term_turnus_end_minute[$i]))? intval($term_turnus_end_minute[$i]) : '';
            $_SESSION['sem_create_data']["term_turnus_desc"][$i] = $term_turnus_desc[$i] ?: $term_turnus_desc_chooser[$i];
            $_SESSION['sem_create_data']["term_turnus_week_offset"][$i] = (int)$term_turnus_week_offset[$i];
            $_SESSION['sem_create_data']["term_turnus_cycle"][$i] = (int)$term_turnus_cycle[$i];
            $_SESSION['sem_create_data']["term_turnus_sws"][$i] = round(str_replace(',','.',$term_turnus_sws[$i]),1);
        }

        //indizierte (=sortierbares Temporaeres Array erzeugen)
        if ($_SESSION['sem_create_data']["term_art"] == 0)
            {
            for ($i=0; $i<$_SESSION['sem_create_data']["turnus_count"]; $i++)
                if (($_SESSION['sem_create_data']["term_turnus_start_stunde"][$i] !== '')  && ($_SESSION['sem_create_data']["term_turnus_end_stunde"][$i] !== '')) {
                    //Index erzeugen
                    $tmp_idx=$_SESSION['sem_create_data']["term_turnus_date"][$i];
                    if ($_SESSION['sem_create_data']["term_turnus_start_stunde"][$i] < 10)
                        $tmp_idx.="0";
                    $tmp_idx.=$_SESSION['sem_create_data']["term_turnus_start_stunde"][$i];
                    if ($_SESSION['sem_create_data']["term_turnus_start_minute"][$i] < 10)
                        $tmp_idx.="0";
                    $tmp_idx.=$_SESSION['sem_create_data']["term_turnus_start_minute"][$i];
                    $tmp_idx.=$_SESSION['sem_create_data']["term_turnus_desc"][$i];
                    $tmp_metadata_termin["turnus_data"][]=array("idx"=>$tmp_idx,
                                                                "day" => $_SESSION['sem_create_data']["term_turnus_date"][$i],
                                                                "start_stunde" => $_SESSION['sem_create_data']["term_turnus_start_stunde"][$i],
                                                                "start_minute" => $_SESSION['sem_create_data']["term_turnus_start_minute"][$i],
                                                                "end_stunde" => $_SESSION['sem_create_data']["term_turnus_end_stunde"][$i],
                                                                "end_minute" => $_SESSION['sem_create_data']["term_turnus_end_minute"][$i],
                                                                "room"=> is_array($_SESSION['sem_create_data']["metadata_termin"]["turnus_data"]) ? $_SESSION['sem_create_data']["metadata_termin"]["turnus_data"][$i]['room'] : '',
                                                                "desc"=>$_SESSION['sem_create_data']["term_turnus_desc"][$i],
                                                                "week_offset"=>$_SESSION['sem_create_data']["term_turnus_week_offset"][$i],
                                                                "cycle"=>$_SESSION['sem_create_data']["term_turnus_cycle"][$i],
                                                                "sws"=>$_SESSION['sem_create_data']["term_turnus_sws"][$i]
                                                                );
                }

            $_SESSION['sem_create_data']["metadata_termin"] = array();
            if (is_array($tmp_metadata_termin["turnus_data"])) {
                //sortieren
                uasort ($tmp_metadata_termin["turnus_data"], create_function('$a,$b', 'return strcmp($a["idx"],$b["idx"]);'));
                foreach(words('term_turnus_date term_turnus_start_stunde term_turnus_start_minute term_turnus_end_stunde term_turnus_end_minute term_turnus_desc term_turnus_week_offset term_turnus_cycle term_turnus_sws') as $k) {
                    $sorter = array_flip(array_keys($tmp_metadata_termin["turnus_data"]));
                    ksort($sorter);
                    array_multisort($sorter,$_SESSION['sem_create_data'][$k]);
                    }
                $_SESSION['sem_create_data']["metadata_termin"]["turnus_data"]=$tmp_metadata_termin["turnus_data"];
                }
            }
        }
    else
        {
        //Arrays fuer Termine loeschen
        $_SESSION['sem_create_data']["term_tag"]='';
        $_SESSION['sem_create_data']["term_monat"]='';
        $_SESSION['sem_create_data']["term_jahr"]='';
        $_SESSION['sem_create_data']["term_start_stunde"]='';
        $_SESSION['sem_create_data']["term_start_minute"]='';
        $_SESSION['sem_create_data']["term_end_stunde"]='';
        $_SESSION['sem_create_data']["term_end_minute"]='';
        $_SESSION['sem_create_data']["term_first_date"]='';

        //evtl. Raumanfragen l�schen
        if (is_array($_SESSION['sem_create_data']['room_requests'])) {
            foreach($_SESSION['sem_create_data']['room_requests'] as $key => $request) {
                if (strpos($key, 'date') !== false) {
                    unset($_SESSION['sem_create_data']['room_requests'][$key]);
                }
            }
        }

        // Extract variables from request
        $term_tag   = Request::getArray('term_tag');
        $term_monat = Request::getArray('term_monat');
        $term_jahr  = Request::getArray('term_jahr');
        $term_start_stunde = Request::getArray('term_start_stunde');
        $term_start_minute = Request::getArray('term_start_minute');
        $term_end_stunde   = Request::getArray('term_end_stunde');
        $term_end_minute   = Request::getArray('term_end_minute');

        //Alle eingegebenen Termin-Daten in Sessionvariable uebernehmen
        for ($i=0; $i<$_SESSION['sem_create_data']["term_count"]; $i++) {
            $_SESSION['sem_create_data']["term_tag"][$i]=$term_tag[$i];
            $_SESSION['sem_create_data']["term_monat"][$i]=$term_monat[$i];
            $_SESSION['sem_create_data']["term_jahr"][$i]=$term_jahr[$i];
            $_SESSION['sem_create_data']["term_start_stunde"][$i] = (strlen($term_start_stunde[$i]))? intval($term_start_stunde[$i]) : '';
            $_SESSION['sem_create_data']["term_start_minute"][$i] = (strlen($term_start_minute[$i]))? intval($term_start_minute[$i]) : '';
            $_SESSION['sem_create_data']["term_end_stunde"][$i] = (strlen($term_end_stunde[$i]))? intval($term_end_stunde[$i]) : '';
            $_SESSION['sem_create_data']["term_end_minute"][$i] = (strlen($term_end_minute[$i]))? intval($term_end_minute[$i]) : '';

            //erster Termin wird gepeichert, wird fuer spaetere Checks benoetigt
            if ((($_SESSION['sem_create_data']["term_first_date"] == 0)
                || ($_SESSION['sem_create_data']["term_first_date"] >mktime((int)$_SESSION['sem_create_data']["term_start_stunde"][$i], (int)$_SESSION['sem_create_data']["term_start_minute"][$i], 0, (int)$_SESSION['sem_create_data']["term_monat"][$i], (int)$_SESSION['sem_create_data']["term_tag"][$i], (int)$_SESSION['sem_create_data']["term_jahr"][$i])))
                && (mktime((int)$_SESSION['sem_create_data']["term_start_stunde"][$i], (int)$_SESSION['sem_create_data']["term_start_minute"][$i], 0, (int)$_SESSION['sem_create_data']["term_monat"][$i], (int)$_SESSION['sem_create_data']["term_tag"][$i], (int)$_SESSION['sem_create_data']["term_jahr"][$i]) > 0)) {
                $_SESSION['sem_create_data']["term_first_date"]=mktime((int)$_SESSION['sem_create_data']["term_start_stunde"][$i], (int)$_SESSION['sem_create_data']["term_start_minute"][$i], 0, (int)$_SESSION['sem_create_data']["term_monat"][$i], (int)$_SESSION['sem_create_data']["term_tag"][$i], (int)$_SESSION['sem_create_data']["term_jahr"][$i]);
            }
        }
    }

    //set the term_art in every case...
    $_SESSION['sem_create_data']["metadata_termin"]["art"]=$_SESSION['sem_create_data']["term_art"];

    //Datum fuer Vobesprechung umwandeln. Checken muessen wir es auch leider direkt hier, da wir es sonst nicht umwandeln duerfen
    if (!Request::get('vor_tag') && !Request::get('vor_monat') && !Request::get('vor_jahr')){
        $_SESSION['sem_create_data']["sem_vor_termin"] = $_SESSION['sem_create_data']["sem_vor_end_termin"] = -1;
    } else {
        if (!check_and_set_date(Request::option('vor_tag'), Request::option('vor_monat'), Request::option('vor_jahr'), Request::option('vor_stunde'), Request::option('vor_minute'), $_SESSION['sem_create_data'], "sem_vor_termin")
        || !check_and_set_date(Request::option('vor_tag'), Request::option('vor_monat'), Request::option('vor_jahr'), Request::option('vor_end_stunde'), Request::option('vor_end_minute'), $_SESSION['sem_create_data'], "sem_vor_end_termin")){
            $errormsg=$errormsg."error�"._("Bitte geben Sie g&uuml;ltige Zeiten f&uuml;r Start- und Endzeit der Vorbesprechung ein!")."�";
        } elseif ($_SESSION['sem_create_data']["sem_vor_termin"] >= $_SESSION['sem_create_data']["sem_vor_end_termin"]) {
            $errormsg .= "error�"._("Die Endzeit der Vorbesprechung darf nicht vor der Startzeit liegen!")."�";
        }
    }
}

if ($form == 4 && Request::isPost()) {
    $_SESSION['sem_create_data']["sem_room"]=Request::quoted('sem_room');
    //The room for the prelimary discussion
    $_SESSION['sem_create_data']["sem_vor_raum"]=Request::quoted('vor_raum');
    $_SESSION['sem_create_data']["sem_vor_resource_id"]=(Request::option('vor_resource_id') == "FALSE") ? FALSE : Request::option('vor_resource_id');
    //if we have a resource_id, we take the room name from resource_id (deprecated at the moment)
    /*if ($GLOBALS['RESOURCES_ENABLE'] && $_SESSION['sem_create_data']["sem_vor_resource_id"]) {
        $resObject = ResourceObject::Factory($_SESSION['sem_create_data']["sem_vor_resource_id"]);
        $_SESSION['sem_create_data']["sem_vor_raum"]=$resObject->getName();
    }*/

    if ($GLOBALS['RESOURCES_ENABLE']) {
        $room_request_form_attributes = array();
        //Room-Requests
        $_SESSION['sem_create_data']['skip_room_request'] = Request::option('skip_room_request');
        if (Request::submitted('room_request_form')) {
            if (Request::option('new_room_request_type')) {
                if ( $_SESSION['sem_create_data']['room_requests'][Request::option('new_room_request_type')] instanceof RoomRequest) {
                    $request = $_SESSION['sem_create_data']['room_requests'][Request::option('new_room_request_type')];
                } else {
                    $request = new RoomRequest();
                    $request->seminar_id = 'assi';
                    $_SESSION['sem_create_data']['room_requests'][Request::option('new_room_request_type')] = $request;
                    $request->user_id = $GLOBALS['user']->id;
                    list($new_type, $id) = explode('_', Request::option('new_room_request_type'));
                    if ($new_type == 'date') {
                        $request->termin_id = Request::option('new_room_request_type');
                    } elseif ($new_type == 'cycle') {
                        $request->metadate_id = Request::option('new_room_request_type');
                    }
                }
                if (!Request::submitted('room_request_choose')) {
                    $room_request_form_attributes = Course_RoomRequestsController::process_form($request, $_SESSION['sem_create_data']['sem_turnout']);
                } elseif (Request::option('current_room_request_type') &&  $_SESSION['sem_create_data']['room_requests'][Request::option('current_room_request_type')] instanceof RoomRequest) {
                    //store last choosen request
                    Course_RoomRequestsController::process_form($_SESSION['sem_create_data']['room_requests'][Request::option('current_room_request_type')], $_SESSION['sem_create_data']['sem_turnout']);
                }
            }
        }
    }
    if ($_SESSION['sem_create_data']["term_art"]==0) {
        //get incoming room-data
        $turnus_data=$_SESSION['sem_create_data']["metadata_termin"]["turnus_data"];

        if (is_array($turnus_data)){

            $term_turnus_room = Request::quotedArray('term_turnus_room');
            $term_turnus_resource_id = Request::optionArray('term_turnus_resource_id');

            foreach ($turnus_data as $key=>$val) {
                //echo $term_turnus_room[$key], $term_turnus_resource_id[$key];

                $_SESSION['sem_create_data']["metadata_termin"]["turnus_data"][$key]["room"] = $term_turnus_room[$key];
                $_SESSION['sem_create_data']["metadata_termin"]["turnus_data"][$key]["resource_id"] = ($term_turnus_resource_id[$key] == "FALSE") ? FALSE : $term_turnus_resource_id[$key];

                //if we have a resource_id, we take the room name from resource_id (deprecated at the moment)
                /*if ($GLOBALS['RESOURCES_ENABLE'] && $_SESSION['sem_create_data']["metadata_termin"]["turnus_data"][$key]["resource_id"]) {
                    $resObject = ResourceObject::Factory($_SESSION['sem_create_data']["metadata_termin"]["turnus_data"][$key]["resource_id"]);
                    $_SESSION['sem_create_data']["metadata_termin"]["turnus_data"][$key]["room"]=$resObject->getName();
                }*/
            }
        }
    } else {
        for ($i=0; $i<$_SESSION['sem_create_data']["term_count"]; $i++) {
            $_SESSION['sem_create_data']["term_room"][$i]=$term_room[$i];
            $_SESSION['sem_create_data']["term_resource_id"][$i]=($term_resource_id[$i] == "FALSE") ? FALSE : $term_resource_id[$i];
            //if we have a resource_id, we take the room name from resource_id (deprecated at the moment)
            /*if ($GLOBALS['RESOURCES_ENABLE'] && $_SESSION['sem_create_data']["term_resource_id"][$i]) {
                $resObject = ResourceObject::Factory($_SESSION['sem_create_data']["term_resource_id"][$i]);
                $_SESSION['sem_create_data']["term_room"][$i]=$resObject->getName();
            }*/
        }
    }
}

if ($form == 5 && Request::isPost()) {

    if(Request::submitted('toggle_admission_quota')){
        $_SESSION['sem_create_data']["admission_enable_quota"] = (int)(Request::int("admission_enable_quota"));
        if(!$_SESSION['sem_create_data']["admission_enable_quota"]){
            $_SESSION['sem_create_data']["sem_admission_date"] = -1;
            $_SESSION['sem_create_data']["sem_admission_ratios_changed"] = false;
        }

    }
    // create a timestamp for begin and end of the seminar
        if (!check_and_set_date(Request::option('adm_s_tag'), Request::option('adm_s_monat'), Request::option('adm_s_jahr'), Request::option('adm_s_stunde'), Request::option('adm_s_minute'), $_SESSION['sem_create_data'], "sem_admission_start_date")) {
        $errormsg=$errormsg."error�"._("Bitte geben Sie ein g&uuml;ltiges Datum f&uuml;r den Start des Anmeldezeitraums ein!")."�";
    }
        if (!check_and_set_date(Request::option('adm_e_tag'), Request::option('adm_e_monat'), Request::option('adm_e_jahr'), Request::option('adm_e_stunde'), Request::option('adm_e_minute'), $_SESSION['sem_create_data'], "sem_admission_end_date")) {
        $errormsg=$errormsg."error�"._("Bitte geben Sie ein g&uuml;ltiges Datum f&uuml;r das Ende des Anmeldezeitraums ein!")."�";
    }
    if ($_SESSION['sem_create_data']["sem_admission_end_date"] != -1) {
        if ($_SESSION['sem_create_data']["sem_admission_end_date"] < time())
        {
            $errormsg=$errormsg."error�"._("Bitte geben Sie ein g&uuml;ltiges Datum f&uuml;r das Ende des Teilnahmeverfahrens ein!")."�";
        }
        if ($_SESSION['sem_create_data']["sem_admission_end_date"] <= $_SESSION['sem_create_data']["sem_admission_start_date"]) {
            $errormsg=$errormsg."error�"._("Das Enddatum des Teilnahmeverfahrens muss nach dem Startdatum liegen!")."�";
        }
    }

    $_SESSION['sem_create_data']["sem_teiln"] = Request::quoted('sem_teiln');
    $_SESSION['sem_create_data']["sem_voraus"] = Request::quoted('sem_voraus');
    $_SESSION['sem_create_data']["sem_orga"] = Request::quoted('sem_orga');
    $_SESSION['sem_create_data']["sem_leistnw"] = Request::quoted('sem_leistnw');
    $_SESSION['sem_create_data']["sem_sonst"] = Request::quoted('sem_sonst');
    $_SESSION['sem_create_data']["sem_paytxt"] = Request::quoted('sem_paytxt');
    $_SESSION['sem_create_data']["sem_datafields"]='';
    $sem_datafields = Request::quotedArray('sem_datafields');
    if (!empty($sem_datafields)) {
        foreach ($sem_datafields as $id => $df_values) {
            $struct = new DataFieldStructure(array("datafield_id"=>$id));
            $struct->load();
            $entry  = DataFieldEntry::createDataFieldEntry($struct);
            $entry->setValueFromSubmit($df_values);
            $_SESSION['sem_create_data']['sem_datafields'][$id] = array('name'=>$entry->getName(), 'type'=>$entry->getType(), 'value'=> $entry->getValue());
        }
    }
    //check if required datafield was not filled out
    $dataFieldStructures = DataFieldStructure::getDataFieldStructures('sem', $_SESSION['sem_create_data']['sem_class'], true);
    foreach ((array)$dataFieldStructures as $id=>$struct) {
        if ($struct->accessAllowed($perm) && $perm->have_perm($struct->getEditPerms()) && $struct->getIsRequired() ) {
           if (! trim($sem_datafields[$id]))
               $errormsg = $errormsg."error�".sprintf(_("Das Feld %s wurde nicht ausgef�llt"), htmlReady($struct->getName()))."�";
        }
    }

    //Studienbereiche entgegennehmen
    $sem_studg_id = Request::optionArray('sem_studg_id');
    if (is_array($sem_studg_id)) {
        $sem_studg_ratio_old = Request::optionArray('sem_studg_ratio_old');
        $sem_studg_ratio = Request::optionArray('sem_studg_ratio');
        foreach ($sem_studg_id as $key=>$val)
            if ($sem_studg_ratio_old[$key] != $sem_studg_ratio[$key])
                $_SESSION['sem_create_data']["sem_admission_ratios_changed"]=TRUE;
        if ($_SESSION['sem_create_data']["sem_admission_ratios_changed"]) {
            $_SESSION['sem_create_data']["sem_studg"]='';
            foreach ($sem_studg_id as $key=>$val)
                $_SESSION['sem_create_data']["sem_studg"][$val]=array("name"=>$sem_studg_name[$key], "ratio"=>(int)$sem_studg_ratio[$key]);
        }
    }

    //Datum fuer Ende der Anmeldung umwandeln. Checken muessen wir es auch leider direkt hier, da wir es sonst nicht umwandeln duerfen
    if (!check_and_set_date(Request::option('adm_tag'), Request::quoted('adm_monat'), Request::quoted('adm_jahr'), Request::quoted('adm_stunde'), Request::quoted('adm_minute'), $_SESSION['sem_create_data'], "sem_admission_date")) {
            if ($_SESSION['sem_create_data']["sem_admission"] == 1) {
                $errormsg=$errormsg."error�"._("Bitte geben Sie g&uuml;ltige Werte f&uuml;r das Losdatum ein!")."�";
            } elseif ($_SESSION['sem_create_data']["sem_admission"] == 2 && $_SESSION['sem_create_data']["admission_enable_quota"] == 1) {
                $errormsg=$errormsg."error�"._("Bitte geben Sie g&uuml;ltige Werte f&uuml;r das Enddatum der Kontingentierung ein!")."�";
            }
            if($_SESSION['sem_create_data']['sem_admission_date'] <
                    $_SESSION['sem_create_data']['sem_admission_start_date'])
            {
                $errormsg=$errormsg."error�"._("Das Losdatum darf nicht vor dem Start des Anmeldezeitraums liegen!")."�";
            }
    }

    //Datum fuer ersten Termin umwandeln. Checken muessen wir es auch leider direkt hier, da wir es sonst nicht umwandeln duerfen
    if ($_SESSION['sem_create_data']["term_start_woche"] == -1 && $_SESSION['sem_create_data']["term_art"] == 0){
        $jahr = Request::option('jahr');
        $monat = Request::option('monat');
        $tag = Request::option('tag');
        if (($jahr>0) && ($jahr<100)) $jahr=$jahr+2000;

        if ($monat == _("mm")) $monat = 0;
        if ($tag == _("tt"))$tag = 0;
        if ($jahr == _("jjjj")) $jahr = 0;
        if (!checkdate((int)$monat, (int)$tag, (int)$jahr))
        {
            $errormsg=$errormsg."error�"._("Bitte geben Sie ein g&uuml;ltiges Datum ein!")."�";
            $_SESSION['sem_create_data']["sem_start_termin"] = -1;
        }
        else {
            $_SESSION['sem_create_data']["sem_start_termin"] = mktime((int)Request::option('stunde'),(int)Request::option('minute'),0,(int)$monat,(int)$tag,(int)$jahr);
            $_SESSION['sem_create_data']["metadata_termin"]["start_termin"] = $_SESSION['sem_create_data']["sem_start_termin"];
            //check overlaps...
            if ($GLOBALS['RESOURCES_ENABLE']) {
                $checkResult = $resAssign->changeMetaAssigns($_SESSION['sem_create_data']["metadata_termin"], $_SESSION['sem_create_data']["sem_start_time"], $_SESSION['sem_create_data']["sem_duration_time"],TRUE);
            }
        }
    }
}

if ($form == 8 && Request::isPost())
    {
    $_SESSION['sem_create_data']["sem_scm_content"]=Request::quoted('sem_scm_content');
    if (!Request::quoted('sem_scm_name')) {
        $_SESSION['sem_create_data']["sem_scm_name"]=$SCM_PRESET[$sem_scm_preset]["name"];
        $_SESSION['sem_create_data']["sem_scm_preset"]=Request::option('sem_scm_preset');
    } else
        $_SESSION['sem_create_data']["sem_scm_name"]=Request::quoted('sem_scm_name');
}

//jump-logic
if (Request::submitted('jump_back')) {

    if ($form > 1) {
        // if we have chosen to not enter dates, skip room-requests
        if ($form == 5) {
            if ($_SESSION['sem_create_data']["term_art"] == -1) {
                $level = 2;
            } else {
                $level = 4;
            }
        }

        //jump normal a form back
        else {
            $level = $form - 1;
        }
    }
}

//Check auf korrekte Eingabe und Sprung in naechste Level, hier auf Schritt 2
if (($form == 1) && (Request::submitted('jump_next')))
    {
    if (($_SESSION['sem_create_data']["sem_duration_time"]<0) && ($_SESSION['sem_create_data']["sem_duration_time"] != -1))
        {
        $level=3;
        $errormsg=$errormsg."error�"._("Das Endsemester darf nicht vor dem Startsemester liegen. Bitte &auml;ndern Sie die entsprechenden Einstellungen!")."�";
        }
    if (strlen($_SESSION['sem_create_data']["sem_name"]) <3)
        {
        $level=1; //wir bleiben auf der ersten Seite
        $errormsg=$errormsg."error�"._("Bitte geben Sie einen g&uuml;ltigen Namen f&uuml;r die Veranstaltung ein!")."�";
        }
     if ($_SESSION['sem_create_data']["sem_start_time"] <0)
        {
        $level=1; //wir bleiben auf der ersten Seite
        $errormsg=$errormsg."error�"._("Bitte geben Sie ein g�ltiges Semester f�r die Veranstaltung ein!")."�";
        }
    if (!$_SESSION['sem_create_data']["sem_inst_id"])
        {
        $level=1;
        $errormsg=$errormsg.sprintf ("error�"._("Da Ihr Account keiner Einrichtung zugeordnet ist, k&ouml;nnen Sie leider noch keine Veranstaltung anlegen. Bitte wenden Sie sich an den/die zust&auml;ndigeN AdministratorIn der Einrichtung oder einen der %sAdministratoren%s des Systems!")."�", "<a href=\"".URLHelper::getLink("dispatch.php/siteinfo/show")."\">", "</a>");
        }
    if (($_SESSION['sem_create_data']["sem_turnout"] < 1) && ($_SESSION['sem_create_data']["sem_admission"]) && ($_SESSION['sem_create_data']["sem_admission"] != 3))
        {
        $level=1;
        $errormsg=$errormsg."error�"._("Wenn Sie die Teilnahmebeschr&auml;nkung benutzen wollen, m&uuml;ssen Sie wenigstens einen Teilnehmer zulassen.")."�";
        $_SESSION['sem_create_data']["sem_turnout"] =1;
        }
        if(!$_SESSION['sem_create_data']["sem_admission"]){
            $_SESSION['sem_create_data']["sem_admission_start_date"] = -1;
            $_SESSION['sem_create_data']["sem_admission_end_date"] = -1;
        }

    if (!$errormsg)
        $level=2;
    }

// move Dozenten
if (Request::quoted('moveup_doz'))
{
    // Ensure continuous order by sorting and reordering
    asort($_SESSION['sem_create_data']['sem_doz']);
    $_SESSION['sem_create_data']['sem_doz'] = array_flip(array_keys($_SESSION['sem_create_data']['sem_doz']));

   $move_uid = get_userid(Request::quoted('moveup_doz'));
   $move_pos = $_SESSION['sem_create_data']["sem_doz"][$move_uid];

   foreach($_SESSION['sem_create_data']["sem_doz"] as $key=>$val)
   {
      if ($val == ($move_pos - 1))
      {
         $_SESSION['sem_create_data']["sem_doz"][$key]      = $move_pos;
         $_SESSION['sem_create_data']["sem_doz"][$move_uid] = $move_pos - 1;
      }
   }
    $level=2;
}
if (Request::quoted('movedown_doz'))
{
    // Ensure continuous order by sorting and reordering
    asort($_SESSION['sem_create_data']['sem_doz']);
    $_SESSION['sem_create_data']['sem_doz'] = array_flip(array_keys($_SESSION['sem_create_data']['sem_doz']));

   $move_uid = get_userid(Request::quoted('movedown_doz'));
   $move_pos = $_SESSION['sem_create_data']["sem_doz"][$move_uid];

   foreach($_SESSION['sem_create_data']["sem_doz"] as $key=>$val)
   {
      if ($val == ($move_pos + 1))
      {
         $_SESSION['sem_create_data']["sem_doz"][$key]      = $move_pos;
         $_SESSION['sem_create_data']["sem_doz"][$move_uid] = $move_pos + 1;
      }
   }
    $level=2;
}
// move Tutoren
if (Request::quoted('moveup_tut'))
{
    // Ensure continuous order by sorting and reordering
    asort($_SESSION['sem_create_data']['sem_tut']);
    $_SESSION['sem_create_data']['sem_tut'] = array_flip(array_keys($_SESSION['sem_create_data']['sem_tut']));

   $move_uid = get_userid(Request::quoted('moveup_tut'));
   $move_pos = $_SESSION['sem_create_data']["sem_tut"][$move_uid];

   foreach($_SESSION['sem_create_data']["sem_tut"] as $key=>$val)
   {
      if ($val == ($move_pos - 1))
      {
         $_SESSION['sem_create_data']["sem_tut"][$key]      = $move_pos;
         $_SESSION['sem_create_data']["sem_tut"][$move_uid] = $move_pos - 1;
      }
   }
    $level=2;
}
if (Request::quoted('movedown_tut'))
{
    // Ensure continuous order by sorting and reordering
    asort($_SESSION['sem_create_data']['sem_tut']);
    $_SESSION['sem_create_data']['sem_tut'] = array_flip(array_keys($_SESSION['sem_create_data']['sem_tut']));

   $move_uid = get_userid(Request::quoted('movedown_tut'));
   $move_pos = $_SESSION['sem_create_data']["sem_tut"][$move_uid];

   foreach($_SESSION['sem_create_data']["sem_tut"] as $key=>$val)
   {
      if ($val == ($move_pos + 1))
      {
         $_SESSION['sem_create_data']["sem_tut"][$key]      = $move_pos;
         $_SESSION['sem_create_data']["sem_tut"][$move_uid] = $move_pos + 1;
      }
   }
    $level=2;
}

//delete Tutoren/Dozenten
if (Request::quoted('delete_doz')) {
  $position = $_SESSION['sem_create_data']["sem_doz"][get_userid(Request::quoted('delete_doz'))];
  unset($_SESSION['sem_create_data']["sem_doz"][get_userid(Request::quoted('delete_doz'))]);

  foreach($_SESSION['sem_create_data']["sem_doz"] as $key => $val) {
    if ($val > $position) {
      $_SESSION['sem_create_data']["sem_doz"][$key] -= 1;
    }
  }

  $level=2;
}

if ($deputies_enabled && Request::quoted('delete_dep')) {
  $dep_id = get_userid(Request::quoted('delete_dep'));
  unset($_SESSION['sem_create_data']["sem_dep"][$dep_id]);

  $level=2;
}

if (Request::quoted('delete_tut')) {
  $position = $_SESSION['sem_create_data']["sem_tut"][get_userid(Request::quoted('delete_tut'))];
  unset($_SESSION['sem_create_data']["sem_tut"][get_userid(Request::quoted('delete_tut'))]);

  foreach($_SESSION['sem_create_data']["sem_tut"] as $key => $val) {
    if ($val > $position) {
      $_SESSION['sem_create_data']["sem_tut"][$key] -= 1;
    }
  }

  $level=2;
}

if (Request::submitted('send_doz')) {
    if (Request::get('add_doz')) {
        $next_position = sizeof($_SESSION['sem_create_data']["sem_doz"]) + 1;
        $doz_id = get_userid(Request::quoted('add_doz'));
        $_SESSION['sem_create_data']["sem_doz"][$doz_id]= $next_position;
        $_SESSION['sem_create_data']["sem_doz_label"][$doz_id]= Request::get("sem_doz_label");
        if ($deputies_enabled) {
            // Unset person as deputy.
            if ($_SESSION['sem_create_data']['sem_dep'][$doz_id]) {
                unset($_SESSION['sem_create_data']['sem_dep'][$doz_id]);
            }
            if (get_config('DEPUTIES_DEFAULTENTRY_ENABLE')) {
                $deputies = getDeputies($doz_id);
                // Add the new lecturer's deputies if necessary.
                foreach ($deputies as $deputy) {
                    if (empty($_SESSION['sem_create_data']['sem_doz'][$deputy['user_id']]) &&
                        !isset($_SESSION['sem_create_data']['sem_dep'][$deputy['user_id']])) {
                    $_SESSION['sem_create_data']['sem_dep'][$deputy['user_id']] = $deputy;
                        }
                }
            }
        }
    }
    $level=2;
}

if ($deputies_enabled && Request::submitted('send_dep')) {
    if (Request::get('add_dep')) {
        $dep_id = get_userid(Request::quoted('add_dep'));
        $_SESSION['sem_create_data']["sem_dep"][$dep_id] = array(
                'user_id' => $dep_id,
                'username' => get_username($dep_id),
                'fullname' => get_fullname($dep_id, 'full_rev'),
                'perms' => $perm->get_perm($dep_id)
            );
        // Remove as lecturer if necessary.
        if (isset($_SESSION['sem_create_data']['sem_doz'][$dep_id])) {
            unset($_SESSION['sem_create_data']['sem_doz'][$dep_id]);
        }
    }
    $level=2;
}

if (Request::submitted('send_tut')) {
    if (Request::get('add_tut')) {
        $next_position = sizeof($_SESSION['sem_create_data']["sem_tut"]) + 1;
        $tut_id = get_userid(Request::quoted('add_tut'));
        $_SESSION['sem_create_data']["sem_tut"][$tut_id]= $next_position;
        $_SESSION['sem_create_data']["sem_tut_label"][$tut_id]= Request::get("sem_tut_label");
    }
    $level=2;
}

// delete user domain

if (Request::submitted('delete_domain')) {
    $index = array_search(Request::get('delete_domain'), $_SESSION['sem_create_data']["sem_domain"]);

    unset($_SESSION['sem_create_data']["sem_domain"][$index]);
}

if (Request::submitted('search_doz') || Request::submitted('search_dep') || Request::submitted('search_tut') || Request::submitted('reset_search') ||
    Request::submitted('sem_bereich_do_search') || Request::submitted('add_domain') || Request::submitted('delete_domain') ||

    $study_areas['add'] || $study_areas['remove'] ||
    $study_areas['showall_button'] || $study_areas['search_button'] ||
    $study_areas['search_key'] || $study_areas['selected'] ||
    $study_areas['rewind_button']) {
    $level=2;
} elseif (($form == 2) && (Request::submitted('jump_next'))) //wenn alles stimmt, Checks und Sprung auf Schritt 3
    {
    if (is_array($_SESSION['sem_create_data']['sem_tut']))
        foreach ($_SESSION['sem_create_data']['sem_tut'] as $key=>$val){
            if (array_key_exists($key, $_SESSION['sem_create_data']['sem_doz']))
                $badly_dozent_is_tutor = TRUE;
        }
    if ($badly_dozent_is_tutor) {
        $level=2; //wir bleiben auf der zweiten Seite
        $errormsg=$errormsg."error�". sprintf(_("Sie d&uuml;rfen eine Person mit Status %s nicht gleichzeitig als %s eintragen!"), get_title_for_status('dozent', 1, $_SESSION['sem_create_data']["sem_status"]), get_title_for_status('tutor', 1, $_SESSION['sem_create_data']["sem_status"]))."�";
    }

    if (sizeof($_SESSION['sem_create_data']["sem_doz"])==0)
        {
        $level=2; //wir bleiben auf der zweiten Seite
        $errormsg=$errormsg."error�". sprintf(_("Bitte geben Sie mindestens eine Person mit Status %s f&uuml;r die Veranstaltung an!"), get_title_for_status('dozent', 1, $_SESSION['sem_create_data']["sem_status"]))."�";
        }
    elseif ((!$perm->have_perm("root")) && (!$perm->have_perm("admin")))
        {
        if (!array_key_exists($user_id, $_SESSION['sem_create_data']['sem_doz'])) {
            $level=2;
            $errormsg=$errormsg."error�". sprintf(_("Sie m&uuml;ssen wenigstens sich selbst als %s f&uuml;r diese Veranstaltung angeben! Der Eintrag wird automatisch gesetzt."), get_title_for_status('dozent', 1, $_SESSION['sem_create_data']["sem_status"]))."�";
            $_SESSION['sem_create_data']['sem_doz'][$user_id]= count($_SESSION['sem_create_data']['sem_doz']) + 1;
            }
        }
    if ($SEM_CLASS[$_SESSION['sem_create_data']["sem_class"]]["bereiche"]) {
        if (sizeof($_SESSION['sem_create_data']["sem_bereich"]) == 0) {
            $level=2;
            $errormsg = $errormsg . "error�" .
            _("Bitte geben Sie mindestens einen Studienbereich f&uuml;r die Veranstaltung an!")."�";
        } else if ($false_mark) {
            $level=2;
            $errormsg = $errormsg . "error�" .
              _("Sie haben eine oder mehrere Fach&uuml;berschriften (unterstrichen) ausgew&auml;hlt. Diese dienen nur der Orientierung und k&ouml;nnen nicht ausgew&auml;hlt werden!")."�";
            }
        }

    if (($_SESSION['sem_create_data']["sem_sec_schreib"]) <($_SESSION['sem_create_data']["sem_sec_lese"]))
        {
        $level=2; //wir bleiben auf der zweiten Seite
        $errormsg=$errormsg."error�"._("Es macht keinen Sinn, die Sicherheitsstufe f&uuml;r den Lesezugriff h&ouml;her zu setzen als f&uuml;r den Schreibzugriff!")."�";
        }
    if (!$errormsg) {
        if ($_SESSION['sem_create_data']["term_art"]== -1) {
            $_SESSION['sem_create_data']['skip_room_request'] = true;
            $level=5;
        } else {
            unset($_SESSION['sem_create_data']['skip_room_request']);
            $level=3;
        }
    } else
        $level=2;
    }

//Felder fuer Standardtermine oder Blocktermine, Studiengaenge hinzufuegen/loeschen
if (Request::submitted('add_turnus_field'))
    {
    $_SESSION['sem_create_data']["turnus_count"]++;
    $level=3;
    }
if (Request::submitted('add_term_field'))
    {
    $_SESSION['sem_create_data']["term_count"]++;
    $level=3;
    }
if (Request::int('delete_turnus_field'))
    {
    for ($i=0; $i<$_SESSION['sem_create_data']["turnus_count"]; $i++)
        if ($i != (Request::int('delete_turnus_field')-1))
            {
            $tmp_term_turnus_date[]=$_SESSION['sem_create_data']["term_turnus_date"][$i];
            $tmp_term_turnus_start_stunde[]=$_SESSION['sem_create_data']["term_turnus_start_stunde"][$i];
            $tmp_term_turnus_start_minute[]=$_SESSION['sem_create_data']["term_turnus_start_minute"][$i];
            $tmp_term_turnus_end_stunde[]=$_SESSION['sem_create_data']["term_turnus_end_stunde"][$i];
            $tmp_term_turnus_end_minute[]=$_SESSION['sem_create_data']["term_turnus_end_minute"][$i];
            $tmp_term_turnus_resource_id[]=$_SESSION['sem_create_data']["term_turnus_resource_id"][$i];
            $tmp_term_turnus_room[]=$_SESSION['sem_create_data']["term_turnus_room"][$i];
            $tmp_term_turnus_desc[]=$_SESSION['sem_create_data']["term_turnus_desc"][$i];
            }
    $_SESSION['sem_create_data']["term_turnus_date"]=$tmp_term_turnus_date;
    $_SESSION['sem_create_data']["term_turnus_start_stunde"]=$tmp_term_turnus_start_stunde;
    $_SESSION['sem_create_data']["term_turnus_start_minute"]=$tmp_term_turnus_start_minute;
    $_SESSION['sem_create_data']["term_turnus_end_stunde"]=$tmp_term_turnus_end_stunde;
    $_SESSION['sem_create_data']["term_turnus_end_minute"]=$tmp_term_turnus_end_minute;
    $_SESSION['sem_create_data']["term_turnus_resource_id"]=$tmp_term_turnus_resource_id;
    $_SESSION['sem_create_data']["term_turnus_room"]=$tmp_term_turnus_room;
    $_SESSION['sem_create_data']["term_turnus_desc"]=$tmp_term_turnus_desc;

    $_SESSION['sem_create_data']["turnus_count"]--;
    $level=3;
    }
if (Request::int('delete_term_field'))
    {
    for ($i=0; $i<$_SESSION['sem_create_data']["term_count"]; $i++)
        if ($i != (Request::int('delete_term_field')-1))
            {
            $tmp_term_tag[]=$_SESSION['sem_create_data']["term_tag"][$i];
            $tmp_term_monat[]=$_SESSION['sem_create_data']["term_monat"][$i];
            $tmp_term_jahr[]=$_SESSION['sem_create_data']["term_jahr"][$i];
            $tmp_term_start_stunde[]=$_SESSION['sem_create_data']["term_start_stunde"][$i];
            $tmp_term_start_minute[]=$_SESSION['sem_create_data']["term_start_minute"][$i];
            $tmp_term_end_stunde[]=$_SESSION['sem_create_data']["term_end_stunde"][$i];
            $tmp_term_end_minute[]=$_SESSION['sem_create_data']["term_end_minute"][$i];
            $tmp_term_resource_id[]=$_SESSION['sem_create_data']["term_resource_id"][$i];
            $tmp_term_room[]=$_SESSION['sem_create_data']["term_room"][$i];
            }
    $_SESSION['sem_create_data']["term_tag"]=$tmp_term_tag;
    $_SESSION['sem_create_data']["term_monat"]=$tmp_term_monat;
    $_SESSION['sem_create_data']["term_jahr"]=$tmp_term_jahr;
    $_SESSION['sem_create_data']["term_start_stunde"]=$tmp_term_start_stunde;
    $_SESSION['sem_create_data']["term_start_minute"]=$tmp_term_start_minute;
    $_SESSION['sem_create_data']["term_end_stunde"]=$tmp_term_end_stunde;
    $_SESSION['sem_create_data']["term_end_minute"]=$tmp_term_end_minute;
    $_SESSION['sem_create_data']["term_resource_id"]=$tmp_term_resource_id;
    $_SESSION['sem_create_data']["term_room"]=$tmp_term_room;

    $_SESSION['sem_create_data']["term_count"]--;
    $level=3;
    }


//Termin-Metaddaten-Check, wenn alles stimmt, Sprung auf Schritt 4
if (($form == 3) && (Request::submitted('jump_next')))
    {
    if ($_SESSION['sem_create_data']["term_art"]==0)
        {
        for ($i=0; $i<$_SESSION['sem_create_data']["turnus_count"]; $i++)
            if ((($_SESSION['sem_create_data']["term_turnus_start_stunde"][$i] !== '') || ($_SESSION['sem_create_data']["term_turnus_end_stunde"][$i] !== '')))
                {
                if (($_SESSION['sem_create_data']["term_turnus_start_stunde"][$i] !== '') xor ($_SESSION['sem_create_data']["term_turnus_end_stunde"][$i]) !== '')
                        {
                        if (!$just_informed)
                            $errormsg=$errormsg."error�"._("Bitte f&uuml;llen Sie beide Felder f&uuml;r Start- und Endzeit der regul&auml;ren Termine aus!")."�";
                        $just_informed=TRUE;
                        }
                if ((($_SESSION['sem_create_data']["term_turnus_start_stunde"][$i]>23) || ($_SESSION['sem_create_data']["term_turnus_start_stunde"][$i]<0))  ||  (($_SESSION['sem_create_data']["term_turnus_start_minute"][$i]>59) || ($_SESSION['sem_create_data']["term_turnus_start_minute"][$i]<0))  ||  (($_SESSION['sem_create_data']["term_turnus_end_stunde"][$i]>23) ||($_SESSION['sem_create_data']["term_turnus_end_stunde"][$i]<0))  || (($_SESSION['sem_create_data']["term_turnus_end_minute"][$i]>59) || ($_SESSION['sem_create_data']["term_turnus_end_minute"][$i]<0)))
                        {
                        if (!$just_informed3)
                            $errormsg=$errormsg."error�"._("Sie haben eine ung&uuml;ltige Zeit eingegeben. Bitte korrigieren Sie dies!")."�";
                        $just_informed3=TRUE;
                        }
                if (mktime((int)$_SESSION['sem_create_data']["term_turnus_start_stunde"][$i], (int)$_SESSION['sem_create_data']["term_turnus_start_minute"][$i], 0, 1, 1, 2001) >= mktime((int)$_SESSION['sem_create_data']["term_turnus_end_stunde"][$i], (int)$_SESSION['sem_create_data']["term_turnus_end_minute"][$i], 0, 1, 1, 2001))
                    if ((!$just_informed5) && (!$just_informed)) {
                        $errormsg=$errormsg."error�"._("Der Endzeitpunkt eines regul&auml;ren Termins muss nach dem jeweiligen Startzeitpunkt liegen!")."�";
                        $just_informed5=TRUE;
                    }
                }
                elseif(!$just_informed4)
                    if (($_SESSION['sem_create_data']["term_turnus_start_stunde"][$i] === '') && ($_SESSION['sem_create_data']["term_turnus_start_minute"][$i] === '') && ($_SESSION['sem_create_data']["term_turnus_end_stunde"][$i] === '') && ($_SESSION['sem_create_data']["term_turnus_end_minute"][$i] === ''))
                        $empty_fields++;
                    else
                        {
                        $errormsg=$errormsg."error�"._("Sie haben nicht alle Felder der regul&auml;ren Termine ausgef&uuml;llt. Bitte f&uuml;llen Sie alle Felder aus!")."�";
                        $just_informed4=TRUE;
                        }
        }
    else {
        for ($i=0; $i<$_SESSION['sem_create_data']["term_count"]; $i++)
            if ((($_SESSION['sem_create_data']["term_start_stunde"][$i] !== '') || ($_SESSION['sem_create_data']["term_end_stunde"][$i] !== '')) && (($_SESSION['sem_create_data']["term_monat"][$i]) && ($_SESSION['sem_create_data']["term_tag"][$i]) && ($_SESSION['sem_create_data']["term_jahr"][$i]))) {
                if (($_SESSION['sem_create_data']["term_start_stunde"][$i] !== '') xor ($_SESSION['sem_create_data']["term_end_stunde"][$i] !== ''))
                        {
                        if (!$just_informed)
                            $errormsg=$errormsg."error�"._("Bitte f&uuml;llen Sie beide Felder f&uuml;r Start- und Endzeit der jeweiligen Termine aus!")."�";
                        $just_informed=TRUE;
                        }
                if (!checkdate ((int)$_SESSION['sem_create_data']["term_monat"][$i], (int)$_SESSION['sem_create_data']["term_tag"][$i], (int)$_SESSION['sem_create_data']["term_jahr"][$i]))
                        {
                        if (!$just_informed2)
                            $errormsg=$errormsg."error�"._("Sie haben ein ung&uuml;ltiges Datum eingegeben. Bitte korrigieren Sie das Datum!")."�";
                        $just_informed2=TRUE;
                        }
                if ((($_SESSION['sem_create_data']["term_start_stunde"][$i]>23) || ($_SESSION['sem_create_data']["term_start_stunde"][$i]<0))  ||  (($_SESSION['sem_create_data']["term_start_minute"][$i]>59) || ($_SESSION['sem_create_data']["term_start_minute"][$i]<0))  ||  (($_SESSION['sem_create_data']["term_end_stunde"][$i]>23) ||($_SESSION['sem_create_data']["term_end_stunde"][$i]<0))  || (($_SESSION['sem_create_data']["term_end_minute"][$i]>59) || ($_SESSION['sem_create_data']["term_end_minute"][$i]<0)))
                        {
                        if (!$just_informed3)
                            $errormsg=$errormsg."error�"._("Sie haben eine ung&uuml;ltige Zeit eingegeben, bitte korrigieren Sie dies!")."�";
                        $just_informed3=TRUE;
                        }
                if (mktime((int)$_SESSION['sem_create_data']["term_start_stunde"][$i], (int)$_SESSION['sem_create_data']["term_start_minute"][$i], 0, 1, 1, 2001) > mktime((int)$_SESSION['sem_create_data']["term_end_stunde"][$i], (int)$_SESSION['sem_create_data']["term_end_minute"][$i], 0, 1, 1, 2001))
                    if ((!$just_informed5) && (!$just_informed)) {
                        $errormsg=$errormsg."error�"._("Der Endzeitpunkt der Termine muss nach dem jeweiligen Startzeitpunkt liegen!")."�";
                        $just_informed5=TRUE;
                    }
            }
            elseif(!$just_informed4)
                if (($_SESSION['sem_create_data']["term_tag"][$i] === '') && ($_SESSION['sem_create_data']["term_monat"][$i] === '') && ($_SESSION['sem_create_data']["term_jahr"][$i] === '') && ($_SESSION['sem_create_data']["term_start_stunde"][$i] === '') && ($_SESSION['sem_create_data']["term_start_minute"][$i] === '') && ($_SESSION['sem_create_data']["term_end_stunde"][$i] === '') && ($_SESSION['sem_create_data']["term_end_minute"][$i] === ''))
                    $empty_fields++;
                else {
                    $errormsg=$errormsg."error�"._("Sie haben nicht alle Felder bei der Termineingabe ausgef&uuml;llt. Bitte f&uuml;llen Sie alle Felder aus!")."�";
                    $just_informed4=TRUE;
                    }
    }

    if ($_SESSION['sem_create_data']["sem_vor_termin"] == -1);
    else {
        if (Request::quoted('vor_stunde') xor Request::quoted('vor_end_stunde'))
            $errormsg=$errormsg."error�"._("Bitte f&uuml;llen Sie beide Felder f&uuml;r Start- und Endzeit der Vorbesprechung aus!")."�";

        //check for room management: we dont allow the preliminary discussion matches a turnus date (in this case, a schedule schoudl be used!)
        if ((!$_SESSION['sem_create_data']["term_art"]) && ($GLOBALS['RESOURCES_ENABLE'])) {
            $sem_start_timestamp = veranstaltung_beginn_from_metadata($_SESSION['sem_create_data']["term_art"],$_SESSION['sem_create_data']["sem_start_time"],$_SESSION['sem_create_data']['term_start_woche'],$_SESSION['sem_create_data']['sem_start_termin'],$_SESSION['sem_create_data']['metadata_termin']['turnus_data']);
            if ($sem_start_timestamp > 0 && $_SESSION['sem_create_data']["sem_vor_termin"] >= $sem_start_timestamp){
                $tmp_vor_day = date("w", $_SESSION['sem_create_data']["sem_vor_termin"]);
                if ($tmp_vor_day == 0)
                    $tmp_vor_day = 7;
                for ($i=0; $i<$_SESSION['sem_create_data']["turnus_count"]; $i++) {
                    if (($_SESSION['sem_create_data']["term_turnus_start_stunde"][$i] == Request::optionArray('vor_stunde')) &&
                        ($_SESSION['sem_create_data']["term_turnus_end_stunde"][$i] == Request::optionArray('vor_end_stunde')) &&
                        ($_SESSION['sem_create_data']["term_turnus_start_minute"][$i] == Request::optionArray('vor_minute')) &&
                        ($_SESSION['sem_create_data']["term_turnus_end_minute"][$i] == Request::optionArray('vor_end_minute')) &&
                        ($_SESSION['sem_create_data']["term_turnus_date"][$i] == $tmp_vor_day)) {
                            $errormsg=$errormsg."error�"._("Der Termin f&uuml;r die Vorbesprechung findet zu den gleichen Zeiten wie die Veranstaltung statt. Bitte legen Sie in diesem Fall einen Ablaufplan in einem sp&auml;teren Schritt an und &auml;ndern einen Termin in den Typ \"Vorbesprechung\"")."�";
                            break;
                        }
                }
            }
        }
    }

    if (!$errormsg) {
        if ($empty_fields) {
            $_SESSION['sem_create_data']['dates_are_valid'] = false;
            $_SESSION['sem_create_data']['skip_room_request'] = true;
            $level = 4;
        } else {
            $_SESSION['sem_create_data']['dates_are_valid'] = true;
            $_SESSION['sem_create_data']['skip_room_request'] = false;
            $level = 4;
        }
    } else {
        $level=3;
    }

}

if (Request::submitted('room_request_form') && !(Request::submitted('jump_back') || Request::submitted('jump_next'))) {
    $level=4;
}

if (($form == 4) && (Request::submitted('jump_next'))) {
    //checks for room-request
    $requests_ok = false;
    if (is_array($_SESSION['sem_create_data']['room_requests'])) {
        foreach ($_SESSION['sem_create_data']['room_requests'] as $request) {
            if ($request->getSettedPropertiesCount() || $request->getResourceId()) {
                $requests_ok = true;
                break;
        }
    }

    if (!$requests_ok && (!(get_config('RESOURCES_ALLOW_SEMASSI_SKIP_REQUEST') && $_SESSION['sem_create_data']['skip_room_request']))) {
            $errormsg .= "error�" . _("Die Anfrage konnte nicht gespeichert werden, da Sie mindestens einen Raumwunsch oder eine gew�nschte Eigenschaft (z.B. Anzahl der Sitzpl�tze) angeben m�ssen!");
            if(get_config('RESOURCES_ALLOW_SEMASSI_SKIP_REQUEST')){
                $errormsg .= "<br>"._("Wenn Sie keine Raumanfrage ben�tigen, aktivieren Sie die entsprechende Option. Die Raumbuchungen und die freien Angaben zu R�umen werden auch ohne Raumanfrage gespeichert.");
            }
            $errormsg .= '�';
            $dont_anchor = TRUE;
        }
    }

    //checks for direct ressources-assign
    if ($_SESSION['sem_create_data']["term_art"]==0 && is_array($_SESSION['sem_create_data']["metadata_termin"]["turnus_data"])) {
        if ($GLOBALS['RESOURCES_ENABLE']) {
            $tmp_metadate = new Metadate();
            $tmp_assigns = array();
            $tmp_metadate->setSeminarStartTime($_SESSION['sem_create_data']['sem_start_time']);
            $tmp_metadate->setSeminarDurationTime($_SESSION['sem_create_data']['sem_duration_time']);
            foreach ($_SESSION['sem_create_data']["metadata_termin"]["turnus_data"] as $key => $val) {
                $metadate_id = $tmp_metadate->addCycle($_SESSION['sem_create_data']["metadata_termin"]["turnus_data"][$key], false);
                $tmp_assigns = array_merge($tmp_assigns, $tmp_metadate->getVirtualMetaAssignObjects($metadate_id, $_SESSION['sem_create_data']["metadata_termin"]["turnus_data"][$key]["resource_id"]));
            }
            $checkResult = $resAssign->changeMetaAssigns(null, null, null, true, $tmp_assigns);
        }
    } else {
        for ($i=0; $i<$_SESSION['sem_create_data']["term_count"]; $i++) {
            //check overlaps
            if ((!$errormsg) && ($GLOBALS['RESOURCES_ENABLE'])) {
                $tmp_chk_date=mktime((int)$_SESSION['sem_create_data']["term_start_stunde"][$i], (int)$_SESSION['sem_create_data']["term_start_minute"][$i], 0, (int)$_SESSION['sem_create_data']["term_monat"][$i], (int)$_SESSION['sem_create_data']["term_tag"][$i], (int)$_SESSION['sem_create_data']["term_jahr"][$i]);
                $tmp_chk_end_time=mktime((int)$_SESSION['sem_create_data']["term_end_stunde"][$i], (int)$_SESSION['sem_create_data']["term_end_minute"][$i], 0, (int)$_SESSION['sem_create_data']["term_monat"][$i], (int)$_SESSION['sem_create_data']["term_tag"][$i], (int)$_SESSION['sem_create_data']["term_jahr"][$i]);
                $checkResult = array_merge((array)$checkResult, (array)$resAssign->insertDateAssign(FALSE, $_SESSION['sem_create_data']["term_resource_id"][$i], $tmp_chk_date, $tmp_chk_end_time, TRUE));
            }
        }
    }

    if ($_SESSION['sem_create_data']["sem_vor_termin"] == -1);
    else {
        //check overlaps...
        if ($GLOBALS['RESOURCES_ENABLE']) {
            $checkResult = array_merge((array)$checkResult, (array)$resAssign->insertDateAssign(FALSE, $_SESSION['sem_create_data']["sem_vor_resource_id"], $_SESSION['sem_create_data']["sem_vor_termin"], $_SESSION['sem_create_data']["sem_vor_end_termin"],TRUE));
        }
    }

    //generate bad messages
    if ($GLOBALS['RESOURCES_ENABLE']) {
        $errormsg.=getFormattedResult($checkResult);
    }
    if (!$errormsg)
        $level=5;
    else
        $level=4;
}

if ($level == 4 && $GLOBALS['RESOURCES_ENABLE'] && $GLOBALS['RESOURCES_ALLOW_ROOM_REQUESTS']) {
        $_SESSION['sem_create_data']['room_requests_options'] = array();
        $_SESSION['sem_create_data']['room_requests_options'][] = array('value' => 'course', 'name' => _('alle regelm��igen und unregelm��igen Termine der Veranstaltung'));
        if ($_SESSION['sem_create_data']["dates_are_valid"]) {
            if ($_SESSION['sem_create_data']["term_art"] == 0) {
                foreach ($_SESSION['sem_create_data']['metadata_termin']['turnus_data'] as $key => $value) {
                    $cycle = new SeminarCycleDate();
                    $cycle->weekday = $value['day'] == 7 ? 0 : $value['day'];
                    $cycle->week_offset = $value['week_offset'];
                    $cycle->cycle = $value['cycle'];
                    $cycle->start_hour = $value['start_stunde'];
                    $cycle->start_minute = $value['start_minute'];
                    $cycle->end_hour = $value['end_stunde'];
                    $cycle->end_minute = $value['end_minute'];
                    $name = _("alle Termine einer regelm��igen Zeit");
                    $name .= ' (' . $cycle->toString('full') . ')';
                    $_SESSION['sem_create_data']['room_requests_options'][] = array('value' => 'cycle_' . $key, 'name' => $name);
                }
            } else {
                for ($i=0; $i < count($_SESSION['sem_create_data']['term_tag']); $i++) {
                    $name = _("Einzeltermin der Veranstaltung");
                    $termin = new SingleDate();
                    $new_date = array('start' => 0, 'end' => 0);
                    if (check_and_set_date($_SESSION['sem_create_data']['term_tag'][$i], $_SESSION['sem_create_data']['term_monat'][$i], $_SESSION['sem_create_data']['term_jahr'][$i],
                        $_SESSION['sem_create_data']['term_start_stunde'][$i], $_SESSION['sem_create_data']['term_start_minute'][$i], $new_date, 'start') &&
                        check_and_set_date($_SESSION['sem_create_data']['term_tag'][$i], $_SESSION['sem_create_data']['term_monat'][$i], $_SESSION['sem_create_data']['term_jahr'][$i],
                        $_SESSION['sem_create_data']['term_end_stunde'][$i], $_SESSION['sem_create_data']['term_end_minute'][$i], $new_date, 'end'))
                    {
                        $termin->setTime($new_date['start'], $new_date['end']);
                    }
                    $name .= ' (' . $termin->toString() . ')';
                    $_SESSION['sem_create_data']['room_requests_options'][] = array('value' => 'date_' . $i, 'name' => $name);
                }
            }
        }
       if (!is_array($_SESSION['sem_create_data']['room_requests'])) {
           $request = new RoomRequest();
           $request->seminar_id = 'assi';
           $request->user_id = $GLOBALS['user']->id;
           $_SESSION['sem_create_data']['room_requests']['course'] = $request;
        }

}

//Neuen Studiengang zur Begrenzung aufnehmen
if (Request::submitted('add_studg')) {
    if ($sem_add_studg && $sem_add_studg != 'all') {
        $query = "SELECT name FROM studiengaenge WHERE studiengang_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($sem_add_studg));
        $studiengang_name = $statement->fetchColumn();

        $_SESSION['sem_create_data']["sem_studg"][$sem_add_studg] = array(
            'name'  => $studiengang_name,
            'ratio' => (int)$sem_add_ratio
        );
    } else if ($sem_add_studg == 'all'){
        $_SESSION['sem_create_data']["sem_studg"][$sem_add_studg] = array(
            'name'  => _('Alle Studieng�nge'),
            'ratio' => (int)$sem_add_ratio
        );
    }
    $level=5;
}

if(Request::submitted('reset_admission_time')) {
    $_SESSION['sem_create_data']["sem_admission_end_date"] = -1;
    $_SESSION['sem_create_data']["sem_admission_start_date"] = -1;
    $level = 5;
}

if(Request::submitted('toggle_admission_quota')) {
    $level = 5;
}
//Studiengang zur Begrenzung loeschen
if (Request::option('sem_delete_studg')) {
    unset($_SESSION['sem_create_data']["sem_studg"][Request::option('sem_delete_studg')]);
    $level=5;
    if(!count($_SESSION['sem_create_data']["sem_studg"])){
         $_SESSION['sem_create_data']["sem_studg"]['all'] = array('name' => _("Alle Studieng�nge"), 'ratio' => 100);
    }
}

//Prozentangabe checken/berechnen wenn neuer Studiengang, einer geloescht oder Seite abgeschickt
if ((($form == 5) && (Request::submitted('jump_next'))) || (Request::submitted('add_studg')) || (Request::option('sem_delete_studg')) || Request::submitted('toggle_admission_quota')) {
    if ($_SESSION['sem_create_data']["sem_admission"] && $_SESSION['sem_create_data']["sem_admission"] != 3 && $_SESSION['sem_create_data']["admission_enable_quota"]) {
        if (!$_SESSION['sem_create_data']["sem_admission_ratios_changed"] && ((Request::submitted('add_studg') && !$sem_add_ratio && $sem_add_studg) || Request::option('sem_delete_studg') || Request::submitted('toggle_admission_quota'))) {//User hat nichts veraendert und neuen Studiengang ohne Wert geschickt oder studiengang gel�scht, wir versuchen automatisch zu rechnen
            if (is_array($_SESSION['sem_create_data']["sem_studg"])){
                $ratio = round(100 / (sizeof ($_SESSION['sem_create_data']["sem_studg"]) ));
                foreach ($_SESSION['sem_create_data']["sem_studg"] as $key=>$val){
                    $_SESSION['sem_create_data']["sem_studg"][$key]["ratio"] = $ratio;
                    $cnt += $ratio;
                }
                if($cnt < 100){ //letzten Studiengang auff�llen, wg. evtl. Rundungsfehler
                    $_SESSION['sem_create_data']["sem_studg"][$key]["ratio"] = (100 - $cnt + $ratio);
                }
            }
        }
        if (is_array($_SESSION['sem_create_data']["sem_studg"])){
            $cnt = 0;
            if(count($_SESSION['sem_create_data']["sem_studg"]) > 1){
                foreach ($_SESSION['sem_create_data']["sem_studg"] as $key => $val){
                    $cnt += $val["ratio"];
                }
                if ($cnt <= 100 && $_SESSION['sem_create_data']["sem_admission_ratios_changed"] && Request::submitted('add_studg') && $sem_add_studg && !$sem_add_ratio){
                    $_SESSION['sem_create_data']["sem_studg"][$key]["ratio"] = (100 - $cnt + $val["ratio"]);
                    $cnt = 100;
                }
            } else {
                reset($_SESSION['sem_create_data']["sem_studg"]);
                $cnt = $_SESSION['sem_create_data']["sem_studg"][key($_SESSION['sem_create_data']["sem_studg"])]["ratio"];
            }
        }
        if($cnt > 100){
            $errormsg.= "error�". _("Die Summe der Kontigente �bersteigt 100%. Bitte �ndern Sie die Kontigente!") . "�";
            $level=5;
        }
        if($cnt < 100){
            $errormsg.= "error�". _("Die Summe der Kontigente liegt unter 100%. Bitte �ndern Sie die Kontigente!") . "�";
            $level=5;
        }
    }
}

//wenn alles stimmt, Sprung auf Schritt 5 (Anlegen)
if (($form == 5) && (Request::submitted('jump_next')))
    {
    if (($_SESSION['sem_create_data']["sem_sec_lese"] ==2) ||  ($_SESSION['sem_create_data']["sem_sec_schreib"] ==2))
        {
            //Password bei Bedarf dann doch noch verschlusseln
            if (!Request::quoted('sem_passwd'))
                $_SESSION['sem_create_data']["sem_pw"] = "";
            elseif($sem_passwd != "*******") {
                $_SESSION['sem_create_data']["sem_pw"] = md5(Request::quoted('sem_passwd'));
                if(Request::quoted('sem_passwd2') != "*******")
                    $check_pw = md5(Request::quoted('sem_passwd2'));
            }

        if (($_SESSION['sem_create_data']["sem_pw"]=="") || ($_SESSION['sem_create_data']["sem_pw"] == md5("")))
                {
                $errormsg=$errormsg."error�"._("Sie haben kein Passwort eingegeben! Bitte geben Sie ein Passwort ein!")."�";
                $level=5;
                }
              elseif (!empty($check_pw) AND $_SESSION['sem_create_data']["sem_pw"] != $check_pw)
                {
            $errormsg=$errormsg."error�"._("Das eingegebene Passwort und das Passwort zur Best&auml;tigung stimmen nicht &uuml;berein!")."�";
                $_SESSION['sem_create_data']["sem_pw"] = "";
                $level=5;
                }
    }

    //Ende der Anmeldung checken
    if ($_SESSION['sem_create_data']["sem_admission"] && $_SESSION['sem_create_data']["sem_admission"] != 3) {
        if ($_SESSION['sem_create_data']["sem_admission_date"] == -1)
            if ($_SESSION['sem_create_data']["sem_admission"] == 1)
                $errormsg.= "error�"._("Bitte geben Sie einen Termin f&uuml;r das Losdatum an!")."�";
            elseif ($_SESSION['sem_create_data']["sem_admission"] == 2 && $_SESSION['sem_create_data']["admission_enable_quota"] == 1)
                $errormsg.= "error�"._("Bitte geben Sie einen Termin f&uuml;r das Enddatum der Kontingentierung an!")."�";
        elseif ($_SESSION['sem_create_data']["term_art"]==0){
            $tmp_first_date=veranstaltung_beginn_from_metadata ($_SESSION['sem_create_data']["term_art"], $_SESSION['sem_create_data']["sem_start_time"], $_SESSION['sem_create_data']["term_start_woche"], $_SESSION['sem_create_data']["sem_start_termin"], $_SESSION['sem_create_data']["metadata_termin"]["turnus_data"], "int");
            if (($_SESSION['sem_create_data']["sem_admission_date"] > $tmp_first_date) && ($tmp_first_date >0)){
                if ($tmp_first_date > 0)
                    if ($_SESSION['sem_create_data']["sem_admission"] == 1)
                        $errormsg.= sprintf ("error�"._("Das Losdatum liegt nach dem ersten Veranstaltungstermin am %s. Bitte &auml;ndern Sie das Losdatum!")."�", date ("d.m.Y", $tmp_first_date));
                    else
                        $errormsg.= sprintf ("error�"._("Das Enddatum der Kontingentierung liegt nach dem ersten Veranstaltungstermin am %s. Bitte &auml;ndern Sie das Enddatum der Kontingentierung!")."�", date ("d.m.Y", $tmp_first_date));
                $level=5;
            }
        } elseif (($_SESSION['sem_create_data']["sem_admission_date"] > $_SESSION['sem_create_data']["term_first_date"]) && ($_SESSION['sem_create_data']["term_first_date"])) {
                if ($_SESSION['sem_create_data']["sem_admission"] == 1)
                    $errormsg.=sprintf ("error�"._("Das Losdatum liegt nach dem eingetragenen Veranstaltungsbeginn am %s. Bitte &auml;ndern Sie das Losdatum!")."�", date ("d.m.Y", $_SESSION['sem_create_data']["term_first_date"]));
                else
                    $errormsg.=sprintf ("error�"._("Das Enddatum der Kontingentierung liegt nach dem eingetragenen Veranstaltungsbeginn am %s. Bitte &auml;ndern Sie das Enddatum der Kontingentierung!")."�", date ("d.m.Y", $_SESSION['sem_create_data']["term_first_date"]));
                $level=5;
        }
        if (($_SESSION['sem_create_data']["sem_admission_date"] < time()) && ($_SESSION['sem_create_data']["sem_admission_date"] != -1)) {
                if ($_SESSION['sem_create_data']["sem_admission"] == 1)
                    $errormsg.=sprintf ("error�"._("Das Losdatum liegt in der Vergangenheit. Bitte &auml;ndern Sie das Losdatum!")."�");
                else
                    $errormsg.=sprintf ("error�"._("Das Enddatum der Kontingentierung liegt in der Vergangenheit. Bitte &auml;ndern Sie das Enddatum der Kontingentierung!")."�");
                $level=5;
        } elseif (($_SESSION['sem_create_data']["sem_admission_date"] < (time() + (24 * 60 *60))) && ($_SESSION['sem_create_data']["sem_admission_date"] != -1)) {
                if ($_SESSION['sem_create_data']["sem_admission"] == 1)
                    $errormsg.=sprintf ("error�"._("Das Losdatum muss mindestens einen Tag in der Zukunft liegen!")."�");
                else
                    $errormsg.=sprintf ("error�"._("Das Enddatum der Kontingentierung muss mindestens einen Tag in der Zukunft liegen!")."�");
                $level=5;
        }
    }

    //Erster Termin wenn angegeben werden soll muss er auch da sein
    if (($_SESSION['sem_create_data']["sem_start_termin"] == -1) && ($_SESSION['sem_create_data']["term_start_woche"] ==-1))
        $errormsg=$errormsg."error�"._("Bitte geben Sie einen ersten Termin an!")."�";
    else
        if (((Request::option('stunde')) && (!Request::option('end_stunde'))) || ((!Request::option('stunde')) && (Request::option('end_stunde'))))
            $errormsg=$errormsg."error�"._("Bitte f&uuml;llen Sie beide Felder f&uuml;r Start- und Endzeit des ersten Termins aus!")."�";
    //create overlap array
    if (is_array($checkResult)) {
        $overlaps_detected=FALSE;
        foreach ($checkResult as $key=>$val)
            if ($val["overlap_assigns"] == TRUE)
                    $overlaps_detected[] = array("resource_id"=>$val["resource_id"], "overlap_assigns"=>$val["overlap_assigns"]);
    }

    //generate bad msg if overlaps exists
    if ($overlaps_detected) {
        $errormsg=$errormsg."error�"._("Folgende gew&uuml;nschte Raumbelegungen &uuml;berschneiden sich mit bereits vorhandenen Belegungen. Bitte &auml;ndern Sie die R&auml;ume oder Zeiten!");
        $i=0;
        foreach ($overlaps_detected as $val) {
            $resObj = ResourceObject::Factory($val["resource_id"]);
            $errormsg.="<br><font size=\"-1\" color=\"black\">".htmlReady($resObj->getName()).": ";
            //show the first overlap
            list(, $val2) = each($val["overlap_assigns"]);
            $errormsg.=date("d.m, H:i",$val2["begin"])." - ".date("H:i",$val2["end"]);
            if (sizeof($val) >1)
                $errormsg.=", ... ("._("und weitere").")";
            $errormsg.=", ".$resObj->getFormattedLink($val2["begin"], _("Raumplan anzeigen"));
            $i++;
        }
        $errormsg.="</font>�";
        unset($overlaps_detected);
    }

    if (!$errormsg)
        $level=6;
    else
        $level=5;
    }

//OK, nun wird es ernst, wir legen das Seminar an.
if (($form == 6) && (Request::submitted('jump_next')))
    {
    $run = TRUE;

    //Rechte ueberpruefen
    if (!$perm->have_perm("dozent"))
        {
        $errormsg .= "error�"._("Sie haben keine Berechtigung Veranstaltungen anzulegen Um eine Veranstaltung anlegen zu k&ouml;nnen, ben&ouml;tigen Sie mindestens den globalen Status &raquo;Dozent&laquo;. Bitte kontaktieren Sie den/die f&uuml;r Sie zust&auml;ndigeN AdministratorIn.")."�";
        $run = FALSE;
        }
    if (!$perm->have_studip_perm("dozent",$_SESSION['sem_create_data']["sem_inst_id"]))
        {
        $errormsg .= "error�"._("Sie haben keine Berechtigung f&uuml;r diese Einrichtung Veranstaltungen anzulegen.")."�";
            $run = FALSE;
        }

    //Nochmal Checken, ob wirklich alle Daten vorliegen. Kann zwar eigentlich hier nicht mehr vorkommen, aber sicher ist sicher.
    if (empty($_SESSION['sem_create_data']["sem_name"]))
        {
        $errormsg  .= "error�"._("Bitte geben Sie einen Namen f&uuml;r die Veranstaltung ein!")."�";
        $run = FALSE;
            }

    if (empty($_SESSION['sem_create_data']["sem_inst_id"]))
        {
        $errormsg .= "error�"._("Bitte geben Sie eine Heimat-Einrichtung f&uuml;r die Veranstaltung an!")."�";
        $run = FALSE;
        }
    if ($SEM_CLASS[$_SESSION['sem_create_data']["sem_class"]]["bereiche"])  {
        if (empty($_SESSION['sem_create_data']["sem_bereich"]))
            {
            $errormsg .= "error�"._("Bitte geben Sie wenigstens einen Studienbereich f&uuml;r die Veranstaltung an!")."�";
            $run = FALSE;
            }
        }

        if ($perm->have_perm("admin") && empty($_SESSION['sem_create_data']["sem_doz"]))
            {
            $errormsg .= "error�"._("Bitte geben Sie wenigstens eine Dozentin oder einen Dozenten f&uuml;r die Veranstaltung an!")."�";
            $run = FALSE;
            }

    $_SESSION['sem_create_data_backup'] = $_SESSION['sem_create_data'];

    if ($run) {
        //Termin-Metadaten-Array zusammenmatschen zum besseren speichern in der Datenbank
        if ($_SESSION['sem_create_data']['term_art'] == -1) {
            $_SESSION['sem_create_data']['metadata_termin'] = array();
            $_SESSION['sem_create_data']['metadata_termin']['art'] = 1;

            $_SESSION['sem_create_data']["term_monat"]='';
            $_SESSION['sem_create_data']["term_jahr"]='';
            $_SESSION['sem_create_data']["term_start_stunde"]='';
            $_SESSION['sem_create_data']["term_start_minute"]='';
            $_SESSION['sem_create_data']["term_end_stunde"]='';
            $_SESSION['sem_create_data']["term_end_minute"]='';
            $_SESSION['sem_create_data']["term_room"]='';
            $_SESSION['sem_create_data']["term_count"]= 0;
            $_SESSION['sem_create_data']["term_first_date"]='';

            $_SESSION['sem_create_data']["term_turnus"] = '';
            $_SESSION['sem_create_data']["term_start_woche"] = '';
            $_SESSION['sem_create_data']["sem_start_termin"] = '';
            $_SESSION['sem_create_data']["turnus_count"] = 0;

            $_SESSION['sem_create_data']["term_turnus_start_stunde"] = '';
            $_SESSION['sem_create_data']["term_turnus_start_minute"] = '';
            $_SESSION['sem_create_data']["term_turnus_end_stunde"] = '';
            $_SESSION['sem_create_data']["term_turnus_end_minute"] = '';
            $_SESSION['sem_create_data']["term_turnus_resource_id"] = '';
            $_SESSION['sem_create_data']["term_turnus_room"] = '';
            $_SESSION['sem_create_data']["term_turnus_date"] = '';
            $_SESSION['sem_create_data']["term_turnus_desc"] = '';

            //set temporary entry (for skip dates field) to the right valuem_create_data["term_tag"]='';
            $_SESSION['sem_create_data']['term_art'] = 1;
        } else if ($_SESSION['sem_create_data']['term_art'] == 1) {
            $_SESSION['sem_create_data']["term_turnus"] = '';
            $_SESSION['sem_create_data']["term_start_woche"] = '';
            $_SESSION['sem_create_data']["sem_start_termin"] = '';
            $_SESSION['sem_create_data']["turnus_count"] = 0;
        }

        //for admission it have to always 3
        if ($_SESSION['sem_create_data']["sem_admission"]) {
            $_SESSION['sem_create_data']["sem_sec_lese"]=3;
            $_SESSION['sem_create_data']["sem_sec_schreib"]=3;
        }



        if (Request::option('Schreibzugriff') < Request::option('Lesezugriff')) // hier wusste ein Dozent nicht, was er tat
            $Schreibzugriff = $Lesezugriff;

        //Visibility
        if ($SEM_CLASS[$_SESSION['sem_create_data']["sem_class"]]["visible"] !== FALSE)
            $visible = TRUE;
        else
            $visible = FALSE;

        $sem = new Seminar();
        if ($_SESSION['sem_create_data']['metadata_termin']['art'] == 0)  {
            if(is_array($_SESSION['sem_create_data']["metadata_termin"]["turnus_data"])){
                foreach ($_SESSION['sem_create_data']["metadata_termin"]["turnus_data"] as $key=>$val) {
                    $_SESSION['sem_create_data']["metadata_termin"]["turnus_data"][$key]["room"] = stripslashes($_SESSION['sem_create_data']["metadata_termin"]["turnus_data"][$key]["room"]);
                    $_SESSION['sem_create_data']["metadata_termin"]["turnus_data"][$key]["description"] = stripslashes($_SESSION['sem_create_data']["metadata_termin"]["turnus_data"][$key]["desc"]);
                    if ($_SESSION['sem_create_data']["metadata_termin"]["turnus_data"][$key]["day"] == 7) {
                        $_SESSION['sem_create_data']["metadata_termin"]["turnus_data"][$key]["day"] = 0;
                    }
                    $metadate_id = $sem->metadate->addCycle($_SESSION['sem_create_data']["metadata_termin"]["turnus_data"][$key]);
                    $temp_rooms[$metadate_id] = $_SESSION['sem_create_data']["metadata_termin"]["turnus_data"][$key]["room"];
                    $temp_resources[$metadate_id] = $_SESSION['sem_create_data']["metadata_termin"]["turnus_data"][$key]["resource_id"];
                    if ($_SESSION['sem_create_data']['room_requests']['cycle_' . $key] instanceof RoomRequest) {
                        $_SESSION['sem_create_data']['room_requests']['cycle_' . $key]->metadate_id = $metadate_id;
                    }
                }
            }
        } else {
            if ($_SESSION['sem_create_data']['term_count'] > 0)
            for ($i = 0; $i < $_SESSION['sem_create_data']['term_count']; $i++) {
                $termin = new SingleDate(array('seminar_id' => $sem->getId()));
                $new_date = array('start' => 0, 'end' => 0);
                if (check_and_set_date($_SESSION['sem_create_data']['term_tag'][$i], $_SESSION['sem_create_data']['term_monat'][$i], $_SESSION['sem_create_data']['term_jahr'][$i],
                    $_SESSION['sem_create_data']['term_start_stunde'][$i], $_SESSION['sem_create_data']['term_start_minute'][$i], $new_date, 'start') &&
                    check_and_set_date($_SESSION['sem_create_data']['term_tag'][$i], $_SESSION['sem_create_data']['term_monat'][$i], $_SESSION['sem_create_data']['term_jahr'][$i],
                    $_SESSION['sem_create_data']['term_end_stunde'][$i], $_SESSION['sem_create_data']['term_end_minute'][$i], $new_date, 'end'))
                {
                    $termin->setTime($new_date['start'], $new_date['end']);

                    if ($_SESSION['sem_create_data']['term_resource_id'][$i]) {
                        $termin->bookRoom($_SESSION['sem_create_data']['term_resource_id'][$i]);
                    } else if ($_SESSION['sem_create_data']['term_room'][$i]){
                        $termin->setFreeRoomText(stripslashes($_SESSION['sem_create_data']['term_room'][$i]));
                    }
                    if ($termin->validate()) {
                        $sem->addSingleDate($termin);
                        if ($_SESSION['sem_create_data']['room_requests']['date_' . $i] instanceof RoomRequest) {
                            $_SESSION['sem_create_data']['room_requests']['date_' . $i]->termin_id = $termin->termin_id;
                        }
                    }
                    unset($termin);
                }
            }
        }
        $sem->metadate->setSeminarStartTime($_SESSION['sem_create_data']['sem_start_time']);
        $sem->metadate->setSeminarDurationTime($_SESSION['sem_create_data']['sem_duration_time']);
        $sem->metadate->seminar_id = $sem->getId();

        $sem->semester_start_time = $_SESSION['sem_create_data']['sem_start_time'];
        $sem->semester_duration_time = $_SESSION['sem_create_data']['sem_duration_time'];
        $sem->seminar_number = stripslashes($_SESSION['sem_create_data']['sem_nummer']);
        $sem->institut_id = $_SESSION['sem_create_data']['sem_inst_id'];
        $sem->name = stripslashes($_SESSION['sem_create_data']['sem_name']);
        $sem->subtitle = stripslashes($_SESSION['sem_create_data']['sem_untert']);
        $sem->status = $_SESSION['sem_create_data']['sem_status'];
        $sem->description = stripslashes($_SESSION['sem_create_data']['sem_desc']);
        $sem->location = stripslashes($_SESSION['sem_create_data']['sem_room']);
        $sem->misc = stripslashes($_SESSION['sem_create_data']['sem_sonst']);
        $sem->password = $_SESSION['sem_create_data']['sem_pw'];
        $sem->read_level = $_SESSION['sem_create_data']['sem_sec_lese'];
        $sem->write_level = $_SESSION['sem_create_data']['sem_sec_schreib'];
        $sem->form = stripslashes($_SESSION['sem_create_data']['sem_art']);
        $sem->participants = stripslashes($_SESSION['sem_create_data']['sem_teiln']);
        $sem->requirements = stripslashes($_SESSION['sem_create_data']['sem_voraus']);
        $sem->orga = stripslashes($_SESSION['sem_create_data']['sem_orga']);
        $sem->leistungsnachweis = stripslashes($_SESSION['sem_create_data']['sem_leistnw']);
        $sem->ects = stripslashes($_SESSION['sem_create_data']['sem_ects']);
        $sem->admission_endtime = $_SESSION['sem_create_data']['sem_admission_date'];
        $sem->admission_turnout = $_SESSION['sem_create_data']['sem_turnout'];
        $sem->admission_type = (int)$_SESSION['sem_create_data']['sem_admission'];
        $sem->admission_prelim = (int)$_SESSION['sem_create_data']['sem_payment'];
        $sem->admission_prelim_txt = stripslashes($_SESSION['sem_create_data']['sem_paytxt']);
        $sem->admission_starttime = $_SESSION['sem_create_data']['sem_admission_start_date'];
        $sem->admission_endtime_sem = $_SESSION['sem_create_data']['sem_admission_end_date'];
        $sem->admission_enable_quota = $_SESSION['sem_create_data']['admission_enable_quota'];
        $sem->visible = (($visible) ? '1' : '0');
        $sem->showscore = '0';
        $sem->modules = $_SESSION['sem_create_data']['sem_modules'];

        $sem->user_number = ($_SESSION['sem_create_data']['user_number']) ? '1' : '0';

        $_SESSION['sem_create_data']["sem_id"] = $sem->getId();

        // Raumanfragen erzeugen
        if ($GLOBALS['RESOURCES_ENABLE'] && $GLOBALS['RESOURCES_ALLOW_ROOM_REQUESTS']) {
            if (!$_SESSION['sem_create_data']['skip_room_request'] && is_array($_SESSION['sem_create_data']['room_requests'])) {
                foreach ($_SESSION['sem_create_data']['room_requests'] as $request) {
                    $request->seminar_id = $sem->getId();
                    $request->user_id = $GLOBALS['user']->id;
                    if ($request->getSettedPropertiesCount() || $request->getResourceId()) {
                        $request->store();
                    }
                }
            }
        }

        // logging
        log_event("SEM_CREATE",$_SESSION['sem_create_data']['sem_id'],NULL,NULL,$query);
        log_event(($visible ? "SEM_VISIBLE" : "SEM_INVISIBLE"), $_SESSION['sem_create_data']['sem_id'],NULL,NULL,'admin_seminare_assi',"SYSTEM");


        // create singledates for the regular entrys
        if (!$_SESSION['sem_create_data']["sem_entry"]) {
            foreach ($sem->getCycles() as $key => $val) {
                $sem->metadate->createSingleDates($key);
                // Raum buchen, wenn eine Angabe gemacht wurde, oder Freitextangabe, falls vorhanden
                if (($temp_resources[$key] != '') || ($temp_rooms[$key] != '')) {
                    $singleDates =& $sem->getSingleDatesForCycle($key);
                    foreach ($singleDates as $sd_key => $sd_val) {
                        if ($GLOBALS['RESOURCES_ENABLE'] && $temp_resources[$key]  != '') {
                            $singleDates[$sd_key]->bookRoom($temp_resources[$key]);
                            if ($msg = $singleDates[$sd_key]->getMessages()) {
                                $errormsg .= $msg;
                            }
                        } else {
                            $singleDates[$sd_key]->setFreeRoomText($temp_rooms[$key]);
                            $singleDates[$sd_key]->store();
                        }
                    }
                }

            }

            // Speichern der Veranstaltungsdaten -> anlegen des Seminars
            $sem->store();

            // speichere die Nutzerdom�nen f�r das neue Seminar
            $count_doms = 0;
            foreach ($_SESSION['sem_create_data']["sem_domain"] as $domain_id){
                $domain = new UserDomain($domain_id);
                $domain->addSeminar($sem->id);
                $count_doms ++;
            }

            //completing the internal settings....
            $successful_entry=1;
            $_SESSION['sem_create_data']["sem_entry"]=TRUE;

            // Logging
            log_event("SEM_CREATE",$_SESSION['sem_create_data']['sem_id'],NULL,NULL,$query);
            log_event(($visible ? "SEM_VISIBLE" : "SEM_INVISIBLE"), $_SESSION['sem_create_data']['sem_id'],NULL,NULL,'admin_seminare_assi',"SYSTEM");
            $_SESSION['links_admin_data']["referred_from"]="assi";
            $_SESSION['links_admin_data']["assi"]=FALSE; //protected Assi-mode off

            if (!array_key_exists('sem_modules', $_SESSION['sem_create_data'])){
                //write the default module-config
                $Modules = new Modules();
                $Modules->writeDefaultStatus($_SESSION['sem_create_data']["sem_id"]);
            }
            //$Modules->writeStatus("scm", $_SESSION['sem_create_data']["sem_id"], FALSE); //the scm has to be turned off, because an empty free informations page isn't funny

            if (is_array($_SESSION['sem_create_data']["sem_doz"]))  // alle ausgew�hlten Dozenten durchlaufen
            {
                $self_included = FALSE;
                $count_doz=0;
                foreach ($_SESSION['sem_create_data']["sem_doz"] as $key=>$val)
                {
                    $group=select_group($_SESSION['sem_create_data']["sem_start_time"]);

                    if ($key == $user_id) {
                        $self_included=TRUE;
                    }

                    $next_pos = get_next_position("dozent",$_SESSION['sem_create_data']["sem_id"]);

                    $query = "INSERT INTO seminar_user (Seminar_id, user_id, status, gruppe,
                                                        visible, mkdate, position, label)
                              VALUES (?, ?, 'dozent', ?, 'yes', UNIX_TIMESTAMP(), ?, ?)";
                    $statement = DBManager::get()->prepare($query);
                    $statement->execute(array(
                        $_SESSION['sem_create_data']['sem_id'],
                        $key, $group, $next_pos,
                        $_SESSION['sem_create_data']['sem_doz_label'][$key] ?: ''
                    ));

                    if ($statement->rowCount() >= 1) {
                        $count_doz++;
                    }
                }
            }

            if (!$perm->have_perm("admin") && !$self_included && (!$deputies_enabled || $_SESSION['sem_create_data']['sem_dep'][$user_id])) // wenn nicht admin, aktuellen Dozenten eintragen
            {
                $group=select_group($_SESSION['sem_create_data']["sem_start_time"]);

                $next_pos = get_next_position("dozent",$_SESSION['sem_create_data']["sem_id"]);

                $query = "INSERT INTO seminar_user (Seminar_id, user_id, status, gruppe,
                                                    mkdate, position, label)
                          VALUES (?, ?, 'dozent', ?, UNIX_TIMESTAMP(), ?, ?)";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array(
                    $_SESSION['sem_create_data']['sem_id'],
                    $user_id, $group, $next_pos,
                    $_SESSION['sem_create_data']['sem_doz_label'][$user_id]
                ));

                if ($statement->rowCount() >= 1) {
                    $count_doz++;
                }
            }

            if (is_array($_SESSION['sem_create_data']["sem_dep"]))  // alle ausgew�hlten Vertretungen durchlaufen
            {
                // Prepare statement that checks whether a user is already
                // present in the seminar_user table for a given seminar
                $query = "SELECT 1 FROM seminar_user WHERE Seminar_id = ? AND user_id = ?";
                $check_statement = DBManager::get()->prepare($query);

                // Prepare statement that inserts the user as a deputy
                $query = "INSERT INTO deputies (range_id, user_id, gruppe)
                          VALUES (?, ?, ?)";
                $insert_statement = DBManager::get()->prepare($query);

                $count_dep=0;
                foreach ($_SESSION['sem_create_data']['sem_dep'] as $key => $val) {
                    $group = select_group($_SESSION['sem_create_data']['sem_start_time']);

                    $check_statement->execute(array(
                        $_SESSION['sem_create_data']['sem_id'],
                        $key
                    ));
                    $present = $check_statement->fetchColumn();
                    $check_statement->closeCursor();

                    if (!$present) { // Vertretung eintragen
                        $insert_statement->execute(array(
                            $_SESSION['sem_create_data']['sem_id'],
                            $key, $group
                        ));
                        $query = "insert into deputies SET range_id = '".
                            $_SESSION['sem_create_data']["sem_id"]."', user_id = '".
                            $key."', gruppe = '$group'";
                        if ($insert_statement->rowCount() >= 1) {
                            $count_dep++;
                        }
                    }
                }
            }

            if (is_array($_SESSION['sem_create_data']["sem_tut"]))  // alle ausgew�hlten Tutoren durchlaufen
            {
                // Prepare statement that checks whether a user is already
                // present in the seminar_user table for a given seminar
                $query = "SELECT 1 FROM seminar_user WHERE Seminar_id = ? AND user_id = ?";
                $check_statement = DBManager::get()->prepare($query);

                // Prepare statement that inserts the user as a deputy
                $query = "INSERT INTO seminar_user (Seminar_id, user_id, status,
                                                    label, gruppe, mkdate, position, visible)
                          VALUES (?, ?, 'tutor', ?, ?, UNIX_TIMESTAMP(), ?, 'yes')";
                $insert_statement = DBManager::get()->prepare($query);

                $count_tut=0;
                foreach ($_SESSION['sem_create_data']["sem_tut"] as $key=>$val)
                {
                    $group=select_group($_SESSION['sem_create_data']["sem_start_time"]);

                    $check_statement->execute(array(
                        $_SESSION['sem_create_data']['sem_id'],
                        $key
                    ));
                    $present = $check_statement->fetchColumn();
                    $check_statement->closeCursor();

                    if (!$present) { // User noch nicht da
                        $next_pos = get_next_position("tutor",$_SESSION['sem_create_data']["sem_id"]);

                        $insert_statement->execute(array(
                            $_SESSION['sem_create_data']['sem_id'],
                            $key,
                            $_SESSION['sem_create_data']['sem_tut_label'][$key] ?: '',
                            $group, $next_pos
                        ));

                        if ($insert_statement->rowCount() >= 1) {
                            $count_tut++;
                        }
                    }
                }
            }

            //Eintrag der Studienbereiche
            if (is_array($_SESSION['sem_create_data']["sem_bereich"])) {
                $seminar = Seminar::getInstance($_SESSION['sem_create_data']["sem_id"]);
                $seminar->setStudyAreas($_SESSION['sem_create_data']["sem_bereich"]);
                $count_bereich = sizeof($_SESSION['sem_create_data']["sem_bereich"]);
            }

            //Eintrag der zugelassen Studieng�nge
            if ($_SESSION['sem_create_data']["sem_admission"] && $_SESSION['sem_create_data']["sem_admission"] != 3) {
                if (is_array($_SESSION['sem_create_data']["sem_studg"])){
                    $query = "INSERT INTO admission_seminar_studiengang
                              VALUES (?, ?, ?)";
                    $insert_statement = DBManager::get()->prepare($query);

                    foreach($_SESSION['sem_create_data']["sem_studg"] as $key => $val) {
                        // Studiengang eintragen
                        $insert_statement->execute(array(
                            $_SESSION['sem_create_data']['sem_id'],
                            $key, $val['ratio']
                        ));
                    }
                }
            }

            //Eintrag der beteiligten Institute
            if (is_array($_SESSION['sem_create_data']["sem_bet_inst"])) {
                $query = "INSERT INTO seminar_inst
                          VALUES (?, ?)";
                $insert_statement = DBManager::get()->prepare($query);

                $count_bet_inst = 0;
                //Alle beteiligten Institute durchlaufen
                foreach ($_SESSION['sem_create_data']["sem_bet_inst"] as $tmp_array) {
                    // Institut eintragen
                    $insert_statement->execute(array(
                        $_SESSION['sem_create_data']['sem_id'],
                        $tmp_array
                    ));

                    if ($statement->rowCount() >= 1) {
                        $count_bet_inst++;
                    }
                }
            }

            //Heimat-Institut ebenfalls eintragen, wenn noch nicht da
            $query = "INSERT IGNORE INTO seminar_inst VALUES (?, ?)";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                $_SESSION['sem_create_data']['sem_id'],
                $_SESSION['sem_create_data']['sem_inst_id']
            ));

            //Standard Ordner im Foldersystem anlegen, damit Studis auch ohne Zutun des Dozenten Uploaden k&ouml;nnen
            if ($_SESSION['sem_create_data']["modules_list"]["documents"]) {
                $query = "INSERT INTO folder (folder_id, range_id, user_id, name, description, mkdate, chdate)
                          VALUES (?, ?, ?, ?, ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array(
                    md5(uniqid('sommervogel', true)),
                    $_SESSION['sem_create_data']['sem_id'],
                    $user_id,
                    _('Allgemeiner Dateiordner'),
                    _('Ablage f�r allgemeine Ordner und Dokumente der Veranstaltung')
                ));
            }

            //Vorbesprechung, falls vorhanden, in Termintabelle eintragen
            if ($_SESSION['sem_create_data']["sem_vor_termin"] <>-1) {
                $vorbesprechung = new SingleDate(array('seminar_id' => $sem->getId()));
                $vorbesprechung->setTime($_SESSION['sem_create_data']['sem_vor_termin'], $_SESSION['sem_create_data']['sem_vor_end_termin']);
                foreach ($TERMIN_TYP as $key => $val) {
                    if ($val['name'] == 'Vorbesprechung') {
                        $vorbesprechung->setDateType($key);
                    }
                }
                if ($_SESSION['sem_create_data']['sem_vor_resource_id']) {
                    $vorbesprechung->bookRoom($_SESSION['sem_create_data']['sem_vor_resource_id']);
                } else {
                    $vorbesprechung->setFreeRoomText(stripslashes($_SESSION['sem_create_data']['sem_vor_raum']));
                }

                $issue = new Issue(array('seminar_id' => $sem->getId()));
                $issue->setTitle('Vorbesprechung');
                $issue->store();

                $vorbesprechung->addIssueId($issue->getIssueId());
                $vorbesprechung->store();
            }

            //Store the additional datafields
            if (is_array($_SESSION['sem_create_data']["sem_datafields"])) {
                foreach ($_SESSION['sem_create_data']['sem_datafields'] as $id=>$val) {
                    $struct = new DataFieldStructure(array("datafield_id"=>$id, 'type'=>$val['type'], 'name'=>$val['name']));
                    $entry  = DataFieldEntry::createDataFieldEntry($struct, $_SESSION['sem_create_data']['sem_id'], $val['value']);
                    if ($entry->isValid())
                        $entry->store();
                    else
                        $errormsg .= "error�" . sprintf(_("Fehlerhafte Eingabe im Feld '%s': %s (Eintrag wurde nicht gespeichert)"), $entry->getName(), $entry->getDisplayValue());
                }
            }

            //if room-reqquest stored in the session, destroy (we don't need it anymore)
            unset($_SESSION['sem_create_data']["room_requests"]);
            //BIEST00072
            if ($_SESSION['sem_create_data']["modules_list"]["scm"]){
                $_SESSION['sem_create_data']["sem_scm_name"] = ($SCM_PRESET[1]['name'] ? $SCM_PRESET[1]['name'] : _("Informationen"));
                $_SESSION['sem_create_data']["sem_scm_id"] = md5(uniqid(rand()));

                $query = "INSERT INTO scm (scm_id, tab_name, range_id, user_id, content, mkdate, chdate)
                          VALUES (?, ?, ?, ?, ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array(
                    $_SESSION['sem_create_data']['sem_scm_id'],
                    $_SESSION['sem_create_data']['sem_scm_name'],
                    $_SESSION['sem_create_data']['sem_id'],
                    $user_id,
                    $_SESSION['sem_create_data']['sem_scm_content']
                ));
            }

            // save activation of plugins
            if (count($_SESSION['sem_create_data']["enabled_plugins"]) > 0) {
                $enabled_plugins = PluginEngine::getPlugins('StandardPlugin');
                $context = $sem->getId();

                foreach ($enabled_plugins as $plugin) {
                    $plugin_id = $plugin->getPluginId();
                    $plugin_status = in_array($plugin_id, $_SESSION['sem_create_data']['enabled_plugins']);

                    if (PluginManager::getInstance()->isPluginActivated($plugin_id, $context) != $plugin_status) {
                        PluginManager::getInstance()->setPluginActivated($plugin_id, $context, $plugin_status);
                    }
                }
            }
            //end of the seminar-creation process
            openSem($_SESSION['sem_create_data']["sem_id"]); //open Veranstaltung to administrate in the admin-area
        } else {
            $errormsg .= "error�"._("<b>Fehler:</b> Die Veranstaltung wurde schon eingetragen!")."�";
                $successful_entry=2;
        }
    }
    $level=7;
}

//Nur der Form halber... es geht weiter zur SCM-Seite
if (($form == 7) && (Request::submitted('jump_next'))) {
    if (!$_SESSION['sem_create_data']["modules_list"]["scm"] && !$_SESSION['sem_create_data']["modules_list"]["schedule"]) {
        redirect_to_course_admin($_SESSION['sem_create_data']["sem_id"]);
        die;
    } elseif (!$_SESSION['sem_create_data']["modules_list"]["scm"]) {
        header ("Location: raumzeit.php?cid=".$_SESSION['sem_create_data']["sem_id"]);
        die;
    }
    $level=8;
}

//Eintragen der Simple-Content Daten
if (($form == 8) && (Request::submitted('jump_next'))) {
    if ($_SESSION['sem_create_data']["sem_scm_content"]) { //BIEST00072
        //if content is created, we enable the module again (it was turned off above)
        $Modules->writeStatus("scm", $_SESSION['sem_create_data']["sem_id"], TRUE);
        if ($_SESSION['sem_create_data']["sem_scm_id"]) {
            $query = "UPDATE scm
                      SET content = ?, tab_name = ?, chdate = UNIX_TIMESTAMP()
                      WHERE scm_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                $_SESSION['sem_create_data']['sem_scm_content'],
                $_SESSION['sem_create_data']['sem_scm_name'],
                $_SESSION['sem_create_data']['sem_scm_id']
            ));
            $affected_rows = $statement->rowCount();
        } else {
            $_SESSION['sem_create_data']["sem_scm_id"]=md5(uniqid(rand()));

            $query = "INSERT INTO scm (scm_id, tab_name, range_id, user_id, content, mkdate, chdate)
                      VALUES (?, ?, ?, ?, ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                $_SESSION['sem_create_data']['sem_scm_id'],
                $_SESSION['sem_create_data']['sem_scm_name'],
                $_SESSION['sem_create_data']['sem_id'],
                $user_id,
                $_SESSION['sem_create_data']['sem_scm_content']
            ));
            $affected_rows = $statement->rowCount();
        }
        if ($affected_rows) {
            //if ($_SESSION['sem_create_data']["modules_list"]["schedule"]) // ## RAUMZEIT : schedule duerfte veraltet sein, muesste als komplett weg
                //header ("Location: admin_dates.php?assi=yes&ebene=sem&range_id=".$_SESSION['sem_create_data']["sem_id"]);
            //else
            redirect_to_course_admin($_SESSION['sem_create_data']["sem_id"]);
            page_close();
            die;
        } else {
            $errormsg .= "error�"._("Fehler! Der Eintrag konnte nicht erfolgreich vorgenommen werden!")."";
            $level=8;
        }
    } else {
        //if no content is created yet, we disable the module and jump to the schedule (if activated)
        //$Modules->writeStatus("scm", $_SESSION['sem_create_data']["sem_id"], FALSE); //BIEST00072
        //if ($_SESSION['sem_create_data']["modules_list"]["schedule"]) // ## RAUMZEIT : siehe oben
        //  header ("Location: admin_dates.php?assi=yes&ebene=sem&range_id=".$_SESSION['sem_create_data']["sem_id"]);
        //else
        redirect_to_course_admin($_SESSION['sem_create_data']["sem_id"]);
        die;
    }
}

//Gibt den aktuellen View an, brauchen wir in der Hilfe
$_SESSION['sem_create_data']["level"]=$level;

// Help-Keywords
switch ($level) {
    case '1':
        PageLayout::setHelpKeyword("Basis.VeranstaltungsAssistentGrunddaten");
        PageLayout::setTitle(_("Veranstaltungs-Assistent - Schritt 1: Grunddaten"));
        break;
    case '2':
        PageLayout::setHelpKeyword("Basis.VeranstaltungsAssistentPersonendatenTypUndSicherheit");
        if ($SEM_CLASS[$_SESSION['sem_create_data']["sem_class"]]["bereiche"])
            PageLayout::setTitle(_("Veranstaltungs-Assistent - Schritt 2: Personendaten, Typ, Sicherheit und Bereiche"));
        else
            PageLayout::setTitle(_("Veranstaltungs-Assistent - Schritt 2: Personendaten, Typ und Sicherheit"));
        break;
    case '3':
        PageLayout::setHelpKeyword("Basis.VeranstaltungsAssistentTermindaten");
        PageLayout::setTitle(_("Veranstaltungs-Assistent - Schritt 3: Zeiten und Termine"));
        break;
    case '4':
        PageLayout::setHelpKeyword("Basis.VeranstaltungsAssistentSonstiges");
        PageLayout::setTitle(_("Veranstaltungs-Assistent - Schritt 4: Orts- und Raumangaben"));
        break;
    case '5':
        PageLayout::setHelpKeyword("Basis.VeranstaltungsAssistentBereitZumAnlegen");
        PageLayout::setTitle(_("Veranstaltungs-Assistent - Schritt 5: Sonstige Daten"));
        break;
    case '6':
        PageLayout::setHelpKeyword("Basis.VeranstaltungsAssistentVeranstaltungAngelegt");
        PageLayout::setTitle(_("Veranstaltungs-Assistent - Schritt 6: Anlegen der Veranstaltung"));
        break;
    case '7':
        PageLayout::setHelpKeyword("Basis.VeranstaltungsAssistent");
        PageLayout::setTitle(_("Veranstaltungs-Assistent"));
        break;
    case '8':
        //This Help-Page won't help.... PageLayout::setHelpKeyword("Basis.VeranstaltungsAssistentLiteratur-UndLinkliste");
        PageLayout::setHelpKeyword("Basis.VeranstaltungsAssistent");
        PageLayout::setTitle(_("Veranstaltungs-Assistent - Schritt 7: Freie Informationsseite"));
        break;
    default:
        PageLayout::setHelpKeyword("Basis.VeranstaltungsAssistent");
        PageLayout::setTitle(_("Veranstaltungs-Assistent"));
        break;
}

if ($perm->have_perm('admin')) {
    Navigation::activateItem('/admin/course/create');
} else {
    Navigation::activateItem('/browse/my_courses/create');
}
// Start of Output
include ('lib/include/html_head.inc.php'); // Output of html head
include ('lib/include/header.php');   // Output of Stud.IP head


if (!$_SESSION['sem_create_data']["sem_class"])
    include ('lib/include/startup_checks.inc.php');

//Before we start, let's decide the category (class) of the Veranstaltung
if ((!$_SESSION['sem_create_data']["sem_class"]) && (!$level)){
    ?>
    <table width="100%" border=0 cellpadding=0 cellspacing=0>
        <?
        if ($errormsg) parse_msg($errormsg);
        ?>
        <tr>
            <td class="blank" valign="top">
                <div class="info"><br>
                <?=_("Willkommen beim Veranstaltungs-Assistenten. Der Veranstaltungs-Assistent wird Sie Schritt f&uuml;r Schritt durch die notwendigen Schritte zum Anlegen einer neuen Veranstaltung in Stud.IP leiten."); ?><br><br>
                <?=_("Bitte geben Sie zun&auml;chst an, welche Art von Veranstaltung Sie neu anlegen m&ouml;chten:"); ?>
                </div>
            </td>
            <td class="blank" align="right" valign="top" rowspan="2">
                <img src="<?= localePictureUrl('assistent.jpg') ?>" border="0">
            </td>
        </tr>
        <tr>
            <td class="blank">
                <ul>
                <? foreach (SeminarCategories::GetAll() as $category) {
                    if (!$category->course_creation_forbidden) {
                        echo "<li><b><a href=\"".URLHelper::getLink("?start_level=TRUE&class=".$category->id)."\">".$category->name."</b></a><br>";
                        echo $category->create_description."</li>";
                    }
                } ?>
                </ul>
            </td>
            <td class="blank"></td>
        </tr>
        <? if (isset($_SESSION['sem_create_data_backup']['timestamp'])) : ?>
        <tr>
            <td class="blank">
            <p class="info">
            <?=_("Sie k�nnen ein Kopie der letzten angelegten Veranstaltung anlegen:");?>
            <a href="<?=URLHelper::getLink('?start_from_backup=1')?>"><?=sprintf(_("Kopie anlegen (%s)"), htmlReady(stripslashes($_SESSION['sem_create_data_backup']['sem_name'])). ' - ' . strftime('%x %X',$_SESSION['sem_create_data_backup']['timestamp']) );?></a>.
            </p>
            </td>
            <td class="blank"></td>
        </tr>
        <? endif ?>
        <? if ($GLOBALS['STUDYGROUPS_ENABLE']) : ?>
        <tr>
            <td class="blank">
            <p class="info">
            <?=_("Sie k�nnen auch Studiengruppen anlegen, die funktional deutlich eingeschr�nkt sind und vor allem Formen selbstorganisierten Lernens unterst�tzen sollen:")?>
            <a href="<?=URLHelper::getLink('dispatch.php/course/studygroup/new')?>"><?=_("Studiengruppen anlegen")?></a>.
            </p>
            </td>
            <td class="blank"></td>
        </tr>
        <? endif ?>
    </table>
    <?
}

//Level 1: Hier werden die Grunddaten abgefragt.
elseif ((!$level) || ($level == 1))
    {
    ?>
    <table width="100%" border=0 cellpadding=0 cellspacing=0>
        <tr>
            <td class="blank" colspan=2>&nbsp;
                <?
                if ($errormsg) parse_msg($errormsg);
                ?>
            </td>
        </tr>
        <tr>
            <td class="blank" valign="top">
                <div class="info">
                <?=_("Willkommen beim Veranstaltungs-Assistenten. Der Veranstaltungs-Assistent wird Sie nun Schritt f&uuml;r Schritt durch die notwendigen Schritte zum Anlegen einer neuen Veranstaltung in Stud.IP leiten."); ?><br><br>
                <?
                if ($cmd=="do_copy") {
                    echo _("Die Daten der zu kopierenden Veranstaltung werden &uuml;bernommen. Bitte &auml;ndern Sie die Informationen, die sich f&uuml;r die kopierte Veranstaltung ergeben.");
                }
                ?><br><br>
                <b><?=_("Schritt 1: Grunddaten der Veranstaltung angeben"); ?></b><br><br>
                <? printf (_("Alle mit einem Sternchen%smarkierten Felder <b>m&uuml;ssen</b> ausgef&uuml;llt werden, um eine Veranstaltung anlegen zu k&ouml;nnen.")."<br><br>", "&nbsp;<font color=\"red\" size=+1><b>*</b></font>&nbsp;");?>
                </div>
            </td>
            <td class="blank" align="right" valign="top">
                <img src="<?= localePictureUrl('hands01.jpg') ?>" border="0">
            </td>
        </tr>
        <tr>
            <td class="blank" colspan=2>
            <form method="POST" action="<? echo URLHelper::getLink() ?>">
            <?= CSRFProtection::tokenTag() ?>
            <input type="hidden" name="form" value=1>
                <table cellspacing=0 cellpadding=2 border=0 width="99%" align="center">
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%">
                            &nbsp;
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="90%" align="center" colspan=3>
                            &nbsp; <?= Button::create(_('Weiter').' >>', 'jump_next') ?>
                        </td>
                    </tr>
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%" align="right">
                            <?=_("Name der Veranstaltung:"); ?>
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="90%"  colspan=3>
                            &nbsp; <input type="text" name="sem_name" size=58 maxlength=254 value="<? echo htmlReady(stripslashes($_SESSION['sem_create_data']["sem_name"])) ?>">
                            <?= tooltipIcon(_("Bitte geben Sie hier einen aussagekr�ftigen, aber m�glichst knappen Titel f�r die Veranstaltung ein. Dieser Eintrag erscheint innerhalb Stud.IPs durchgehend zur Identifikation der Veranstaltung.")) ?>
                            <font color="red" size=+2>*</font>
                        </td>
                    </tr>
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%" align="right">
                            <?=_("Untertitel:"); ?>
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="90%"  colspan=3>
                            &nbsp; <input type="text" name="sem_untert" size=58 maxlength=254 value="<? echo htmlReady(stripslashes($_SESSION['sem_create_data']["sem_untert"]))?>">
                            <?= tooltipIcon(_("Der Untertitel erm�glicht eine genauere Beschreibung der Veranstaltung.")) ?>
                        </td>
                    </tr>
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%" align="right">
                            <?=_("Typ der Veranstaltung:"); ?>
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="90%" colspan=3>
                            &nbsp; <select name="sem_status">
                            <?
                                foreach ($SEM_TYPE as $sem_type_id => $sem_type) {
                                    if ($sem_type["class"] == $_SESSION['sem_create_data']["sem_class"])
                                        printf("<option %s value=%s>%s</option>",
                                               $_SESSION['sem_create_data']["sem_status"] == $sem_type_id
                                                 ? "selected"
                                                 : "",
                                               $sem_type_id,
                                               $sem_type["name"]);
                                }
                            ?>
                            </select> <br>
                            &nbsp; <font size="-1"> <?=_("in der Kategorie"); ?> <b><? echo $SEM_CLASS[$_SESSION['sem_create_data']["sem_class"]]["name"] ?></b></font>
                            <?= tooltipIcon(_("�ber den Typ der Veranstaltung werden die Veranstaltungen innerhalb von Listen gruppiert.")) ?>
                            <font color="red" size=+2>*</font>
                        </td>
                    </tr>
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%" align="right">
                            <?=_("Art der Veranstaltung:"); ?>
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="90%" colspan=3>
                            &nbsp; <input type="text" name="sem_art" size=30 maxlength=254 value="<? echo htmlReady(stripslashes($_SESSION['sem_create_data']["sem_art"])) ?>">
                            <font size=-1><?=_("(eigene Beschreibung)"); ?></font>
                            <?= tooltipIcon(_("Hier k�nnen Sie eine frei w�hlbare Bezeichnung f�r die Art der Veranstaltung w�hlen.")) ?>
                        </td>
                    </tr>
                    <?
                    if (!$SEM_CLASS[$_SESSION['sem_create_data']["sem_class"]]["compact_mode"]) {
                    ?>
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%" align="right">
                            <?=_("Veranstaltungsnummer:"); ?>
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="30%">
                            &nbsp; <input type="text" name="sem_nummer" size=20 maxlength=32 value="<? echo  htmlReady(stripslashes($_SESSION['sem_create_data']["sem_nummer"])) ?>">
                            <?= tooltipIcon(_("Fall Sie eine eindeutige Veranstaltungsnummer f�r diese Veranstaltung kennen, geben Sie diese bitte hier ein.")) ?>
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%" align="right">
                            <?=_("ECTS-Punkte:"); ?>
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="60%">
                            &nbsp; <input type="text" name="sem_ects" size=6 maxlength=32 value="<? echo  htmlReady(stripslashes($_SESSION['sem_create_data']["sem_ects"])) ?>">
                            <?= tooltipIcon(_("ECTS-Punkte, die in dieser Veranstaltung erreicht werden k�nnen.")) ?>
                        </td>
                    </tr>
                    <?
                    }
                    if (!$SEM_CLASS[$_SESSION['sem_create_data']["sem_class"]]["compact_mode"]) {
                    ?>
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%" align="right">
                            <?=_("Teilnahme- beschr&auml;nkung:"); ?>
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="30%" colspan=1>
                            &nbsp; <input type="radio" name="sem_admission" value=0 <? if (!$_SESSION['sem_create_data']["sem_admission"]) echo 'checked'?>>
                            <?=_("keine"); ?> &nbsp; <br>
                            &nbsp; <input type="radio" name="sem_admission" value=2 <? if ($_SESSION['sem_create_data']["sem_admission"]=="2") echo 'checked'?>>
                            <?=_("nach Anmeldereihenfolge"); ?> <br>
                            &nbsp; <input type="radio" name="sem_admission" value=1 <? if ($_SESSION['sem_create_data']["sem_admission"]=="1") echo 'checked'?>>
                            <?=_("per Losverfahren"); ?>&nbsp;
                            <?= tooltipIcon(_("Sie k�nnen die Anzahl der Teilnehmenden beschr�nken. M�glich ist die Zulassung von Interessierten �ber das Losverfahren oder �ber die Reihenfolge der Anmeldung. Sie k�nnen sp�ter Angaben �ber zugelassene Teilnehmer machen.")) ?>
                            <br>
                            &nbsp; <input type="radio" name="sem_admission" value=3 <? if ($_SESSION['sem_create_data']["sem_admission"]=="3") echo 'checked'?>>
                            <?=_("gesperrt"); ?>&nbsp;
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%" align="right">
                            <?=_("maximale Teilnehmeranzahl:"); ?>
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="50%">
                            &nbsp; <input type="text" name="sem_turnout" size=6 maxlength=5 value="<? echo (int)$_SESSION['sem_create_data']["sem_turnout"] ?>">
                            <?= tooltipIcon(_("Geben Sie hier die maximale Teilnehmerzahl an. Stud.IP kann auf Wunsch f�r Sie ein Anmeldeverfahren starten, wenn Sie �Teilnahmebeschr�nkung: per Losverfahren / nach Anmeldereihenfolge� benutzen.")) ?>
                        </td>
                    </tr>
                    <tr<? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%" align="right">
                            <?=_("Anmeldemodus:"); ?>
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="90%" colspan=3>
                            &nbsp; <input type="radio" name="sem_payment" value=0 <? if ($_SESSION['sem_create_data']["sem_payment"] == 0) echo 'checked'?>>
                            <?= _("direkter Eintrag"); ?>&nbsp;
                            <?= tooltipIcon(_("Neue Teilnehmer werden direkt in die Veranstaltung eingetragen.")) ?>
                            &nbsp; <input type="radio" name="sem_payment" value=1 <? if ($_SESSION['sem_create_data']["sem_payment"] == 1) echo 'checked'?>>
                            <?= _("vorl&auml;ufiger Eintrag"); ?>&nbsp;
                            <?= tooltipIcon(_("Neue Teilnehmer bekommen den Status \"vorl�ufig aktzeptiert\". Sie k�nnen von Hand die zugelassenen Teilnehmer ausw�hlen. Vorl�ufig akzeptierte Teilnehmer haben keinen Zugriff auf die Veranstaltung.")) ?>
                        </td>
                    </tr>
                    <?
                    }
                    ?>
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%" align="right">
                            <?=_("Beschreibung/ Kommentar:"); ?>
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="90%" colspan=3>
                            &nbsp; <textarea name="sem_desc" cols=58 rows=6><? echo htmlReady(stripslashes($_SESSION['sem_create_data']["sem_desc"])) ?></textarea>
                            <?= tooltipIcon(_("Hier geben Sie bitte den eigentlichen Kommentartext der Veranstaltung (analog zum Vorlesungskommentar) ein.")) ?>
                        </td>
                    </tr>
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%" align="right">
                            <?=_("Semester:"); ?>
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="20%">
                            &nbsp;
                            <?
                            $all_semester = $semester->getAllSemesterData();

                            echo "<select name=\"sem_start_time\">";
                            if(!$GLOBALS['ASSI_SEMESTER_PRESELECT'])
                            {
                                echo "<option value=\"-1\" >["._('bitte ausw�hlen')."]</option>";
                            }
                            foreach ($all_semester as $key => $semester) {
                                if ((!$semester["past"]) && ($semester["ende"] > time())) {
                                    if ($_SESSION['sem_create_data']["sem_start_time"] ==$semester["beginn"]) {
                                        echo "<option value=".$semester["beginn"]." selected>", htmlReady($semester["name"]), "</option>";
                                    } else {
                                        echo "<option value=".$semester["beginn"].">", htmlReady($semester["name"]), "</option>";
                                    }
                                }
                            }
                            echo "</select>";

                            ?>
                            <?= tooltipIcon(_("Bitte geben Sie hier ein, welchem Semester die Veranstaltung zugeordnet werden soll.")) ?>
                            <font color="red" size=+2>*</font>
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%" align="right">
                            <?=_("Dauer:"); ?>
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="60%">
                            &nbsp; <select name="sem_duration_time">
                            <?
                                if ($_SESSION['sem_create_data']["sem_duration_time"] == 0)
                                    echo "<option value=0 selected>"._("1 Semester")."</option>";
                                else
                                    echo "<option value=0>"._("1 Semester")."</option>";
                                for ($i=0; $i<sizeof($all_semester); $i++)
                                    if ((!$all_semester[$i]["past"]) && ($all_semester[$i]["semester_id"] != Semester::findCurrent()->semester_id) && (($all_semester[$i]["vorles_ende"] > time())))
                                        {
                                        if (($_SESSION['sem_create_data']["sem_start_time"] + $_SESSION['sem_create_data']["sem_duration_time"]) == $all_semester[$i]["beginn"])
                                            {
                                            if (!$_SESSION['sem_create_data']["sem_duration_time"] == 0)
                                                echo "<option value=",$all_semester[$i]["beginn"], " selected>"._("bis")." ", $all_semester[$i]["name"], "</option>";
                                            else
                                                echo "<option value=",$all_semester[$i]["beginn"], ">"._("bis")." ", $all_semester[$i]["name"], "</option>";
                                            }
                                        else
                                            echo "<option value=",$all_semester[$i]["beginn"], ">"._("bis")." ", htmlReady($all_semester[$i]["name"]), "</option>";
                                        }
                                if ($_SESSION['sem_create_data']["sem_duration_time"] == -1)
                                    echo "<option value=-1 selected>"._("unbegrenzt")."</option>";
                                else
                                    echo "<option value=-1>"._("unbegrenzt")."</option>";
                            ?>
                            </select>
                            <?= tooltipIcon(_("Falls die Veranstaltung mehrere Semester l�uft, k�nnen Sie hier das Endsemester w�hlen. Dauerveranstaltungen k�nnen �ber die entsprechende Einstellung markiert werden.")) ?>
                        </td>
                    </tr>
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%" align="right">
                            <?=_("Turnus:"); ?>
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="30%" colspan=3>
                            &nbsp; <select  name="term_art">
                            <?
                            if ($_SESSION['sem_create_data']["term_art"] == 0)
                                echo "<option selected value=\"0\">"._("regelm&auml;&szlig;ig")."</option>";
                            else
                                echo "<option value=\"0\">"._("regelm&auml;&szlig;ig")."</option>>";
                            if ($_SESSION['sem_create_data']["modules_list"]["schedule"]) {
                                if ($_SESSION['sem_create_data']["term_art"] == 1)
                                    echo "<option selected value=\"1\">"._("unregelm&auml;&szlig;ig oder Blockveranstaltung")."</option>";
                                else
                                    echo "<option value=\"1\">"._("unregelm&auml;&szlig;ig oder Blockveranstaltung")."</option>";
                            }
                            if ($_SESSION['sem_create_data']["term_art"] == -1)
                                echo "<option selected value=\"-1\">"._("keine Termine eingeben")."</option>";
                            else
                                echo "<option value=\"-1\">"._("keine Termine eingeben")."</option>";
                            ?>
                            </select>
                            <?= tooltipIcon(_("Bitte w�hlen Sie hier aus, ob die Veranstaltung regelm��ig stattfindet, oder ob es nur Sitzungen an bestimmten Terminen gibt (etwa bei einem Blockseminar)")) ?>
                            <font color="red" size=+2>*</font>
                        </td>
                    </tr>
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%" align="right">
                            <?=_("Heimat-Einrichtung:"); ?>
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="90%" colspan=3>
                            &nbsp;
                            <?
                            // Prepare inner statement that obtains all institutes
                            // for a given faculty id
                            $query = "SELECT Institut_id, Name
                                      FROM Institute a
                                      WHERE fakultaets_id = ? AND Institut_id != fakultaets_id
                                      ORDER BY Name";
                            $institute_statement = DBManager::get()->prepare($query);

                            // Prepare outer statement
                            $parameters = array();
                            if (!$perm->have_perm('admin')) {
                                $query = "SELECT Name, a.Institut_id, a.Institut_id = fakultaets_id AS is_fak, inst_perms
                                          FROM user_inst AS a
                                          LEFT JOIN Institute USING (institut_id)
                                          WHERE user_id = ? AND inst_perms = 'dozent'
                                          ORDER BY is_fak, Name";
                                $parameters[] = $user_id;
                            } else if (!$perm->have_perm('root')) {
                                $query = "SELECT Name, a.Institut_id, a.Institut_id = fakultaets_id AS is_fak, inst_perms
                                          FROM user_inst AS a
                                          LEFT JOIN Institute USING (institut_id)
                                          WHERE user_id = ? AND inst_perms = 'admin'
                                          ORDER BY is_fak, Name";
                                $parameters[] = $user_id;
                            } else {
                                $query = "SELECT Name, Institut_id, 1 AS is_fak, 'admin' AS inst_perms
                                          FROM Institute
                                          WHERE Institut_id = fakultaets_id
                                          ORDER BY Name";
                            }
                            $statement = DBManager::get()->prepare($query);
                            $statement->execute($parameters);
                            $options = $statement->fetchAll(PDO::FETCH_ASSOC);

                            if (count($options)) {
                                echo '<select name="sem_inst_id">';
                                foreach ($options as $option) {
                                    printf('<option %s style="%s" value="%s">%s</option>',
                                           $option['Institut_id'] == $_SESSION['sem_create_data']["sem_inst_id"] ? 'selected' : '',
                                           $option['is_fak'] ? 'font-weight:bold;' : '',
                                           $option['Institut_id'],
                                           htmlReady(my_substr($option['Name'], 0, 60)));

                                    if ($option['is_fak'] && $option['inst_perms'] == 'admin') {
                                        $institute_statement->execute(array($option['Institut_id']));
                                        while ($row = $institute_statement->fetch(PDO::FETCH_ASSOC)) {
                                            printf('<option %s value="%s">&nbsp;&nbsp;&nbsp;&nbsp;%s</option>',
                                                   $row['Institut_id'] == $_SESSION['sem_create_data']["sem_inst_id"] ? 'selected' : '',
                                                   $row['Institut_id'],
                                                   htmlReady(my_substr($row['Name'], 0, 60)));
                                        }
                                        $institute_statement->closeCursor();
                                    }
                                }
                                echo '</select>';
                            }
                            ?>
                            <?= tooltipIcon(_("Die Heimat-Einrichtung ist die Einrichtung, die offiziell f�r die Veranstaltung zust�ndig ist.")) ?>
                            <font color="red" size=+2>*</font>
                        </td>
                    </tr>
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%" align="right">
                            <?=_("Beteiligte Einrichtungen:"); ?>
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="90%" colspan=3>
                            &nbsp; <select  name="sem_bet_inst[]" MULTIPLE size=7>
                            <?
                                // Prepare statement that reads all institutes for
                                // a given faculty id
                                $query = "SELECT Institut_id, Name
                                          FROM Institute
                                          WHERE fakultaets_id = ? AND Institut_id != fakultaets_id
                                          ORDER BY Name";
                                $institute_statement = DBManager::get()->prepare($query);

                                // Prepare and execute statement that obtains
                                // all faculties
                                $query = "SELECT Institut_id, Name
                                          FROM Institute
                                          WHERE Institut_id = fakultaets_id
                                          ORDER BY Name";
                                $statement = DBManager::get()->query($query);
                                $options = $statement->fetchAll(PDO::FETCH_ASSOC);

                                foreach ($options as $option) {
                                    $selected = '';
                                    if (is_array($_SESSION['sem_create_data']['sem_bet_inst'])
                                        && in_array($option['Institut_id'],$_SESSION['sem_create_data']['sem_bet_inst']))
                                    {
                                        $selected = 'selected';
                                    }
                                    printf('<option %s style="font-weight:bold;" value="%s">%s</option>',
                                           $selected,
                                           $option['Institut_id'],
                                           htmlReady(my_substr($option['Name'], 0, 60)));

                                    $institute_statement->execute(array($option['Institut_id']));
                                    while ($row = $institute_statement->fetch(PDO::FETCH_ASSOC)) {
                                        $selected = '';
                                        if (is_array($_SESSION['sem_create_data']['sem_bet_inst'])
                                            && in_array($row['Institut_id'], $_SESSION['sem_create_data']['sem_bet_inst']))
                                        {
                                            $selected = 'selected';
                                        }
                                        printf('<option %s value="%s">&nbsp;&nbsp;&nbsp;&nbsp;%s</option>',
                                               $selected,
                                               $row['Institut_id'],
                                               htmlReady(my_substr($row['Name'], 0, 60)));
                                    }
                                    $institute_statement->closeCursor();
                                }
                            ?>
                            </select>
                            <?= tooltipIcon(sprintf(_("Bitte markieren Sie hier alle Einrichtungen, an denen die Veranstaltung ebenfalls angeboten wird. Bitte beachten Sie: Sie k�nnen sp�ter nur %s aus den Einrichtungen ausw�hlen, die entweder als Heimat- oder als beteiligte Einrichtung markiert worden sind. Sie k�nnen mehrere Eintr�ge markieren, indem Sie die STRG bzw. APPLE Taste gedr�ckt halten und dann auf die Eintr�ge klicken."), get_title_for_status('dozent', 2, $_SESSION['sem_create_data']["sem_status"]))) ?>
                        </td>
                    </tr>
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%">
                            &nbsp;
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="90%" align="center" colspan=3>
                            &nbsp; <?= Button::create(_('Weiter').' >>', 'jump_next') ?>
                        </td>
                    </tr>
                </table>
            </form>
            </td>
        </tr>
    </table>
    <?
    }

//Level 2: Hier werden weitere Einzelheiten (Personendaten und Zeiten) abgefragt
if ($level == 2)
    {
    ?>
    <table width="100%" border=0 cellpadding=0 cellspacing=0>
        <tr >
            <td class="blank" colspan=2>&nbsp;
                <?
                if ($errormsg) parse_msg($errormsg);
                ?>
            </td>
        </tr>
        <tr>
            <td class="blank" valign="top">
                <div class="info">
                <?
                if ($SEM_CLASS[$_SESSION['sem_create_data']["sem_class"]]["bereiche"])
                    echo "<b>"._("Schritt 2: Personendaten, Studienbereiche und weitere Angaben zur Veranstaltung")."</b><br><br>";
                else
                    echo "<b>"._("Schritt 2: Personendaten und weitere Angaben zur Veranstaltung")." </b><br><br>";
                ?>
                <font size=-1><? printf (_("Alle mit einem Sternchen%smarkierten Felder <b>m&uuml;ssen</b> ausgef&uuml;llt werden, um eine Veranstaltung anlegen zu k&ouml;nnen.")."</font><br><br>", "&nbsp;</font><font color=\"red\" size=+1><b>*</b></font><font size=-1>&nbsp;");?>
                </div>
            </td>
            <td class="blank" align="right" valign="top">
                <img src="<?= localePictureUrl('hands02.jpg') ?>" border="0">
            </td>
        </tr>
        <tr>
            <td class="blank" colspan=2>
            <form method="POST" action="<? echo URLHelper::getLink() ?>#anker">
            <?= CSRFProtection::tokenTag() ?>
            <input type="hidden" name="form" value=2>
                <table width ="99%" cellspacing=0 cellpadding=2 border=0 align="center">
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%">
                            &nbsp;
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="90%" align="center" colspan=3>
                            &nbsp; <?= LinkButton::create('<< '._('Zur�ck'), URLHelper::getUrl('?jump_back=1&form=' . $level)) .'&nbsp;'. Button::create(_('Weiter').' >>', 'jump_next') ?>
                        </td>
                    </tr>
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%" align="right">
                        <?
                        echo get_title_for_status('dozent', count($_SESSION['sem_create_data']["sem_doz"]), $_SESSION['sem_create_data']["sem_status"]);
                        ?>
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="40%">
                            <?
                            if (sizeof($_SESSION['sem_create_data']["sem_doz"]) >0) {
                        asort($_SESSION['sem_create_data']["sem_doz"]);
                        echo "<table>";
                        $i = 0;
                                foreach($_SESSION['sem_create_data']["sem_doz"] as $key=>$val) {
                                    echo "<tr>";
                                     $href = "?delete_doz=".get_username($key)."#anker";
                                     echo "<td>";
                                     echo "<a href='".URLHelper::getLink($href)."'>";
                                     echo Assets::img('icons/16/blue/trash.png');
                                     echo "</a>";
                                     echo "</td>";

                           // move up (if not first)
                           echo "<td>";
                           if ($i > 0)
                           {
                                                            $href = "?moveup_doz=".get_username($key)."&foo=".time()."#anker";
                                                            echo "<a href='".URLHelper::getLink($href)."'>";
                                                            echo Assets::img('icons/16/yellow/arr_2up.png');
                                                            echo "</a>";
                           }
                           echo "</td>";
                           // move down (if not last)
                           echo "<td>";
                           if ($i < (sizeof($_SESSION['sem_create_data']["sem_doz"]) - 1))
                           {
                                                            $href = "?movedown_doz=".get_username($key)."&foo=".time()."#anker";
                                                            echo "<a href='".URLHelper::getLink($href)."'>";
                                                            echo Assets::img('icons/16/yellow/arr_2down.png');
                                                            echo "</a>";
                           }
                           echo "</td>";
                              echo "<td>";
                              $label = $_SESSION['sem_create_data']["sem_doz_label"][$key]
                                  ? " - ".$_SESSION['sem_create_data']["sem_doz_label"][$key]
                                  : "";
                              echo "<font size=\"-1\"><b>". get_fullname($key, "full_rev", true).
                           " (". get_username($key) . ")</b>".htmlReady($label)."</font>";

                              echo "</td>";

                                   echo "</tr>";// end of row
                           $i++;
                        }
                           echo "</table>";
                     //     printf ("&nbsp; <a href=\"%s\"><img src=\"".$GLOBALS['ASSETS_URL']."images/icons/16/blue/trash.png\" border=\"0\"></a> &nbsp; <font size=\"-1\"><b>%s (%s)&nbsp; &nbsp; <br>", URLHelper::getLink("?delete_doz=".get_username($key)), get_fullname($key,"full_rev",true), get_username($key));
                         } else {
                                printf ("<font size=\"-1\">&nbsp;  ". sprintf(_("Keine %s gew&auml;hlt."), get_title_for_status('dozent', 2, $_SESSION['sem_create_data']["sem_status"]))."</font><br >");
                            }
                            ?>
                            &nbsp; <?= tooltipIcon(sprintf(_("Die Namen der Benutzer, die die Veranstaltung leiten. Nutzen Sie die Suchfunktion, um weitere Eintragungen vorzunehmen, oder das M�lltonnensymbol, um Eintr�ge zu l�schen."), get_title_for_status('dozent', 2, $_SESSION['sem_create_data']["sem_status"]))) ?>
                            <font color="red" size=+2>*</font>
                        </td>
                <td <? echo $cssSw->getFullClass() ?> width="50%" colspan="2">
                <?php
                print sprintf(_("%s hinzuf&uuml;gen"), get_title_for_status('dozent', 1, $seminar_type));
                print "<br><input type=\"IMAGE\" src=\"".Assets::image_path('icons/16/yellow/arr_2left.png')."\" ".tooltip(_("NutzerIn hinzuf�gen"))." border=\"0\" name=\"send_doz\"> ";

                if (SeminarCategories::getByTypeId($_SESSION['sem_create_data']["sem_status"])->only_inst_user) {
                    $search_template = "user_inst";
                } else {
                    $search_template = "user";
                }

                $searchForDozentUser = new PermissionSearch($search_template,
                                       sprintf(_("%s ausw�hlen"), get_title_for_status('dozent', 1, $seminar_type)),
                                       "username",
                                       array('permission' => 'dozent',
                                             'exclude_user' => array_keys((array)$_SESSION['sem_create_data']["sem_doz"]),
                                             'institute' => array_merge((array)$_SESSION['sem_create_data']["sem_inst_id"], (array)$_SESSION['sem_create_data']["sem_bet_inst"])
                                          )
                                       );
                print QuickSearch::get("add_doz", $searchForDozentUser)
                            ->withButton(array('search_button_name' => 'search_doz', 'reset_button_name' => 'reset_search'))
                            ->render();
                print "<input type=\"text\" name=\"sem_doz_label\" placeholder=\""._("Funktion")."\">";

                ?>
                <br><font size=-1><?=_("Geben Sie zur Suche den Vor-, Nach- oder Benutzernamen ein.")?></font>
                </td>
            </tr>
                 <?php if ($deputies_enabled) { ?>
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%" align="right">
                        <?
                        echo get_title_for_status('deputy', 2, $_SESSION['sem_create_data']["sem_status"]);
                        ?>
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="40%">
                            <?
                            if (sizeof($_SESSION['sem_create_data']["sem_dep"]) >0) {
                        asort($_SESSION['sem_create_data']["sem_dep"]);
                        echo "<table>";
                        $i = 0;
                                foreach($_SESSION['sem_create_data']["sem_dep"] as $key=>$val) {
                                                            echo "<tr>";
                                                            echo "<td>";

                                                            $href = "?delete_dep=".get_username($key)."#anker";
                                                            echo "<a href='".URLHelper::getLink($href)."'>";
                                                            echo Assets::img('icons/16/blue/trash.png');
                                                            echo "</a>";
                                                            echo "</td>";
                              echo "<td>";
                              echo "<font size=\"-1\">&nbsp;<b>".htmlReady($val['fullname']).
                           " (". $val['username'] . ", "._("Status").": ".$val['perms'].")</b></font>";

                              echo "</td>";

                                   echo "</tr>";// end of row
                           $i++;
                        }
                        echo "</table>";
                            } else {
                                printf ("<font size=\"-1\">&nbsp;  ". sprintf(_("Keine %s gew&auml;hlt."), get_title_for_status('deputy', 2, $_SESSION['sem_create_data']["sem_status"]))."</font><br >");
                            }
                            ?>
                            &nbsp; <?= tooltipIcon(sprintf(_("Personen, die Sie hier eintragen, haben dieselben Rechte wie %s, erscheinen aber nicht nach au�en. Nutzen Sie die Suchfunktion (Lupensymbol), um weitere Eintragungen vorzunehmen, oder das M�lltonnensymbol, um Eintr�ge zu l�schen."), get_title_for_status('dozent', 2, $_SESSION['sem_create_data']["sem_status"]))) ?>
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="50%" colspan="2">
                        <?php
                        print _("Vertretung hinzuf&uuml;gen");
                        print "<br><input type=\"IMAGE\" src=\"".Assets::image_path('icons/16/yellow/arr_2left.png')."\" ".tooltip(_("NutzerIn hinzuf�gen"))." border=\"0\" name=\"send_dep\"> ";

                        $deputysearch = new PermissionSearch('user',
                                        sprintf(_("%s ausw�hlen"), get_title_for_status('deputy', 1, $seminar_type)),
                                        'username',
                                        array('permission' => getValidDeputyPerms(),
                                              'exclude_user' => array_keys((array)$_SESSION['sem_create_data']["sem_dep"])
                                            )
                                        );
                        print QuickSearch::get("add_dep", $deputysearch)
                              ->withButton(array('search_button_name' => 'search_dep', 'reset_button_name' => 'reset_search'))
                              ->render();
                        ?>
                        <br><font size=-1><?=_("Geben Sie zur Suche den Vor-, Nach- oder Benutzernamen ein.")?></font>
                        </td>
                    </tr>
                    <?php } ?>
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%" align="right">
                        <?
                        echo get_title_for_status('tutor', count($_SESSION['sem_create_data']["sem_tut"]), $_SESSION['sem_create_data']["sem_status"]);
                        ?>
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="40%">
                            <?
                            if (sizeof($_SESSION['sem_create_data']["sem_tut"]) >0) {
                        asort($_SESSION['sem_create_data']["sem_tut"]);
                        echo "<table>";
                        $i = 0;
                                foreach($_SESSION['sem_create_data']["sem_tut"] as $key=>$val) {
                                                            echo "<tr>";
                                                            echo "<td>";

                                                            $href = "?delete_tut=".get_username($key)."#anker";
                                                            echo "<a href='".URLHelper::getLink($href)."'>";
                                                            echo Assets::img('icons/16/blue/trash.png');
                                                            echo "</a>";
                                                            echo "</td>";

                           // move up (if not first)
                           echo "<td>";
                           if ($i > 0)
                           {
                                                            $href = "?moveup_tut=".get_username($key)."&foo=".time()."#anker";
                                                            echo "<a href='".URLHelper::getLink($href)."'>";
                                                            echo Assets::img('icons/16/yellow/arr_2up.png');
                                                            echo "</a>";
                           }
                           echo "</td>";
                           // move down (if not last)
                           echo "<td>";
                           if ($i < (sizeof($_SESSION['sem_create_data']["sem_tut"]) - 1))
                           {
                                                            $href = "?movedown_tut=".get_username($key)."&foo=".time()."#anker";
                                                            echo "<a href='".URLHelper::getLink($href)."'>";
                                                            echo Assets::img('icons/16/yellow/arr_2down.png');
                                                            echo "</a>";
                           }
                           echo "</td>";
                              echo "<td>";
                              $label = $_SESSION['sem_create_data']["sem_tut_label"][$key]
                                  ? " - ".$_SESSION['sem_create_data']["sem_tut_label"][$key]
                                  : "";
                              echo "<font size=\"-1\"><b>".get_fullname($key, "full_rev",true).
                           " (". get_username($key) . ")</b>".htmlReady($label)."</font>";

                              echo "</td>";

                                   echo "</tr>";// end of row
                           $i++;
                        }
                        echo "</table>";
                            } else {
                                printf ("<font size=\"-1\">&nbsp;  ". sprintf(_("Keine %s gew&auml;hlt."), get_title_for_status('tutor', 2, $_SESSION['sem_create_data']["sem_status"]))."</font><br >");
                            }
                            ?>
                            &nbsp; <?= tooltipIcon(sprintf(_("Die Namen der %s, die in der Veranstaltung weitergehende Rechte erhalten (meist studentische Hilfskr�fte). Nutzen Sie die Suchfunktion (Lupensymbol), um weitere Eintragungen vorzunehmen, oder das M�lltonnensymbol, um Eintr�ge zu l�schen."), get_title_for_status('tutor', 2, $_SESSION['sem_create_data']["sem_status"]))) ?>
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="50%" colspan="2">
                        <?php
                        print sprintf(_("%s hinzuf&uuml;gen"), get_title_for_status('tutor', 1, $seminar_type));
                        print "<br><input type=\"IMAGE\" src=\"".Assets::image_path('icons/16/yellow/arr_2left.png')."\" ".tooltip(_("NutzerIn hinzuf�gen"))." border=\"0\" name=\"send_tut\"> ";

                        $searchForTutorUser = new PermissionSearch($search_template,
                                       sprintf(_("%s ausw�hlen"), get_title_for_status('tutor', 1, $seminar_type)),
                                       "username",
                                       array('permission' => array('tutor','dozent'),
                                             'exclude_user' => array_merge(array_keys((array)$_SESSION['sem_create_data']["sem_tut"]), array_keys((array)$_SESSION['sem_create_data']["sem_doz"])),
                                             'institute' => array_merge((array)$_SESSION['sem_create_data']["sem_inst_id"], (array)$_SESSION['sem_create_data']["sem_bet_inst"])
                                          )
                                       );
                        print QuickSearch::get("add_tut", $searchForTutorUser)
                              ->withButton(array('search_button_name' => 'search_tut', 'reset_button_name' => 'reset_search'))
                              ->render();

                        ?>
                        <input type="text" name="sem_tut_label" placeholder="<?= _('Funktion') ?>">
                        <br><font size=-1><?=_("Geben Sie zur Suche den Vor-, Nach- oder Benutzernamen ein.")?></font>
                        </td>
                    </tr>

                    <? if ($SEM_CLASS[$_SESSION['sem_create_data']["sem_class"]]["bereiche"]) : ?>
                        <tr <? $cssSw->switchClass() ?>>
                            <td colspan="4" class="<? echo $cssSw->getClass() ?>">

                                <?= _("Studienbereiche:") ?>
                                <?= tooltipIcon( _("Sie m�ssen mindestens einen Studienbereich ausw�hlen! Der Studienbereich legt z.B. fest, wo die Veranstaltung im Vorlesungsverzeichnis auftaucht.")) ?>
                                <font color="red" size=+2>*</font>

                                <?
                                $trails_views = $GLOBALS['STUDIP_BASE_PATH'] . '/app/views';
                                $factory = new Flexi_TemplateFactory($trails_views);
                                list(,$smm_semester_id) = array_values(SemesterData::GetInstance()->getSemesterDataByDate($_SESSION['sem_create_data']["sem_start_time"]));

                                echo $factory->render('course/study_areas/form',
                                                      array('course_id' => '-',
                                                            'selection' => $area_selection,
                                                            'semester_id' => $smm_semester_id));
                                ?>

                            </td>
                        </tr>
                    <? endif ?>

                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%" align="right">
                            <?=_("Lesezugriff:"); ?>
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="90%" colspan=3>
                            <?
                            if (!$_SESSION['sem_create_data']["sem_admission"]) {
                                if (!isset($_SESSION['sem_create_data']["sem_sec_lese"]) || $_SESSION['sem_create_data']["sem_sec_lese"]==3)
                                    $_SESSION['sem_create_data']["sem_sec_lese"] = "1"; //Vorgabe: nur angemeldet oder es war Teilnahmebegrenzung gesetzt
                                if (get_config('ENABLE_FREE_ACCESS')){
                                    ?>
                                    <input type="radio" name="sem_sec_lese" value="0" <?php print $_SESSION['sem_create_data']["sem_sec_lese"] == 0 ? "checked" : ""?>> <?=_("freier Zugriff"); ?> &nbsp;
                                    <?
                                } else {
                                    ?>
                                    <font color=#BBBBBb>&nbsp; &nbsp; &nbsp;  <?=_("freier Zugriff")?> &nbsp;</font>
                                    <?
                                }
                                ?>
                                <input type="radio" name="sem_sec_lese" value="1" <?php print $_SESSION['sem_create_data']["sem_sec_lese"] == 1 ? "checked" : ""?>> <?=_("in Stud.IP angemeldet"); ?> &nbsp;
                                <input type="radio" name="sem_sec_lese" value="2" <?php print $_SESSION['sem_create_data']["sem_sec_lese"] == 2 ? "checked" : ""?>> <?=_("nur mit Passwort"); ?> &nbsp;
                                <?= tooltipIcon(_("Hier geben Sie an, ob der Lesezugriff auf die Veranstaltung frei (jeder), normal beschr�nkt (nur registrierte Stud.IP-User) oder nur mit einem speziellen Passwort m�glich ist.")) ?>
                            <?
                            } else
                                print "&nbsp; <font size=-1>"._("Leseberechtigung nach erfolgreichem Anmeldeprozess")."</font>"
                            ?>
                        </td>
                    </tr>
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%" align="right">
                            <?=_("Schreibzugriff:"); ?>
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="90%" colspan=3>
                            <?
                            if (!$_SESSION['sem_create_data']["sem_admission"]) {
                                if (!isset($_SESSION['sem_create_data']["sem_sec_schreib"]) || $_SESSION['sem_create_data']["sem_sec_schreib"]==3)
                                    $_SESSION['sem_create_data']["sem_sec_schreib"] = "1";  //Vorgabe: nur angemeldet
                                if (get_config('ENABLE_FREE_ACCESS') && $SEM_CLASS[$_SESSION['sem_create_data']["sem_class"]]["write_access_nobody"]) {
                                    ?>
                                <input type="radio" name="sem_sec_schreib" value="0" <?php print $_SESSION['sem_create_data']["sem_sec_schreib"] == 0 ? "checked" : ""?>> <?=_("freier Zugriff"); ?> &nbsp;
                                    <?
                                    }
                                else {
                                    ?>
                                <font color=#BBBBBb>&nbsp; &nbsp; &nbsp;  <?=_("freier Zugriff")?> &nbsp;</font>
                                    <?
                                    }
                            ?>
                                <input type="radio" name="sem_sec_schreib" value="1" <?php print $_SESSION['sem_create_data']["sem_sec_schreib"] == 1 ? "checked" : ""?>> <?=_("in Stud.IP angemeldet"); ?> &nbsp;
                                <input type="radio" name="sem_sec_schreib" value="2" <?php print $_SESSION['sem_create_data']["sem_sec_schreib"] == 2 ? "checked" : ""?>> <?=_("nur mit Passwort"); ?> &nbsp;
                                <?= tooltipIcon(_("Hier geben Sie an, ob der Schreibzugriff auf die Veranstaltung frei (jeder), normal beschr�nkt (nur registrierte Stud.IP-User) oder nur mit einem speziellen Passwort m�glich ist.")) ?>
                            <?
                            } else
                                print "&nbsp; <font size=-1>"._("Schreibberechtigung nach erfolgreichem Anmeldeprozess")."</font>"
                            ?>
                        </td>
                    </tr>
                    <? if (count(($all_domains = UserDomain::getUserDomains()))) {?>
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="4%">
                            &nbsp;
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="96%" colspan=2>
                            <font size=-1><b><?=_("Zugelassenene Nutzerdom�nen:")?> </b></font><br>
                            <table border=0 cellpadding=2 cellspacing=0>
                                <tr>
                                    <td class="<? echo $cssSw->getClass() ?>" colspan=3 >
                                        <font size=-1><?=_("Bitte geben Sie hier ein, welche Nutzerdom�nen zugelassen sind.")."</font>"?>
                                    </td>
                                </tr>
                                    <?
                                    $sem_domain = Request::quoted('sem_domain');
                                    if (Request::submitted('add_domain') && Request::quoted('sem_domain') !== '' &&
                                        !in_array($sem_domain, $_SESSION['sem_create_data']["sem_domain"])) {
                                        $_SESSION['sem_create_data']["sem_domain"][]= $sem_domain;
                                    }

                                    foreach ($_SESSION['sem_create_data']["sem_domain"] as $domain_id) {
                                        $domain = new UserDomain($domain_id);
                                        ?>
                                            <tr>
                                                <td class="<? echo $cssSw->getClass() ?>" >
                                                <font size=-1>
                                                <?= htmlReady($domain->getName()) ?>
                                                </font>
                                                </td>
                                                <td class="<?= $cssSw->getClass() ?>" nowrap colspan=2 >
                                                <a href="<?= URLHelper::getLink('?delete_domain='.$domain_id) ?>">
                                                    <?= Assets::img('icons/16/blue/trash.png', array('class' => 'text-top', 'title' =>_('Nutzerdom�ne aus der Liste l�schen'))) ?>
                                                </a>
                                                </td>
                                            </tr>
                                        <?
                                     }
                                    // get all user domains that can be added
                                    $domains = array_diff($all_domains, $_SESSION['sem_create_data']["sem_domain"]);
                                    if (count($domains)) {
                                        ?>
                                    <tr>
                                        <td class="<? echo $cssSw->getClass() ?>" >
                                        <font size=-1>
                                        <select name="sem_domain">
                                        <option value="">-- <?=_("bitte ausw�hlen")?> --</option>
                                        <?

                                        foreach ($domains as $domain) {
                                            printf ("<option value=\"%s\">%s</option>", $domain->getID(), htmlReady(my_substr($domain->getName(), 0, 40)));
                                        }
                                        ?>
                                        </select>
                                        </font>
                                        </td>

                                        <td class="<? echo $cssSw->getClass() ?>">
                                            <?= Button::create(_('Hinzuf�gen'), 'add_domain', array('title' => _("Ausgew�hlte Nutzerdom�ne hinzuf�gen"))) ?>
                                            <? // TODO: Find appropriate Infotext
                                                echo tooltipIcon(_("Bitte markieren Sie hier alle Nutzerdom�nen, f�r die die Veranstaltung angeboten wird."))
                                            ?>
                                        </td>

                                    </tr>
                                        <?
                                        }
                                    ?>
                            </table>
                        </td>
                    </tr>
                    <?} ?>
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%">
                            &nbsp;
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="90%" align="center" colspan=3>
                            &nbsp; <?= LinkButton::create('<< '._('Zur�ck'), URLHelper::getUrl('?jump_back=1&form=' . $level)) ?>&nbsp;<?= Button::create(_('Weiter').' >>', 'jump_next') ?>
                        </td>
                    </tr>
                </table>
            </form>
            </td>
        </tr>
    </table>
    <script>
        jQuery("input[name=sem_doz_label], input[name=sem_tut_label]").autocomplete({
            source: <?=
                json_encode(preg_split("/[\s,;]+/", studip_utf8encode(Config::get()->getValue("PROPOSED_TEACHER_LABELS")), -1, PREG_SPLIT_NO_EMPTY));
            ?>
        });
    </script>
    <?
    }

//Level 3: Metadaten ueber Terminstruktur
if ($level == 3) {
    $semester = new SemesterData;
    $all_semester = $semester->getAllSemesterData();
    ?>
    <table width="100%" border=0 cellpadding=0 cellspacing=0>
        <tr>
            <td class="blank" colspan=2>&nbsp;
                <?
                if ($errormsg) parse_msg($errormsg);
                ?>
            </td>
        </tr>
        <tr>
            <td class="blank" valign="top">
                <div class="info">
                <b><?=_("Schritt 3: Termindaten"); ?></b><br><br>
                <? if ($_SESSION['sem_create_data']["term_art"] ==0)
                    print _("Bitte geben Sie hier ein, an welchen Tagen die Veranstaltung stattfindet. Wenn Sie nur einen Wochentag wissen, brauchen Sie nur diesen angeben.<br>Sie haben sp&auml;ter noch die M&ouml;glichkeit, weitere Einzelheiten zu diesen Terminen anzugeben.")."<br><br>";
                else
                    print _("Bitte geben Sie hier die einzelnen Termine an, an denen die Veranstaltung stattfindet.<br> Sie haben sp&auml;ter noch die M&ouml;glichkeit, weitere Einzelheiten zu diesen Terminen anzugeben.")."<br><br>";
                ?>
                <font size=-1><? printf (_("Alle mit einem Sternchen%smarkierten Felder <b>m&uuml;ssen</b> ausgef&uuml;llt werden, um eine Veranstaltung anlegen zu k&ouml;nnen.")."</font><br><br>", "&nbsp;</font><font color=\"red\" size=+1><b>*</b></font><font size=-1>&nbsp;");?>
                </div>
            </td>
            <td class="blank" align="right" valign="top">
                <img src="<?= localePictureUrl('hands03.jpg') ?>" border="0">
            </td>
        </tr>
        <tr>
            <td class="blank" colspan=2>
            <form method="POST" name="Formular" action="<? echo URLHelper::getLink() ?>">
            <?= CSRFProtection::tokenTag() ?>
            <input type="hidden" name="form" value=3>
                <table width ="99%" cellspacing=0 cellpadding=2 border=0 align="center">
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%">
                            &nbsp;
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="90%" align="center" colspan=3>
                            &nbsp; <?= LinkButton::create('<< '._('Zur�ck'), URLHelper::getUrl('?jump_back=1&form=' . $level)) ?>&nbsp;<?= Button::create(_('Weiter').' >>', 'jump_next') ?>
                        </td>
                    </tr>
                    <?
                        if ($_SESSION['sem_create_data']["term_art"] == 0)
                            {
                            ?>
                            <tr <? $cssSw->switchClass() ?>>
                                <td class="<? echo $cssSw->getClass() ?>" width="10%" align="right">
                                    &nbsp; <?=_("Daten &uuml;ber die Termine:"); ?>
                                </td>
                                <td class="<? echo $cssSw->getClass() ?>" width="90%" colspan=3>
                                    &nbsp; <b><font size=-1><?=_("Regelm&auml;&szlig;ige Veranstaltung"); ?></font></b><br><br>
                                    &nbsp;  <font size=-1><?=_("Wenn Sie den Typ der Veranstaltung &auml;ndern m&ouml;chten, gehen Sie bitte auf die erste Seite zur&uuml;ck."); ?></font><br><br>

                                    <br><br>&nbsp; <font size=-1><?=_("Die Veranstaltung findet immer zu diesen Zeiten statt:"); ?></font><br><br>
                                    <?
                                    if (empty($_SESSION['sem_create_data']["turnus_count"]))
                                        $_SESSION['sem_create_data']["turnus_count"]=1;
                                    for ($i=0; $i<$_SESSION['sem_create_data']["turnus_count"]; $i++) {
                                        if ($i>0) echo "<hr>\n";
                                        echo '&nbsp; <font size=-1><select name="term_turnus_date[', $i, ']">';
                                        $ttd = (empty($_SESSION['sem_create_data']["term_turnus_date"][$i]))? 1 : $_SESSION['sem_create_data']["term_turnus_date"][$i];
                                        for($kk = 1; $kk <= 7; $kk++ ){
                                            echo '<option ', (($kk == $ttd)? 'selected ':'');
                                            echo 'value="',$kk,'">';
                                            switch ($kk){
                                                case 2: echo _("Dienstag"); break;
                                                case 3: echo _("Mittwoch"); break;
                                                case 4: echo _("Donnerstag"); break;
                                                case 5: echo _("Freitag"); break;
                                                case 6: echo _("Samstag"); break;
                                                case 7: echo _("Sonntag"); break;
                                                case 1:
                                                default: echo _("Montag");
                                            }
                                            echo '</option>';
                                        }
                                        echo "</select>\n";
                                        $ss = (strlen($_SESSION['sem_create_data']["term_turnus_start_stunde"][$i]))? sprintf('%02d', $_SESSION['sem_create_data']["term_turnus_start_stunde"][$i]) : '';
                                        if (strlen($_SESSION['sem_create_data']["term_turnus_start_minute"][$i])) {
                                            $sm = sprintf('%02d', $_SESSION['sem_create_data']["term_turnus_start_minute"][$i]);
                                        } elseif (strlen($_SESSION['sem_create_data']["term_turnus_start_stunde"][$i])) {
                                            $sm = '00';
                                        } else {
                                            $sm ='';
                                        }
                                        $es = (strlen($_SESSION['sem_create_data']["term_turnus_end_stunde"][$i]))? sprintf('%02d', $_SESSION['sem_create_data']["term_turnus_end_stunde"][$i]) : '';
                                        if (strlen($_SESSION['sem_create_data']["term_turnus_end_minute"][$i])) {
                                            $em = sprintf('%02d', $_SESSION['sem_create_data']["term_turnus_end_minute"][$i]);
                                        } elseif (strlen($_SESSION['sem_create_data']["term_turnus_end_stunde"][$i])) {
                                            $em = '00';
                                        } else {
                                            $em = '';
                                        }
                                        echo '&nbsp; <input type="text" name="term_turnus_start_stunde['. $i. ']" size=2 maxlength=2 value="'. $ss. '"> : ';
                                        echo '<input type="text" name="term_turnus_start_minute['. $i. ']" size=2 maxlength=2 value="'. $sm. '">&nbsp;', _("Uhr bis");
                                        echo '&nbsp; <input type="text" name="term_turnus_end_stunde['. $i.']" size=2 maxlength=2 value="'. $es. '"> : ';
                                        echo '<input type="text" name="term_turnus_end_minute['. $i. ']" size=2 maxlength=2 value="'. $em. '">&nbsp;', _("Uhr"), "\n";

                                        if ($_SESSION['sem_create_data']["turnus_count"]>1) {
                                            ?>
                                            &nbsp; <a href="<? echo URLHelper::getLink("?delete_turnus_field=".($i+1)) ?>">
                                                <?= Assets::img('icons/16/blue/trash.png', array('class' => 'text-top', 'title' =>_('Dieses Feld aus der Auswahl l�schen')));
                                        }
                                        echo  Termin_Eingabe_javascript(4, $i, 0, $ss,$sm,$es,$em);

                                        //Beschreibung
                                        echo "\n<br>&nbsp; " . _("Beschreibung:") . "&nbsp;";
                                        echo "\n<select name=\"term_turnus_desc_chooser[$i]\" ";
                                        echo "onChange=\"document.Formular.elements['term_turnus_desc[$i]'].value=document.Formular.elements['term_turnus_desc_chooser[$i]'].options[Formular.elements['term_turnus_desc_chooser[$i]'].selectedIndex].value;\" ";
                                        echo ">";
                                        echo "\n<option value=\"\">" . _("ausw&auml;hlen oder wie Eingabe") . " --></option>";
                                        foreach($TERMIN_TYP as $ttyp){
                                            if ($ttyp['sitzung']) {
                                                echo "\n<option ";
                                                if ($_SESSION['sem_create_data']['term_turnus_desc'][$i] == $ttyp['name']) echo "selected";
                                                echo " value=\"" . htmlReady($ttyp['name']) . "\">" . htmlReady($ttyp['name']) . "</option>";
                                            }
                                        }
                                        echo "\n</select>";
                                        echo "&nbsp;";
                                        echo "\n<input type=\"text\" name=\"term_turnus_desc[$i]\" size=\"30\" value=\"".stripslashes(htmlReady($_SESSION['sem_create_data']['term_turnus_desc'][$i]))."\">";
                                        echo "\n<br>&nbsp;  " . _("Turnus:") . '&nbsp;';
                                        echo '<select name="term_turnus_cycle['.$i.']">';
                                        foreach(array(_("w�chentlich"), _("zweiw�chentlich"), _("dreiw�chentlich")) as $v => $c){
                                            echo '<option value="'.$v.'" '.($_SESSION['sem_create_data']["term_turnus_cycle"][$i]==$v ? 'selected' : '').'>'.$c."</option>";
                                        }
                                        echo '</select>&nbsp;'._("erster Termin in der");
                                        echo '<select name="term_turnus_week_offset['.$i.']">';
                                        $semester_index = get_sem_num($_SESSION['sem_create_data']["sem_start_time"]);
                                        $tmp_first_date = getCorrectedSemesterVorlesBegin($semester_index);
                                        $end_date = $all_semester[$semester_index]['vorles_ende'];
                                        $sem_week = 0;
                                        while ($tmp_first_date < $end_date) {
                                            echo '<option';
                                            if ($_SESSION['sem_create_data']["term_turnus_week_offset"][$i] == $sem_week) {
                                                echo ' selected="selected"';
                                            }
                                            echo ' value="'.$sem_week.'">';
                                            echo ($sem_week+1).'. '._("Semesterwoche")." ("._("ab")." ".date("d.m.Y",$tmp_first_date).")</option>";
                                            $sem_week++;
                                            $tmp_first_date = $tmp_first_date + (7 * 24 * 60 * 60);
                                        }
                                        echo '</select>';
                                        echo "&nbsp;" ._("SWS Dozent:");
                                        echo "\n&nbsp;<input type=\"text\" name=\"term_turnus_sws[$i]\" size=\"1\" maxlength=\"3\" value=\"".stripslashes(htmlReady($_SESSION['sem_create_data']['term_turnus_sws'][$i]))."\">";

                                    }
                                    ?>
                                    <br>&nbsp; <?= Button::create(_('Feld hinzuf�gen'), 'add_turnus_field') ?>
                                    <?= tooltipIcon(_("Wenn es sich um eine regelm��ige Veranstaltung handelt, so k�nnen Sie hier genau angeben, an welchen Tagen, zu welchen Zeiten und in welchem Raum die Veranstaltung stattfindet. Wenn Sie noch keine Zeiten wissen, dann klicken Sie auf �keine Zeiten speichern�.")) ?>
                                    <br>
                                </td>
                            </tr>
                        <?
                        }
                    else
                        {
                        ?>
                            <tr <? $cssSw->switchClass() ?>>
                                <td class="<? echo $cssSw->getClass() ?>" width="10%" align="right">
                                    &nbsp; <?=_("Daten &uuml;ber die Termine:"); ?>
                                </td>
                                <td class="<? echo $cssSw->getClass() ?>" width="90%" colspan=3>
                                    &nbsp; <b><font size=-1><?=_("Veranstaltung an unregelm&auml;&szlig;igen Terminen"); ?></font></b><br><br>
                                    &nbsp;  <font size=-1><?=_("Wenn Sie den Typ der Veranstaltung &auml;ndern m&ouml;chten, gehen Sie bitte auf die erste Seite zur&uuml;ck."); ?></font><br><br>
                                    &nbsp; <font size=-1><?=_("Die Veranstaltung findet an diesen Terminen statt:"); ?></font><br><br>
                                    <?
                                    if (empty($_SESSION['sem_create_data']["term_count"]))
                                        $_SESSION['sem_create_data']["term_count"]=1;
                                    for ($i=0; $i<$_SESSION['sem_create_data']["term_count"]; $i++)
                                        {
                                        if ($i>0) echo "<br>";
                                        $ss = (strlen($_SESSION['sem_create_data']["term_start_stunde"][$i]))? sprintf('%02d',$_SESSION['sem_create_data']["term_start_stunde"][$i]) : '';
                                        if (strlen($_SESSION['sem_create_data']["term_start_minute"][$i])) {
                                            $sm = sprintf('%02d', $_SESSION['sem_create_data']["term_start_minute"][$i]);
                                        } elseif (strlen($_SESSION['sem_create_data']["term_start_stunde"][$i])) {
                                            $sm = '00';
                                        } else {
                                            $sm = '';
                                        }
                                        $es = (strlen($_SESSION['sem_create_data']["term_end_stunde"][$i]))? sprintf('%02d',$_SESSION['sem_create_data']["term_end_stunde"][$i]):'';
                                        if (strlen($_SESSION['sem_create_data']["term_end_minute"][$i])) {
                                            $em = sprintf('%02d', $_SESSION['sem_create_data']["term_end_minute"][$i]);
                                        } elseif (strlen($_SESSION['sem_create_data']["term_end_stunde"][$i])) {
                                            $em = '00';
                                        } else {
                                            $em = '';
                                        }
                                        echo '<font size=-1>&nbsp; ', _("Datum:"), ' <input type="text" name="term_tag[',$i,']" size=2 maxlength=2 value="';
                                        if ($_SESSION['sem_create_data']["term_tag"][$i]) echo sprintf('%02d',$_SESSION['sem_create_data']["term_tag"][$i]);
                                        echo '">.',"\n", '<input type="text" name="term_monat[',$i,']" size=2 maxlength=2 value="';
                                        if ($_SESSION['sem_create_data']["term_monat"][$i]) echo sprintf('%02d',$_SESSION['sem_create_data']["term_monat"][$i]);
                                        echo '">. <input type="text" name="term_jahr[',$i,']" size=4 maxlength=4 value="';
                                        if ($_SESSION['sem_create_data']["term_jahr"][$i]) echo $_SESSION['sem_create_data']["term_jahr"][$i];
                                        echo '"> &nbsp;'. _("um"). '<input type="text" name="term_start_stunde['.$i.']" size=2 maxlength=2 value="'. $ss. '"> : ';
                                        echo '<input type="text" name="term_start_minute['.$i.']" size=2 maxlength=2 value="'. $sm. '">&nbsp;'. _("Uhr bis");
                                        echo '<input type="text" name="term_end_stunde['.$i.']" size=2 maxlength=2 value="'. $es. '"> : ';
                                        echo '<input type="text" name="term_end_minute['.$i.']" size=2 maxlength=2 value="'. $em. '">&nbsp;'. _("Uhr"). '</font>'. "\n";

                                        if ($_SESSION['sem_create_data']["term_count"]>1)
                                            {
                                            ?>
                                            &nbsp; <a href="<? echo URLHelper::getLink("?delete_term_field=".($i+1)) ?>">
                                            <?= Assets::img('icons/16/blue/trash.png', array('class' => 'text-top', 'title' =>_('Dieses Feld aus der Auswahl l�schen')));
                                            }
                                        echo  Termin_Eingabe_javascript (5, $i, 0, $ss, $sm, $es, $em);
                                        }
                                        ?>
                                        <br>&nbsp; <?= Button::create(_('Feld hinzuf�gen'), 'add_term_field') ?>
                                        <?= tooltipIcon(_("In diesem Feldern k�nnen Sie alle Veranstaltungstermine eingeben. Wenn die Termine noch nicht feststehen, lassen Sie die Felder einfach frei.")) ?>
                                        <br>
                                </td>
                            </tr>
                        <?
                        }
                    if ($_SESSION['sem_create_data']["modules_list"]["schedule"]) {
                    ?>
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%" align="right">
                            <?=_("Vorbesprechung:"); ?>
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="90%" colspan=3>
                        <?
                            $ss = (($_SESSION['sem_create_data']["sem_vor_termin"] <> -1)? date("H",$_SESSION['sem_create_data']["sem_vor_termin"]):'');
                            $sm = (($_SESSION['sem_create_data']["sem_vor_termin"] <> -1)? date("i",$_SESSION['sem_create_data']["sem_vor_termin"]):'');
                            $es = (($_SESSION['sem_create_data']["sem_vor_end_termin"] <> -1)? date("H",$_SESSION['sem_create_data']["sem_vor_end_termin"]):'');
                            $em = (($_SESSION['sem_create_data']["sem_vor_end_termin"] <> -1)? date("i",$_SESSION['sem_create_data']["sem_vor_end_termin"]):'');
                            echo '<font size=-1>&nbsp; <font size=-1>', _("Wenn es eine Vorbesprechung gibt, tragen Sie diese bitte hier ein:"), '</font><br><br>&nbsp; ', _("Datum:"), '</font>', "\n";
                            echo '<font size=-1><input type="text" name="vor_tag" size=2 maxlength=2 value="', (($_SESSION['sem_create_data']["sem_vor_termin"] <> -1)? date("d",$_SESSION['sem_create_data']["sem_vor_termin"]):''), '">. ', "\n";
                            echo '<input type="text" name="vor_monat" size=2 maxlength=2 value="', (($_SESSION['sem_create_data']["sem_vor_termin"] <> -1)?  date("m",$_SESSION['sem_create_data']["sem_vor_termin"]):''), '">. ', "\n";
                            echo '<input type="text" name="vor_jahr" size=4 maxlength=4 value="', (($_SESSION['sem_create_data']["sem_vor_termin"] <> -1)? date("Y",$_SESSION['sem_create_data']["sem_vor_termin"]):''), '">&nbsp;', "\n";
                            echo _("um"), ' <input type="text" name="vor_stunde" size=2 maxlength=2 value="', $ss, '"> : ', "\n";
                            echo '<input type="text" name="vor_minute" size=2 maxlength=2 value="', $sm, '">&nbsp;', _("Uhr bis"), "\n";
                            echo '<input type="text" name="vor_end_stunde" size=2 maxlength=2 value="', $es, '"> : ', "\n";
                            echo '<input type="text" name="vor_end_minute" size=2 maxlength=2 value="', $em, '">&nbsp;', _("Uhr"), "\n";
                            echo tooltipIcon(_("Dieses Feld m�ssen Sie nur ausf�llen, wenn es eine verbindliche Vorbesprechung zu der Veranstaltung gibt."));
                            echo  Termin_Eingabe_javascript (6, 0, 0, $ss, $sm, $es, $em);
                        ?>
                        </td>
                    </tr>
                    <?
                    }
                    ?>
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%">
                            &nbsp;
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="90%" align="center" colspan=3>
                            &nbsp; <?= LinkButton::create('<< '._('Zur�ck'), URLHelper::getUrl('?jump_back=1&form=' . $level)) ?>&nbsp;<?= Button::create(_('Weiter').' >>', 'jump_next') ?>
                        </td>
                    </tr>
                </table>
            </form>
            </td>
        </tr>
    </table>
    <?
    }

//Level 4: Raumdaten
if ($level == 4) {
    if ($GLOBALS['RESOURCES_ENABLE'])
        $resList = new ResourcesUserRoomsList($user_id->id, TRUE, FALSE, TRUE);
    ?>
    <table width="100%" border=0 cellpadding=0 cellspacing=0>
        <tr>
            <td class="blank" colspan=2>&nbsp;
                <?
                if ($errormsg) parse_msg($errormsg);
                ?>
            </td>
        </tr>
        <tr>
            <td class="blank" valign="top">
                <div class="info">
                <b><?=_("Schritt 4: Raumangaben"); ?></b><br><br>
                <?
                if ($GLOBALS['RESOURCES_ENABLE']) {
                    if ($GLOBALS['RESOURCES_ALLOW_ROOM_REQUESTS']) {
                        if ($resList->roomsExist())
                            print _("Bitte geben Sie hier ein, welche Angaben zu R&auml;umen gemacht werden, buchen Sie konkrete R&auml;ume oder stellen Sie Raumw&uuml;nsche an die zentrale Raumverwaltung.")."<br><br>";
                        else
                            print _("Bitte geben Sie hier ein, welche Angaben zu R&auml;umen gemacht werden oder stellen Sie Raumw&uuml;nsche an die zentrale Raumverwaltung.")."<br><br>";
                    } elseif ($resList->roomsExist())
                        print _("Bitte geben Sie hier ein, welche Angaben zu R&auml;umen gemacht werden oder buchen Sie konkrete R&auml;ume.")."<br><br>";
                } else
                    print _("Bitte geben Sie hier ein, welche Angaben zu R&auml;umen gemacht werden.")."<br><br>";
                ?>
            </td>
            <td class="blank" align="right" valign="top">
                <img src="<?= localePictureUrl('hands04.jpg') ?>" border="0">
            </td>
        </tr>
        <tr>
            <td class="blank" colspan=2>
            <form method="POST" name="form_4" action="<? echo URLHelper::getLink() ?>#anker">
            <?= CSRFProtection::tokenTag() ?>
            <input type="hidden" name="form" value=4>
                <table width ="99%" cellspacing=0 cellpadding=2 border=0 align="center">
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="4%">
                            &nbsp;
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="96%" align="center" colspan=3>
                            &nbsp; <?= LinkButton::create('<< '._('Zur�ck'), URLHelper::getUrl('?jump_back=1&form=' . $level)) ?>&nbsp;<?= Button::create(_('Weiter').' >>', 'jump_next') ?>
                        </td>
                    </tr>
                    <?
                    if (($GLOBALS['RESOURCES_ALLOW_ROOM_REQUESTS']) && ($GLOBALS['RESOURCES_ENABLE']) && $_SESSION['sem_create_data']['dates_are_valid']) {
                    ?>

                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="4%" align="right">
                            &nbsp;
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="96%">
                            <font size="-1"><b><?=_("Raumw&uuml;nsche"); ?></b><br><br>
                            <?
                            if (get_config('RESOURCES_ALLOW_SEMASSI_SKIP_REQUEST')){
                                echo _("<u>Keine</u> Raumanfrage erstellen") . "&nbsp;&nbsp;";
                                echo "<input type=\"checkbox\" name=\"skip_room_request\" style=\"vertical-align:middle\" value=\"1\" ";
                                if ($_SESSION['sem_create_data']['skip_room_request']) echo " checked ";
                                echo "><br><br>";
                            }
                            ?>
                            <noscript>
                            <label for="new_room_request_type"><?= _("Art der Raumanfrage:")?></label>
                            <select onChange="jQuery('input[name=room_request_choose]')[0].click();" id="new_room_request_type" name="new_room_request_type">
                            <? foreach ($_SESSION['sem_create_data']['room_requests_options'] as $one) :?>
                            <option value="<?= $one['value']?>" <?= (Request::option('new_room_request_type') == $one['value'] ? 'selected' : '')?>>
                                <?= htmlReady($one['name'])?>
                            </option>
                            <? endforeach ?>
                            </select>
                            <?
                            echo Button::create(_('Ausw�hlen'), 'room_request_choose', array('title' => _("einen anderen Anfragetyp bearbeiten")));

                            $current_request_type = Request::option('new_room_request_type', 'course');
                            $form_attributes = array('admission_turnout' => $_SESSION['sem_create_data']['sem_turnout'],
                                                     'request' => $_SESSION['sem_create_data']['room_requests'][$current_request_type],
                                                     'room_categories' => array_filter(getResourcesCategories(), create_function('$a', 'return $a["is_room"] == 1;'))
                                                    );
                            if (Request::option('new_room_request_type')) {
                                $form_attributes = array_merge($form_attributes, $room_request_form_attributes);
                            }
                            if ($form_attributes['request'] instanceof RoomRequest) {
                                $trails_views = $GLOBALS['STUDIP_BASE_PATH'] . '/app/views';
                                $factory = new Flexi_TemplateFactory($trails_views);
                                echo $factory->render('course/room_requests/_form.php', $form_attributes);
                                echo '<div style="text-align:center">' . Button::create(_('�bernehmen'), 'room_request_save', array('title' => _("Eingaben zur Raumanfrage speichern"))) .  '</div>';
                            }
                            printf('<input type="hidden" name="current_room_request_type" value="%s">', $current_request_type);
                            ?>
                            </noscript>
                            <div id="assi_room_request_with_js" style="margin-bottom:10px;"></div>
                            <script>
                                jQuery('#assi_room_request_with_js').load('<?=URLHelper::getUrl('dispatch.php/course/room_requests/index_assi/-')?>');
                                jQuery('#RoomRequestDialogbox').live('dialogclose', function () {
                                                                        jQuery('#assi_room_request_with_js').load('<?=URLHelper::getUrl('dispatch.php/course/room_requests/index_assi/-')?>');
                                                                    }
                                                                    );
                            </script>
                            <?
                    } else {
                        echo "<input type=\"hidden\" name=\"skip_room_request\" value=\"1\">";
                    }
                    if ($GLOBALS['RESOURCES_ENABLE'] && $resList->roomsExist() &&
                        (((is_array($_SESSION['sem_create_data']["metadata_termin"]["turnus_data"])) && ($_SESSION['sem_create_data']["term_art"] == 0))
                        || (($_SESSION['sem_create_data']["term_first_date"])) && ($_SESSION['sem_create_data']["term_art"] == 1)
                        || ($_SESSION['sem_create_data']["sem_vor_termin"] > -1))) {
                    ?>
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="4%" align="right">
                            &nbsp;
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="96%" colspan=3>
                            <font size="-1"><b><?=_("Raumbuchungen"); ?></b></font><br><br>
                            <table border="0" width="100%" cellspaceing="2" cellpadding="0">
                            <?
                                print "<font size=\"-1\">"._("Sie k&ouml;nnen zu jedem Termin einen Raum eintragen. Diese Eintragung wird beim Speichern der Veranstaltung in der Raumverwaltung gebucht.")."</font><br>";
                                if ($_SESSION['sem_create_data']["term_art"] == 0) {
                                    if (is_array($_SESSION['sem_create_data']["metadata_termin"]["turnus_data"])) {
                                        foreach ($_SESSION['sem_create_data']["metadata_termin"]["turnus_data"] as $val) {
                                            print "<tr><td width=\"50%\"><font size=\"-1\">";
                                            switch ($val["day"]) {
                                                case 1: print _("Montag"); break;
                                                case 2: print _("Dienstag"); break;
                                                case 3: print _("Mittwoch"); break;
                                                case 4: print _("Donnerstag"); break;
                                                case 5: print _("Freitag"); break;
                                                case 6: print _("Samstag"); break;
                                                case 7: print _("Sonntag"); break;
                                            }
                                            printf (" "._("von %02d:%02d Uhr bis %02d:%02d Uhr"), $val["start_stunde"], $val["start_minute"],  $val["end_stunde"], $val["end_minute"]);
                                            print "</font></td><td width=\"50%\"><font size=\"-1\">";
                                            $resList->reset();
                                            if ($resList->numberOfRooms()) {
                                                print " &nbsp;<select name=\"term_turnus_resource_id[]\">";
                                                printf ("<option %s value=\"FALSE\">["._("bitte ausw&auml;hlen")."]</option>", (!$val["resource_id"]) ? "selected" : "");
                                                while ($res = $resList->next()) {
                                                    printf ("<option %s value=\"%s\">%s</option>", ($val["resource_id"] == $res["resource_id"]) ? "selected" :"", $res["resource_id"], htmlReady($res["name"]));                                                }
                                                print "</select><br>";
                                            }
                                            print "</font></td></tr>\n";
                                        }
                                    }
                                } elseif ($_SESSION['sem_create_data']["term_art"] == 1) {
                                    for ($i=0; $i<$_SESSION['sem_create_data']["term_count"]; $i++) {
                                        if (($_SESSION['sem_create_data']["term_tag"][$i]) && ($_SESSION['sem_create_data']["term_monat"][$i]) && ($_SESSION['sem_create_data']["term_jahr"][$i]) && ($_SESSION['sem_create_data']["term_start_stunde"][$i] !== '') && ($_SESSION['sem_create_data']["term_end_stunde"][$i] !== '')) {
                                            print "<tr><td width=\"50%\"><font size=\"-1\">";
                                            printf (_("am %02d.%02d.%s von %02d:%02d Uhr bis %02d:%02d Uhr"), $_SESSION['sem_create_data']["term_tag"][$i], $_SESSION['sem_create_data']["term_monat"][$i], $_SESSION['sem_create_data']["term_jahr"][$i], $_SESSION['sem_create_data']["term_start_stunde"][$i], $_SESSION['sem_create_data']["term_start_minute"][$i], $_SESSION['sem_create_data']["term_end_stunde"][$i], $_SESSION['sem_create_data']["term_end_minute"][$i]);
                                            print "</font></td><td width=\"50%\"><font size=\"-1\">";
                                            $resList->reset();
                                            if ($resList->numberOfRooms()) {
                                                printf (" &nbsp;<select name=\"term_resource_id[%s]\">", $i);
                                                printf ("<option %s value=\"FALSE\">["._("bitte ausw&auml;hlen")."]</option>", (!$_SESSION['sem_create_data']["term_resource_id"][$i]) ? "selected" : "");
                                                while ($res = $resList->next()) {
                                                    printf ("<option %s value=\"%s\">%s</option>", ($_SESSION['sem_create_data']["term_resource_id"][$i] == $res["resource_id"]) ? "selected" :"", $res["resource_id"], htmlReady($res["name"]));
                                                }
                                                print "</select><br>";
                                            }
                                            print "</font></td></tr>\n";
                                        }
                                    }
                                }
                                if ($_SESSION['sem_create_data']["sem_vor_termin"] > -1) {
                                    print "<tr><td width=\"50%\"><font size=\"-1\">";
                                    printf (" "._("Vorbesprechung am %s von %s Uhr bis %s Uhr"), date("d.m.Y", $_SESSION['sem_create_data']["sem_vor_termin"]), date("H:i", $_SESSION['sem_create_data']["sem_vor_termin"]), date("H:i", $_SESSION['sem_create_data']["sem_vor_end_termin"]));
                                    print "</font></td><td width=\"50%\"><font size=\"-1\">";
                                    $resList->reset();
                                    if ($resList->numberOfRooms()) {
                                        print " &nbsp;<select name=\"vor_resource_id\">";
                                        printf ("<option %s value=\"FALSE\">["._("bitte ausw&auml;hlen")."]</option>", (!$_SESSION['sem_create_data']["sem_vor_resource_id"]) ? "selected" : "");
                                        while ($res = $resList->next()) {
                                            printf ("<option %s value=\"%s\">%s</option>", ($_SESSION['sem_create_data']["sem_vor_resource_id"] == $res["resource_id"]) ? "selected" :"", $res["resource_id"], htmlReady($res["name"]));
                                        }
                                        print "</select><br>";
                                    }
                                    print "</font></td></tr>\n";
                                }
                                ?>
                            </table>
                        </td>
                    </tr>
                    <?
                    }
                    if (((is_array($_SESSION['sem_create_data']["metadata_termin"]["turnus_data"])) && ($_SESSION['sem_create_data']["term_art"] == 0))
                        || (($_SESSION['sem_create_data']["term_first_date"]) && ($_SESSION['sem_create_data']["term_art"] == 1))
                        || ($_SESSION['sem_create_data']["sem_vor_termin"] > -1)) {
                    ?>
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="4%" align="right">
                            &nbsp;
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="96%" colspan=3>
                            <font size="-1"><b><?=_("freie Angaben zu R&auml;umen"); ?></b></font><br><br>
                            <table border="0" width="100%" cellspaceing="2" cellpadding="0">
                                <?
                                printf ("<font size=\"-1\">"._("%sSie k&ouml;nnen zu jedem Termin freie Angaben zu Raum bzw. Ort machen:")."</font><br>", (($GLOBALS['RESOURCES_ENABLE'] && $resList->roomsExist()) ? "<i><u>"._("oder:")."</u></i>&nbsp;" : ""));
                                if ($_SESSION['sem_create_data']["term_art"] == 0) {
                                    if (is_array($_SESSION['sem_create_data']["metadata_termin"]["turnus_data"])) {
                                        foreach ($_SESSION['sem_create_data']["metadata_termin"]["turnus_data"] as $val) {
                                            print "<tr><td width=\"50%\"><font size=\"-1\">";
                                            switch ($val["day"]) {
                                                case 1: print _("Montag"); break;
                                                case 2: print _("Dienstag"); break;
                                                case 3: print _("Mittwoch"); break;
                                                case 4: print _("Donnerstag"); break;
                                                case 5: print _("Freitag"); break;
                                                case 6: print _("Samstag"); break;
                                                case 7: print _("Sonntag"); break;
                                            }
                                            printf (" "._("von %02d:%02d Uhr bis %02d:%02d Uhr"), $val["start_stunde"], $val["start_minute"],  $val["end_stunde"], $val["end_minute"]);
                                            print "</font></td><td width=\"50%\"><font size=\"-1\">";
                                            printf ("&nbsp;<input type=\"text\" name=\"term_turnus_room[]\" size=\"30\" maxlength=\"255\" value=\"%s\"><br>", htmlReady(stripslashes($val["room"])));
                                            print "</font></td></tr>\n";
                                        }
                                    }
                                } elseif ($_SESSION['sem_create_data']["term_art"] == 1) {
                                    for ($i=0; $i<$_SESSION['sem_create_data']["term_count"]; $i++) {
                                        if (($_SESSION['sem_create_data']["term_tag"][$i]) && ($_SESSION['sem_create_data']["term_monat"][$i]) && ($_SESSION['sem_create_data']["term_jahr"][$i]) && ($_SESSION['sem_create_data']["term_start_stunde"][$i] !== '') && ($_SESSION['sem_create_data']["term_end_stunde"][$i] !== '')) {
                                            print "<tr><td width=\"50%\"><font size=\"-1\">";
                                            printf (_("am %02d.%02d.%s von %02d:%02d Uhr bis %02d:%02d Uhr"), $_SESSION['sem_create_data']["term_tag"][$i], $_SESSION['sem_create_data']["term_monat"][$i], $_SESSION['sem_create_data']["term_jahr"][$i], $_SESSION['sem_create_data']["term_start_stunde"][$i], $_SESSION['sem_create_data']["term_start_minute"][$i], $_SESSION['sem_create_data']["term_end_stunde"][$i], $_SESSION['sem_create_data']["term_end_minute"][$i]);
                                            print "</font></td><td width=\"50%\"><font size=\"-1\">";
                                            printf ("&nbsp;<input type=\"text\" name=\"term_room[%s]\" size=\"30\" maxlength=\"255\" value=\"%s\">", $i, htmlReady(stripslashes($_SESSION['sem_create_data']["term_room"][$i])));
                                            print "</font></td></tr>\n";
                                        }
                                    }

                                }
                                if ($_SESSION['sem_create_data']["sem_vor_termin"] > -1) {
                                    print "<tr><td width=\"50%\"><font size=\"-1\">";
                                    printf (" "._("Vorbesprechung am %s von %s Uhr bis %s Uhr"), date("d.m.Y", $_SESSION['sem_create_data']["sem_vor_termin"]), date("H:i", $_SESSION['sem_create_data']["sem_vor_termin"]), date("H:i", $_SESSION['sem_create_data']["sem_vor_end_termin"]));
                                    print "</font></td><td width=\"50%\"><font size=\"-1\">";
                                    printf ("&nbsp;<input type=\"text\" name=\"vor_raum\" size=\"30\" maxlength=\"255\" value=\"%s\"><br>", htmlReady(stripslashes($_SESSION['sem_create_data']["sem_vor_raum"])));
                                    print "</font></td></tr>\n";
                                }
                                ?>
                            </table>
                        </td>
                    </tr>
                    <?
                    } else {
                    ?>
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="4%" align="right">

                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="96%"  colspan=3>
                            <font size="-1">
                            <?=_("Sie k&ouml;nnen hier eine unspezifische Ortsangabe machen:")?><br>
                            <textarea name="sem_room" cols=58 rows="4"><? echo  htmlReady(stripslashes($_SESSION['sem_create_data']["sem_room"])) ?></textarea>
                            <?= tooltipIcon(_("Sie k�nnen hier einen Ort eingeben, der nur angezeigt wird, wenn keine genaueren Angaben aus Zeiten oder Sitzungsterminen gemacht werden k�nnen oder Sitzungstermine bereits abgelaufen sind und aus diesem Grund nicht mehr angezeigt werden.")) ?>
                            <br><?=_("<b>Achtung:</b> Diese Ortsangabe wird nur angezeigt, wenn keine genaueren Angaben aus Zeiten oder Sitzungsterminen gemacht werden k&ouml;nnen.");?>
                        </td>
                    </tr>
                    <?
                    }
                    ?>
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="4%">
                            &nbsp;
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="96%" align="center" colspan=3>
                            &nbsp; <?= LinkButton::create('<< '._('Zur�ck'), URLHelper::getUrl('?jump_back=1&form=' . $level)) ?>&nbsp;<?= Button::create(_('Weiter').' >>', 'jump_next') ?>
                        </td>
                    </tr>
                </table>
            </form>
            </td>
        </tr>
    </table>
    <?
    }


//Level 5: Hier wird der Rest abgefragt
if ($level == 5)
    {


    ?>
    <table width="100%" border=0 cellpadding=0 cellspacing=0>
        <tr >
            <td class="blank" colspan=2 >&nbsp;
                <?
                if ($errormsg) parse_msg($errormsg);
                ?>
            </td>
        </tr>
        <tr>
            <td class="blank" valign="top">
                <div class="info">
                <b><?=_("Schritt 5: Sonstige Daten zu der Veranstaltung"); ?></b><br><br>
                <? printf (_("Alle mit einem Sternchen%smarkierten Felder <b>m&uuml;ssen</b> ausgef&uuml;llt werden, um eine Veranstaltung anlegen zu k&ouml;nnen.")."<br><br>", "&nbsp;<font color=\"red\" size=+1><b>*</b></font>&nbsp;");?>
                </div>
            </td>
            <td class="blank" align="right" valign="top">
                <img src="<?= localePictureUrl('hands05.jpg') ?>" border="0">
            </td>
        </tr>
        <tr>
            <td class="blank" colspan=2>
            <form method="POST" name="form_5" action="<? echo URLHelper::getLink() ?>">
            <?= CSRFProtection::tokenTag() ?>
            <input type="hidden" name="form" value=5>
                <table width ="99%" cellspacing=0 cellpadding=2 border=0 align="center">
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%">
                            &nbsp;
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="90%" align="center" colspan=3>
                            &nbsp; <?= LinkButton::create('<< '._('Zur�ck'), URLHelper::getUrl('?jump_back=1&form=' . $level)) ?>&nbsp;<?= Button::create(_('Weiter').' >>', 'jump_next') ?>
                        </td>
                    </tr>
                    <? if ($_SESSION['sem_create_data']["sem_admission"] != 3) { ?>
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%" align="right">
                            <?= _("Anmeldezeitraum:"); ?>
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="90%" colspan=3>
                            <font size=-1>&nbsp;
                                <? print _("Bitte geben Sie hier ein Datum an, ab wann und bis wann sich Teilnehmer f�r die Veranstaltung eintragen d&uuml;rfen."); ?>
                                <br>&nbsp;
                                <? print _("Wenn sich die Teilnehmer sofort nach erstellen dieser Veranstaltung eintragen d&uuml;rfen, lassen Sie das Datum einfach unver&auml;ndert."); ?>
                                <br>&nbsp;
                                <? print _("Wenn es kein Ende der Anmeldefrist geben soll, lassen Sie das Enddatum unver&auml;ndert."); ?>
                                <br><br>
                            </font>
                            <table align="right" width="98%" border="0" cellpadding="2" cellspacing="0">
                                <tr>
                                    <td class="<? echo $cssSw->getClass() ?>" valign="top" align="right" width="10%">
                                        <font size=-1><? echo _("Startdatum f&uuml;r Anmeldungen");?>:</font>
                                    </td>
                                    <td class="<? echo $cssSw->getClass() ?>" valign="top" width="40%">
                                        <font size=-1>&nbsp; <input type="text" name="adm_s_tag" size=2 maxlength=2 value="<? if ($_SESSION['sem_create_data']["sem_admission_start_date"]<>-1) echo date("d",$_SESSION['sem_create_data']["sem_admission_start_date"]); else echo _("tt") ?>">.
                                        <input type="text" name="adm_s_monat" size=2 maxlength=2 value="<? if ($_SESSION['sem_create_data']["sem_admission_start_date"]<>-1) echo date("m",$_SESSION['sem_create_data']["sem_admission_start_date"]); else echo _("mm") ?>">.
                                        <input type="text" name="adm_s_jahr" size=4 maxlength=4 value="<? if ($_SESSION['sem_create_data']["sem_admission_start_date"]<>-1) echo date("Y",$_SESSION['sem_create_data']["sem_admission_start_date"]); else echo _("jjjj") ?>"><?=_("um");?>&nbsp;</font><br>
                                        <font size=-1>&nbsp; <input type="text" name="adm_s_stunde" size=2 maxlength=2 value="<? if ($_SESSION['sem_create_data']["sem_admission_start_date"]<>-1) echo date("H",$_SESSION['sem_create_data']["sem_admission_start_date"]); else echo _("hh") ?>">:
                                        <input type="text" name="adm_s_minute" size=2 maxlength=2 value="<? if ($_SESSION['sem_create_data']["sem_admission_start_date"]<>-1) echo date("i",$_SESSION['sem_create_data']["sem_admission_start_date"]); else echo _("mm") ?>">&nbsp;<?=_("Uhr");?></font>&nbsp;
                                        <?=Termin_Eingabe_javascript(20,0,($_SESSION['sem_create_data']["sem_admission_start_date"] != -1 ? $_SESSION['sem_create_data']["sem_admission_start_date"] : 0),'','','','','&form_name=form_5');?>
                                        <?= tooltipIcon(_("Teilnehmer d�rfen sich erst ab diesem Datum in die Veranstaltung eintragen.")) ?>
                                    </td>
                                    <td class="<? echo $cssSw->getClass() ?>" valign="top" align="right" width="10%">
                                        <font size=-1><? echo _("Enddatum f&uuml;r Anmeldungen");?>:</font>
                                    </td>
                                    <td class="<? echo $cssSw->getClass() ?>" valign="top" width="40%">
                                        <font size=-1>&nbsp; <input type="text" name="adm_e_tag" size=2 maxlength=2 value="<? if ($_SESSION['sem_create_data']["sem_admission_end_date"]<>-1) echo date("d",$_SESSION['sem_create_data']["sem_admission_end_date"]); else echo _("tt") ?>">.
                                        <input type="text" name="adm_e_monat" size=2 maxlength=2 value="<? if ($_SESSION['sem_create_data']["sem_admission_end_date"]<>-1) echo date("m",$_SESSION['sem_create_data']["sem_admission_end_date"]); else echo _("mm") ?>">.
                                        <input type="text" name="adm_e_jahr" size=4 maxlength=4 value="<? if ($_SESSION['sem_create_data']["sem_admission_end_date"]<>-1) echo date("Y",$_SESSION['sem_create_data']["sem_admission_end_date"]); else echo _("jjjj") ?>"><?=_("um");?>&nbsp;</font><br>
                                        <font size=-1>&nbsp; <input type="text" name="adm_e_stunde" size=2 maxlength=2 value="<? if ($_SESSION['sem_create_data']["sem_admission_end_date"]<>-1) echo date("H",$_SESSION['sem_create_data']["sem_admission_end_date"]); else echo "23" ?>">:
                                        <input type="text" name="adm_e_minute" size=2 maxlength=2 value="<? if ($_SESSION['sem_create_data']["sem_admission_end_date"]<>-1) echo date("i",$_SESSION['sem_create_data']["sem_admission_end_date"]); else echo "59" ?>">&nbsp;<?=_("Uhr");?></font>&nbsp;
                                        <?=Termin_Eingabe_javascript(21,0,($_SESSION['sem_create_data']["sem_admission_end_date"] != -1 ? $_SESSION['sem_create_data']["sem_admission_end_date"] : 0),'','','','','&form_name=form_5');?>
                                        <?= tooltipIcon(_("Teilnehmer d�rfen sich nur bis zu diesem Datum in die Veranstaltung eintragen.")) ?>
                                    </td>
                                    <td class="<? echo $cssSw->getClass() ?>" >
                                    <?=  Button::create(_('L�schen'), 'reset_admission_time', array('title' => _("Start- und Enddatum zur�cksetzen")))?>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <?
                    if (($_SESSION['sem_create_data']["sem_sec_lese"] ==2) || ($_SESSION['sem_create_data']["sem_sec_schreib"] ==2)) {
                        ?>
                        <tr <? $cssSw->switchClass() ?>>
                            <td class="<? echo $cssSw->getClass() ?>" width="10%" align="right">
                                <?=_("Passwort f&uuml;r Freischaltung:"); ?>
                            </td>
                            <td class="<? echo $cssSw->getClass() ?>" width="90%" colspan=3>&nbsp;
                                <?
                                    if (($_SESSION['sem_create_data']["sem_pw"]=="") || ($_SESSION['sem_create_data']["sem_pw"] == md5("")))
                                        echo "<input type=\"password\" name=\"sem_passwd\" size=12 maxlength=31> <font size='+2' color='red'>*</font>&nbsp; "._("Passwort-Wiederholung:")."&nbsp; <input type=\"password\" name=\"sem_passwd2\" size=12 maxlength=31>";
                                    else
                                        echo "<input type=\"password\" name=\"sem_passwd\" size=12 maxlength=31 value=\"*******\"> <font size='+2' color='red'>*</font>&nbsp; "._("Passwort-Wiederholung:")."&nbsp; <input type=\"password\" name=\"sem_passwd2\" size=12 maxlength=31 value=\"*******\">";
                                ?>
                                <?= tooltipIcon(_("Bitte geben Sie hier ein Passwort f�r die Veranstaltung sowie dasselbe Passwort nochmal zur Best�tigung ein. Dieses wird sp�ter von den Teilnehmenden ben�tigt, um die Veranstaltung abonnieren zu k�nnen.")) ?>
                                <font size="+2" color="red">*</font>
                            </td>
                        </tr>
                        <?
                    }
                    if ($_SESSION['sem_create_data']["sem_admission"]) {
                        $num_all = $_SESSION['sem_create_data']["sem_turnout"];
                        if (is_array($_SESSION['sem_create_data']["sem_studg"]) && $_SESSION['sem_create_data']["sem_turnout"]){
                            foreach ($_SESSION['sem_create_data']["sem_studg"] as $key => $val){
                                if ($val["ratio"] && $key != 'all') {
                                    $num_stg[$key] = round($_SESSION['sem_create_data']["sem_turnout"] * $val["ratio"] / 100);
                                    $num_all -= $num_stg[$key];
                                }
                            }
                            if ($num_all < 0) $num_all = 0;
                            $num_stg['all'] = $num_all;
                        }
                    ?>
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%" align="right">
                            <?=_("Anmeldeverfahren:"); ?>
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="90%"  colspan=3>
                            <font size=-1>&nbsp;<?
                            if ($_SESSION['sem_create_data']["sem_admission"] == 1)
                                print _("Sie haben vorher das Stud.IP Anmeldeverfahren nach dem Losverfahren aktiviert.");
                            else
                                print _("Sie haben vorher das Stud.IP Anmeldeverfahren nach der Reihenfolge der Anmeldungen aktiviert.");
                            ?><br>
                            &nbsp;<?
                            if ($_SESSION['sem_create_data']["sem_admission"] == 1)
                                print _("Bitte geben Sie hier an, welche Studieng&auml;nge mit welchen Kontingenten zugelassen sind und wann das Losdatum ist:");
                            elseif (!$_SESSION['sem_create_data']["admission_enable_quota"])
                                print _("Bitte geben Sie hier an, welche Studieng&auml;nge zugelassen sind:");
                            else
                                print _("Bitte geben Sie hier an, welche Studieng&auml;nge mit welchen Kontingenten zugelassen sind und wann das Enddatum der Kontingentierung ist:");
                            ?><br><br>
                                <table border="0" cellpadding="2" cellspacing="0">
                                    <tr>
                                    <td class="<? echo $cssSw->getClass() ?>" colspan="4" >
                                    <input style="vertical-align:middle;" type="checkbox" name="admission_enable_quota" <?=($_SESSION['sem_create_data']["admission_enable_quota"] ? 'checked' : '')?> value="1">
                                    <font size=-1><?=_("Prozentuale Kontingentierung aktivieren.")."</font>"?>
                                    &nbsp;&nbsp;
                                    <?= Button::createAccept(_('OK!'), 'toggle_admission_quota', array('title' => _("Kontingentierung aktivieren/deaktivieren"))) ?>
                                    </td>
                                    </tr>
                                    <tr>
                                        <td class="<? echo $cssSw->getClass() ?>" valign="bottom" width="40%">
                                        <font size=-1>&nbsp;
                                        </font>
                                        </td>
                                        <td class="<? echo $cssSw->getClass() ?>" valign="bottom"  nowrap width="5%">
                                        &nbsp;
                                        </td>
                                        <td class="<? echo $cssSw->getClass() ?>" valign="top" align="right" width="25%">
                                            <font size=-1><? if ($_SESSION['sem_create_data']["sem_admission"] == 1) echo _("Losdatum").':'; elseif($_SESSION['sem_create_data']["admission_enable_quota"]) echo _("Enddatum der Kontingentierung").':';?>&nbsp;</font>
                                        </td>
                                        <td class="<? echo $cssSw->getClass() ?>" valign="top" width="45%">
                                        <?if($_SESSION['sem_create_data']["sem_admission"] == 1 || $_SESSION['sem_create_data']["admission_enable_quota"]){?>
                                            <font size=-1>&nbsp; <input type="text" name="adm_tag" size=2 maxlength=2 value="<? if ($_SESSION['sem_create_data']["sem_admission_date"]<>-1) echo date("d",$_SESSION['sem_create_data']["sem_admission_date"]); else echo _("tt") ?>">.
                                            <input type="text" name="adm_monat" size=2 maxlength=2 value="<? if ($_SESSION['sem_create_data']["sem_admission_date"]<>-1) echo date("m",$_SESSION['sem_create_data']["sem_admission_date"]); else echo _("mm") ?>">.
                                            <input type="text" name="adm_jahr" size=4 maxlength=4 value="<? if ($_SESSION['sem_create_data']["sem_admission_date"]<>-1) echo date("Y",$_SESSION['sem_create_data']["sem_admission_date"]); else echo _("jjjj") ?>"><?=_("um");?>&nbsp;</font><br>
                                            <font size=-1>&nbsp; <input type="text" name="adm_stunde" size=2 maxlength=2 value="<? if ($_SESSION['sem_create_data']["sem_admission_date"]<>-1) echo date("H",$_SESSION['sem_create_data']["sem_admission_date"]); else echo"23" ?>">:
                                            <input type="text" name="adm_minute" size=2 maxlength=2 value="<? if ($_SESSION['sem_create_data']["sem_admission_date"]<>-1) echo date("i",$_SESSION['sem_create_data']["sem_admission_date"]); else echo"59" ?>">&nbsp;<?=_("Uhr");?></font>&nbsp;
                                            <?=Termin_Eingabe_javascript(22,0,($_SESSION['sem_create_data']["sem_admission_date"] != -1 ? $_SESSION['sem_create_data']["sem_admission_date"] : 0),'','','','','&form_name=form_5');?>
                                            <?
                                            if ($_SESSION['sem_create_data']["sem_admission"] == 1) {
                                            ?>
                                            <?= tooltipIcon(_("Bitte geben Sie hier ein, wann die Anw�rter auf der Anmeldeliste in die Veranstaltung gelost werden. Freigebliebene Pl�tze werden nach diesem Termin per Warteliste an andere interessierte Personen vergeben.")) ?>
                                            <?
                                            } else {
                                            ?>
                                            <?= tooltipIcon(_("Bitte geben Sie hier ein, wann das Anmeldeverfahren die Kontingentierung aufheben soll. Ab diesem Zeitpunkt werden freie Pl�tze an interessierten Personen aus der Warteliste vergeben.")) ?>
                                            <?
                                            }
                                        } else echo '&nbsp;';
                                        ?>
                                        </td>
                                    </tr>
                                    <?
                                    if (count($_SESSION['sem_create_data']["sem_studg"])) {
                                        foreach ($_SESSION['sem_create_data']["sem_studg"] as $key=>$val) {
                                    ?>
                                    <tr>
                                        <td class="<? echo $cssSw->getClass() ?>" width="25%">
                                        <font size=-1>&nbsp;
                                        <?
                                        echo (htmlReady(my_substr($val["name"], 0, 40)));
                                        ?>
                                        </font>
                                        </td>
                                        <td class="<? echo $cssSw->getClass() ?>" nowrap width="5%">
                                        <input type="hidden" name="sem_studg_id[]" value="<? echo $key ?>">
                                        <input type="hidden" name="sem_studg_name[]" value="<? echo $val["name"] ?>">
                                        <?
                                        if($_SESSION['sem_create_data']["admission_enable_quota"]){
                                            printf ("<input type=\"HIDDEN\" name=\"sem_studg_ratio_old[]\" value=\"%s\">", $val["ratio"]);
                                            printf ("<input type=\"TEXT\" name=\"sem_studg_ratio[]\" size=5 maxlength=5 value=\"%s\"><font size=-1> %% (%s Teilnehmer)</font>", $val["ratio"], $num_stg[$key]);
                                            printf ("&nbsp; <a href=\"%s\"><img border=0 src=\"".Assets::image_path('icons/16/blue/trash.png')."\" ".tooltip(_("Den Studiengang aus der Liste l�schen")).">", URLHelper::getLink("?sem_delete_studg=".$key));
                                        } else {
                                            printf ("&nbsp; <a href=\"%s\"><img border=0 src=\"".Assets::image_path('icons/16/blue/trash.png')."\" ".tooltip(_("Den Studiengang aus der Liste l�schen")).">", URLHelper::getLink("?sem_delete_studg=".$key));
                                        }
                                        ?>
                                        </td>
                                        <td class="<? echo $cssSw->getClass() ?>" width="70%" colspan=2>&nbsp;
                                        </td>
                                    </tr>
                                    <?
                                        }
                                    }
                                    $stg = array();
                                    if(!isset($_SESSION['sem_create_data']["sem_studg"]['all'])){
                                        $stg[] = array('name' => _("Alle Studieng�nge"), 'studiengang_id' => 'all');
                                    }

                                    $query = "SELECT *
                                              FROM studiengaenge
                                              WHERE studiengang_id NOT IN (?)
                                              ORDER BY name";
                                    $statement = DBManager::get()->prepare($query);
                                    $statement->execute(array(
                                        array_keys($_SESSION['sem_create_data']['sem_studg']) ?: array('')
                                    ));
                                    $studiengaenge = $statement->fetchAll(PDO::FETCH_ASSOC);
                                    $stg = array_merge($stg, $studiengaenge);

                                    if (count($stg)) {
                                    ?>
                                    <tr>
                                        <td class="<? echo $cssSw->getClass() ?>" width="25%">
                                        <font size=-1>&nbsp;
                                        <select name="sem_add_studg">
                                        <option value="">-- <?=_("bitte ausw&auml;hlen"); ?> --</option>
                                    <?
                                    foreach($stg as $s) {
                                    printf ("<option value=%s>%s</option>", $s["studiengang_id"], htmlReady(my_substr($s["name"], 0, 40)));
                                    }
                                    ?>
                                        </select>
                                        </font>
                                        </td>
                                        <td class="<? echo $cssSw->getClass() ?>" nowrap width="5%">
                                        <?if($_SESSION['sem_create_data']["admission_enable_quota"]){?>
                                            <input type="text" name="sem_add_ratio" size=5 maxlength=5><font size=-1> %</font>
                                        <?} else echo '&nbsp;';?>
                                        </td>
                                        <td class="<? echo $cssSw->getClass() ?>" width="25%">
                                            <?= Button::create(_('Hinzuf�gen'), 'add_studg') ?>&nbsp;
                                            <?= tooltipIcon(_("Bitte geben Sie hier ein, f�r welche Studieng�nge die Veranstaltung mit welchen Kontingenten beschr�nkt sein soll und bis wann eine Anmeldung �ber das Stud.IP Anmeldeverfahren m�glich ist.")) ?>
                                        </td>
                                        <td class="<? echo $cssSw->getClass() ?>" width="40%">&nbsp;
                                        </td>
                                    </tr>
                                    <?
                                    }
                                    ?>
                                </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%" align="right">
                            &nbsp;
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="90%"  colspan=3>
                            <?
                            printf (_("%sAchtung: %sWenn Sie ein Anmeldeverfahren starten, so kann dieser Schritt sp&auml;ter nicht r&uuml;ckg&auml;ngig gemacht werden.")." <br>&nbsp; "._("Sie k&ouml;nnen jedoch die Anzahl der Teilnehmer jederzeit unter <i>Grunddaten</i> anpassen.")."</font> ", "<font size=-1 color=\"red\">&nbsp; ", "</font><font size=-1>");
                            ?>
                        </td>
                    </tr>
                    <?
                    }

                    if ($_SESSION['sem_create_data']["sem_payment"]=="1") { ?>
                    <tr<?$cssSw->switchClass()?>>
                        <td class ="<? echo $cssSw->getClass() ?>" width="10%" align="right">
                            <? echo _("Hinweistext bei vorl&auml;ufigen Eintr&auml;gen:"); ?>
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" colspan=3>
                            &nbsp;&nbsp;<textarea name="sem_paytxt" cols=58 rows=4><? echo htmlReady(stripslashes($_SESSION['sem_create_data']["sem_paytxt"])) ?></textarea>
                            <?= tooltipIcon(_("Dieser Hinweistext erl�utert Ihren TeilnehmerInnen was sie tun m�ssen, um endg�ltig f�r die Veranstaltung zugelassen zu werden. Beschreiben Sie genau, wie Beitr�ge zu entrichten sind, Leistungen nachgewiesen werden m�ssen, etc.")) ?>
                        </td>
                    </tr>
                    <?
                    }
                    }
                    if (!$SEM_CLASS[$_SESSION['sem_create_data']["sem_class"]]["compact_mode"]) {
                    ?>
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%" align="right">
                            <?=_("Teilnehmer- beschreibung:"); ?>
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="90%"  colspan=3>
                            &nbsp; <textarea name="sem_teiln" cols=58 rows=4><? echo  htmlReady(stripslashes($_SESSION['sem_create_data']["sem_teiln"])) ?></textarea>
                            <?= tooltipIcon(_("Bitte geben Sie hier ein, f�r welchen Teilnehmerkreis die Veranstaltung geeignet ist.")) ?>
                        </td>
                    </tr>
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%" align="right">
                            <?=_("Voraussetzungen:"); ?>
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="90%"  colspan=3>
                            &nbsp; <textarea name="sem_voraus" cols=58 rows=4><? echo  htmlReady(stripslashes($_SESSION['sem_create_data']["sem_voraus"])) ?></textarea>
                            <?= tooltipIcon(_("Bitte geben Sie hier ein, welche Voraussetzungen f�r die Veranstaltung n�tig sind.")) ?>
                        </td>
                    </tr>
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%" align="right">
                            <?=_("Lernorganisation:"); ?>
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="90%"  colspan=3>
                            &nbsp; <textarea name="sem_orga" cols=58 rows=4><? echo  htmlReady(stripslashes($_SESSION['sem_create_data']["sem_orga"])) ?></textarea>
                            <?= tooltipIcon(_("Bitte geben Sie hier ein, mit welcher Lernorganisation die Veranstaltung durchgef�hrt wird.")) ?>
                        </td>
                    </tr>
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%" align="right">
                            <?=_("Leistungsnachweis:"); ?>
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="90%" colspan=3>
                            &nbsp; <textarea name="sem_leistnw" cols=58 rows=4><? echo  htmlReady(stripslashes($_SESSION['sem_create_data']["sem_leistnw"])) ?></textarea>
                            <?= tooltipIcon(_("Bitte geben Sie hier ein, welche Leistungsnachweise erbracht werden m�ssen.")) ?>
                        </td>
                    </tr>
                    <?
                    }
                    //add the free adminstrable datafields
                    $dataFieldStructures = DataFieldStructure::getDataFieldStructures('sem', $_SESSION['sem_create_data']['sem_class'], true);
                    foreach ((array)$dataFieldStructures as $id=>$struct) {
                        if ($struct->accessAllowed($perm)) {
                            ?>
                            <tr <? $cssSw->switchClass() ?>>
                                <td class="<?= $cssSw->getClass() ?>" width="10%" align="right">
                                    <?=htmlReady($struct->getName()) ?>

                                    <?if($struct->getIsRequired() && $perm->have_perm($struct->getEditPerms())):?>
                                        <font color="red" size=+2>*</font>
                                    <?endif;?>
                                </td>
                                <td class="<?= $cssSw->getClass() ?>" width="90%" colspan=3>
                                    <div style="width:33.8em; float:left;">
                                        <?
                                        if ($perm->have_perm($struct->getEditPerms())) {
                                            $entry = DataFieldEntry::createDataFieldEntry($struct, '', stripslashes($_SESSION['sem_create_data']["sem_datafields"][$id]['value']));
                                            print "&nbsp;&nbsp;".$entry->getHTML("sem_datafields");

                                        } else {
                                        ?>
                                        &nbsp;<font size="-1"><?=_("Diese Daten werden von Ihrem zust&auml;ndigen Administrator erfasst.")?></font>
                                        <?= tooltipIcon(_("Diese Felder werden zentral durch die zust�ndigen Administratoren erfasst.")) ?>
                                        <?
                                        }
                                        ?>
                                    </div>
                                    <?if ($perm->have_perm($struct->getEditPerms()) && $struct->getDescription())
                                          echo tooltipIcon(_($struct->getDescription()));
                                    ?>
                                </td>
                            </tr>
                            <?
                        }
                    }
                    ?>
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%" align="right">
                            <?=_("Sonstiges:"); ?>
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="90%" colspan=3>
                            &nbsp; <textarea name="sem_sonst" cols=58 rows=<? if ($SEM_CLASS[$_SESSION['sem_create_data']["sem_class"]]["compact_mode"]) echo "10"; else echo "4" ?>><? echo  htmlReady(stripslashes($_SESSION['sem_create_data']["sem_sonst"])) ?></textarea>
                            <?= tooltipIcon(_("Hier ist Platz f�r alle sonstigen Informationen zur Veranstaltung.")) ?>
                        </td>
                    </tr>
                    <?
                    if (($_SESSION['sem_create_data']["term_start_woche"]==-1) && ($_SESSION['sem_create_data']["term_art"] == 0))
                        {
                        ?>
                        <tr <? $cssSw->switchClass() ?>>
                            <td class="<? echo $cssSw->getClass() ?>" width="10%" align="right">
                                <?=_("erster Termin:"); ?>
                            </td>
                            <td class="<? echo $cssSw->getClass() ?>" width="90%" colspan=3>
                                <font size=-1>&nbsp; <font size=-1><?=_("Sie haben angegeben, an einem anderen Zeitpunkt mit der Veranstaltung zu beginnen. Bitte geben Sie hier den ersten Termin ein."); ?></font><br><br>&nbsp; <?=_("Datum:"); ?> </font>
                                <font size=-1><input type="text" name="tag" size=2 maxlength=2 value="<? if ($_SESSION['sem_create_data']["sem_start_termin"]<>-1) echo date("d",$_SESSION['sem_create_data']["sem_start_termin"]); else echo _("tt") ?>">.
                                <input type="text" name="monat" size=2 maxlength=2 value="<? if ($_SESSION['sem_create_data']["sem_start_termin"]<>-1) echo date("m",$_SESSION['sem_create_data']["sem_start_termin"]); else echo _("mm") ?>">.
                                <input type="text" name="jahr" size=4 maxlength=4 value="<? if ($_SESSION['sem_create_data']["sem_start_termin"]<>-1) echo date("Y",$_SESSION['sem_create_data']["sem_start_termin"]); else echo _("jjjj") ?>">&nbsp; </font>
                                <?= tooltipIcon(_("Bitte geben Sie hier ein, wann der erste Termin der Veranstaltung stattfindet.")) ?>
                            </td>
                        </tr>
                        <?
                        }
                    ?>
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%">
                            &nbsp;
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="90%" align="center" colspan=3>
                            &nbsp; <?= LinkButton::create('<< '._('Zur�ck'), URLHelper::getUrl('?jump_back=1&form=' . $level)) ?>&nbsp;<?= Button::create(_('Weiter').' >>', 'jump_next') ?>
                        </td>
                    </tr>
                </table>
            </form>
            </td>
        </tr>
    </table>
    <?
    }

//Level 6: Seminar anlegen
if ($level == 6)
    {
    ?>
    <table width="100%" border=0 cellpadding=0 cellspacing=0>
        <tr>
            <td class="blank" colspan=2>&nbsp;
                <?
                if ($errormsg) parse_msg($errormsg);
                ?>
            </td>
        </tr>
        <tr>
            <td class="blank" valign="top">
                <div class="info">
                <b><?=_("Schritt 6: Bereit zum Anlegen der Veranstaltung"); ?></b><br><br>
                <?=_("Sie haben nun alle n&ouml;tigen Daten zum Anlegen der Veranstaltung eingegeben. Wenn Sie auf &raquo;anlegen&laquo; klicken, wird die Veranstaltung in Stud.IP &uuml;bernommen. Wenn Sie sich nicht sicher sind, ob alle Daten korrekt sind, &uuml;berpr&uuml;fen Sie noch einmal Ihre Eingaben auf den vorhergehenden Seiten."); ?><br><br>
                <form method="POST" action="<? echo URLHelper::getLink() ?>">
                    <?= CSRFProtection::tokenTag() ?>
                    <input type="hidden" name="form" value=6>
                    <?= LinkButton::create('<< '._('Zur�ck'), URLHelper::getUrl('?jump_back=1&form=' . $level)) ?>&nbsp;<?= Button::create(_('Anlegen'), 'jump_next') ?>
                </form>
                </div>
            </td>
            <td class="blank" align="right" valign="top">
                <img src="<?= localePictureUrl('hands06.jpg') ?>" border="0">
            </td>
        </tr>
    </table>
    <?
    }

//Level 6:Statusmeldungen nach dem Anlegen und weiter zum den Einzelheiten
if ($level == 7)
    {
    ?>
    <table width="100%" border=0 cellpadding=0 cellspacing=0>
        <tr>
            <td class="blank" colspan=2>&nbsp;
                <?
                if ($errormsg) parse_msg($errormsg);
                ?>
            </td>
        </tr>
        <?
        if (!$successful_entry)
            { ?>
            <tr>
                <td class="blank">
                    <div class="info">
                    <b><?=_("Die Veranstaltung konnte nicht angelegt werden."); ?></b><br><br>
                    <?=_("Bitte korrigieren Sie die Daten."); ?>
                    <form method="POST" action="<? echo URLHelper::getLink() ?>">
                        <?= CSRFProtection::tokenTag() ?>
                        <input type="hidden" name="form" value=7>
                        <?= LinkButton::create('<< '._('Zur�ck'), URLHelper::getUrl('?jump_back=1&form=' . $level)) ?>
                    </form>
                    </div>
                </td>
                <td class="blank" align="right">
                    <img src="<?= localePictureUrl('hands06.jpg') ?>" border="0">
                </td>
            </tr> <?
            }
        elseif ($successful_entry==2)
            { ?>
            <tr>
                <td class="blank" valign="top">
                    <div class="info">
                    <?
                    print _("Sie haben die Veranstaltung bereits angelegt.");
                    if (($_SESSION['sem_create_data']["modules_list"]["schedule"]) || ($_SESSION['sem_create_data']["modules_list"]["scm"])) {
                        if (($_SESSION['sem_create_data']["modules_list"]["schedule"]) && ($_SESSION['sem_create_data']["modules_list"]["scm"]))
                            print " "._("Sie k&ouml;nnen nun mit der Informationsseite und dem Termin-Assistenten fortfahren oder an diesem Punkt abbrechen.");
                        if (($_SESSION['sem_create_data']["modules_list"]["schedule"]) && (!$_SESSION['sem_create_data']["modules_list"]["scm"]))
                            print " "._("Sie k&ouml;nnen nun mit dem Termin-Assistenten fortfahren oder an diesem Punkt abbrechen.");
                        if ((!$_SESSION['sem_create_data']["modules_list"]["schedule"]) && ($_SESSION['sem_create_data']["modules_list"]["scm"]))
                            print " "._("Sie k&ouml;nnen nun mit der Informationsseite fortfahren oder an diesem Punkt abbrechen.");
                        print "<br><br><font size=-1>"._("Sie haben jederzeit die M&ouml;glichkeit, die bereits erfassten Daten zu &auml;ndern und diese Schritte sp&auml;ter nachzuholen.")."</font>";
                    }
                    ?>
                    <br><br>
                    <form method="POST" action="<? echo URLHelper::getLink() ?>">
                        <?= CSRFProtection::tokenTag() ?>
                        <input type="hidden" name="form" value=7>
                        <?
                        echo Button::createCancel(_('Abbrechen'), 'cancel');
                        if (($_SESSION['sem_create_data']["modules_list"]["schedule"]) || ($_SESSION['sem_create_data']["modules_list"]["scm"])) {
                            ?>
                            &nbsp;<?= Button::create(_('Weiter').' >>', 'jump_next') ?>
                            <?
                        }
                        ?>
                    </form>
                    </div>
                </td>
                <td class="blank" align="right">
                    <img src="<?= localePictureUrl('hands06.jpg') ?>" border="0">
                </td>
            </tr> <?
            }

        else
            { ?>
            <tr>
                <td class="blank" valign="top">
                    <div class="info">
                    <b><?=_("Die Daten der Veranstaltung wurden in das System &uuml;bernommen"); ?></b><br><br>
                    <?
                    print _("Die Veranstaltung ist jetzt eingerichtet.");
                    if (($_SESSION['sem_create_data']["modules_list"]["schedule"]) || ($_SESSION['sem_create_data']["modules_list"]["scm"])) {
                        print " "._("Wenn Sie nun auf &raquo;weiter >>&laquo; klicken, k&ouml;nnen Sie weitere -optionale- Daten f&uuml;r die Veranstaltung eintragen.");
                        if (($_SESSION['sem_create_data']["modules_list"]["schedule"]) && ($_SESSION['sem_create_data']["modules_list"]["scm"]))
                            print " "._("Sie haben die M&ouml;glichkeit, eine Informationsseite anzulegen und k�nnen den Terminen im Ablaufplan Themen zuordnen.");
                        if (($_SESSION['sem_create_data']["modules_list"]["schedule"]) && (!$_SESSION['sem_create_data']["modules_list"]["scm"]))
                            print " "._("Sie k�nnen den Terminen im Ablaufplan Themen zuordnen.");
                        if ((!$_SESSION['sem_create_data']["modules_list"]["schedule"]) && ($_SESSION['sem_create_data']["modules_list"]["scm"]))
                            print " "._("Sie haben die M&ouml;glichkeit,  eine Informationsseite anzulegen.");
                        print "<br><br><font size=-1>"._("Sie haben jederzeit die M&ouml;glichkeit, die bereits erfassten Daten zu &auml;ndern und die n&auml;chsten Schritte sp&auml;ter nachzuholen.")."</font>";
                    }
                    ?><br><br>
                    <?= _("Klicken Sie auf den Titel der Veranstaltung, um direkt zur neu angelegten Veranstaltung zu gelangen:") ?>
                    <a href="<?= URLHelper::getLink('seminar_main.php') ?>"><?= htmlReady(stripslashes($_SESSION['sem_create_data']["sem_name"])) ?></a>
                    <br><br>
                    <? if (isset($_SESSION['sem_create_data_backup']['timestamp'])) : ?>
                        <?= _("Sie k�nnen direkt eine Kopie der neu angelegten Veranstaltung anlegen:") ?>
                        <a href="<?= URLHelper::getLink('?start_from_backup=1') ?>"><?= _("Kopie anlegen") ?></a>
                        <br><br>
                    <? endif ?>
                    <form method="POST" action="<?= URLHelper::getLink() ?>">
                        <?= CSRFProtection::tokenTag() ?>
                        <input type="hidden" name="form" value=7>
                        <?
                        if (($_SESSION['sem_create_data']["modules_list"]["schedule"]) || ($_SESSION['sem_create_data']["modules_list"]["scm"])) {
                            ?>
                            <?= Button::create(_('Weiter').' >>', 'jump_next') ?>
                            <?
                        }
                        ?>
                    </form>
                    </div>
                </td>
                <td class="blank" align="right" valign="top">
                    <img src="<?= localePictureUrl('hands06.jpg') ?>" border="0">
                </td>
            </tr>
            <tr>
                <td class="blank" colspan=2>
                <br>
                <form method="POST" action="<? echo URLHelper::getLink() ?>">
                    <?= CSRFProtection::tokenTag() ?>
                    <table width ="60%" cellspacing=1 cellpadding=1>
                        <tr>
                            <td width="10%" class="blank">&nbsp; </td>
                            <td width="90%" class="rahmen_steel">
                            <?
                            printf ("<br><br><ul><li>"._("Veranstaltung <b>%s</b> erfolgreich angelegt.")."<br><br>", htmlReady(stripslashes($_SESSION['sem_create_data']["sem_name"])));
                            if ($count_bet_inst==1)
                                print "<li>"._("Veranstaltung f&uuml;r <b>1</b> beteiligte Einrichtung angelegt.")."<br><br>";
                            elseif ($count_bet_inst>1)
                                printf ("<li>"._("Veranstaltung f&uuml;r <b>%s</b> beteiligte Einrichtungen angelegt.")."<br><br>", $count_bet_inst);
                            printf("<li>"._("<b>%d</b> %s f&uuml;r die Veranstaltung eingetragen.")."<br><br>", $count_doz, get_title_for_status('dozent', $count_doz, $_SESSION['sem_create_data']["sem_status"]));
                            if ($deputies_enabled && $count_dep > 0) {
                                printf("<li>"._("<b>%d</b> %s f&uuml;r die Veranstaltung eingetragen.")."<br><br>", $count_dep, get_title_for_status('deputy', $count_dep, $_SESSION['sem_create_data']["sem_status"]));
                            }
                            printf("<li>"._("<b>%d</b> %s f&uuml;r die Veranstaltung eingetragen.")."<br><br>", $count_tut, get_title_for_status('tutor', $count_tut, $_SESSION['sem_create_data']["sem_status"]));
                            if ($count_doms==1)
                                print "<li>"._("<b>1</b> Nutzerdom&auml;ne f&uuml;r die Veranstaltung eingetragen.")."<br><br>";
                            elseif ($count_doms>1)
                                printf ("<li>"._("<b>%s</b> Nutzerdom&auml;nen f&uuml;r die Veranstaltung eingetragen.")."<br><br>", $count_doms);
                            if ($count_bereich==1)
                                print "<li>"._("<b>1</b> Bereich f&uuml;r die Veranstaltung eingetragen.")."<br><br>";
                            elseif ($count_bereich)
                                printf ("<li>"._("<b>%s</b> Bereiche f&uuml;r die Veranstaltung eingetragen.")."<br><br>", $count_bereich);
                            //Show the result from the resources system
                            if ($GLOBALS['RESOURCES_ENABLE']) {
                                if (is_array($updateResult))
                                    foreach ($updateResult as $key=>$val) {
                                        if ($val["resource_id"]) {
                                            if ($val["overlap_assigns"] == TRUE)
                                                $resources_failed[$val["resource_id"]]=TRUE;
                                            else
                                                $resources_booked[$val["resource_id"]]=TRUE;
                                        }
                                    }
                                if ($resources_booked) {
                                    $i=0;
                                    $rooms='';
                                    foreach ($resources_booked as $key=>$val) {
                                        $resObj = ResourceObject::Factory($key);
                                        if ($i)
                                            $rooms.=", ";
                                        $rooms.= $resObj->getFormattedLink();
                                        $i++;
                                    }
                                    if (sizeof($resources_booked) == 1)
                                        printf ("<li>"._("Die Belegung des Raums %s wurde in die Ressourcenverwaltung eingetragen.")."<br><br>", $rooms);
                                    else
                                        printf ("<li>"._("Die Belegung der R&auml;ume %s wurde in die Ressourcenverwaltung eingetragen."). "<br><br>", $rooms);
                                }
                                if ($resources_failed) {
                                    $i=0;
                                    $rooms='';
                                    foreach ($resources_failed as $key=>$val) {
                                        $resObj = ResourceObject::Factory($key);
                                        if ($i)
                                            $rooms.=", ";
                                        $rooms.= $resObj->getFormattedLink();
                                        $i++;
                                    }
                                    if (sizeof($resources_failed) == 1)
                                        printf ("<li><font color=\"red\">"._("Eine oder mehrere Belegungen des Raumes %s konnte wegen &Uuml;berschneidungen nicht in die Ressourcenverwaltung eingetragen werden!")."<br>", $rooms);
                                    else
                                        printf ("<li><font color=\"red\">"._("Eine oder mehrere Belegungen der R&auml;ume %s konnten wegen &Uuml;berschneidungen nicht in die Ressourcenverwaltung eingetragen werden!")."<br>", $rooms);
                                    print _("Bitte &uuml;berpr&uuml;fen Sie manuell die Belegungen!")."</font><br><br>";
                                }
                            }

                            echo "</ul>";
                            ?>
                            </td>
                        </tr>
                    </table>
                    <br>
                    <br>
                </form>
                </td>
            </tr>
            <?
            }
            ?>
    </table>
    <?
    }

//Level 8: Erstellen des Simple-Content-Bereichs
if ($level == 8)
    {
    ?>
    <table width="100%" border=0 cellpadding=0 cellspacing=0>
        <tr>
            <td class="blank" colspan=2>&nbsp;
                <?
                if ($errormsg) parse_msg($errormsg);
                ?>
            </td>
        </tr>
        <tr>
            <td class="blank" valign="top">
                <div class="info">
                <b><?=_("Schritt 7: Erstellen einer Informationsseite"); ?></b><br><br>
                <? printf (_("Sie k&ouml;nnen nun eine frei gestaltbare Infomationsseite f&uuml;r die eben angelegte Veranstaltung <b>%s</b> eingeben."), $_SESSION['sem_create_data']["sem_name"]);
                print "<br>"._("Sie k&ouml;nnen die Bezeichnug dieser Seite frei bestimmten. Nutzen Sie sie etwa, um ungeordnete Literaturlisten oder weitere Informationen anzugeben.");
                if ($_SESSION['sem_create_data']["modules_list"]["schedule"])
                    print "<br> "._("Wenn Sie auf &raquo;weiter&laquo; klicken, haben Sie die M&ouml;glichkeit, mit dem Termin-Assistenten einen Ablaufplan f&uuml;r die Veranstaltung anzulegen.")
                ?>
                <br><br>
                </div>
            </td>
            <td class="blank" align="right" valign="top">
                <img src="<?= localePictureUrl('hands07.jpg') ?>" border="0">
            </td>
        </tr>
        <tr>
            <td class="blank" colspan=2>
            <form method="POST" name="form_8" action="<? echo URLHelper::getLink() ?>">
            <?= CSRFProtection::tokenTag() ?>
            <input type="hidden" name="form" value=8>
                <table width ="99%" cellspacing=0 cellpadding=2 border=0 align="center">
                    <tr<? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%">
                            &nbsp;
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="90%" align="center" colspan=3>
                            <?
                            echo Button::createCancel(_('Abbrechen'), 'cancel');
                            if ($_SESSION['sem_create_data']["modules_list"]["schedule"]) {
                                ?>
                                &nbsp;<?= Button::create(_('Weiter').' >>', 'jump_next') ?>
                                <?
                            } else {
                                ?>
                                &nbsp;<?= Button::create(_('�bernehmen'), 'jump_next') ?>
                                <?
                            }
                            ?>
                        </td>
                    </tr>
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%" align="right">
                            <?=_("Titel:"); ?>
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="90%"  colspan=3>
                            &nbsp;
                            <select name="sem_scm_preset">
                                <? foreach ($SCM_PRESET as $key=>$val)
                                    printf ("<option value=\"%s\" %s>%s</option>\n", $key, ($_SESSION['sem_create_data']["sem_scm_preset"] == $key) ? "selected": "", $val["name"]);
                                ?>
                            </select>&nbsp; <?=_("oder geben Sie einen beliebigen Titel ein:") ?>
                            <input type="text" name="sem_scm_name" value="<?=$_SESSION['sem_create_data']["sem_scm_name"]?>" maxlength="20" size="20">
                            <?= tooltipIcon(_("Sie k�nnen entweder einen vordefinierten Titel f�r die freie Kursseite ausw�hlen oder einen eigenen Titel frei w�hlen. Diese Titel erscheint im Reitersystem der Veranstaltung als Bezeichnug des der freien Informationsseite")) ?>
                        </td>
                    </tr>
                    <tr <? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%" align="right">
                            <?=_("Inhalt der Seite:"); ?>
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="50%"  colspan=2>
                            &nbsp; <textarea name="sem_scm_content" cols=58 rows=10><? echo $_SESSION['sem_create_data']["sem_scm_content"] ?></textarea>

                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="40%" valign="top">
                            <?
                            print "<br><font size=\"-1\">"._("Sie k&ouml;nnen auf dieser Seite s&auml;mtliche Stud.IP Formatierungen verwenden. Sie k&ouml;nnen Links normal einegeben, diesen werden automatisch sp&auml;ter als Hyperlinks dargestellt.");

                            $help_url = format_help_url("Basis.VerschiedenesFormat");
                            print "<br><br><a target=\"_blank\" href=\"".$help_url."\">"._("Hilfe zur Formatierung von Texten")."</a>";
                            print "<br><br>"._("Um eine geordnete Literaturliste zu erstellen, benutzen Sie bitte die Literaturverwaltung.")."</a></font>";
                            ?>
                            <br>
                            <?= tooltipIcon(_("In dieses Feld k�nnen Sie beliebigen Text zur Anzeige auf der Informationsseite eingeben.")) ?>
                        </td>
                    </tr>
                    <tr<? $cssSw->switchClass() ?>>
                        <td class="<? echo $cssSw->getClass() ?>" width="10%">
                            &nbsp;
                        </td>
                        <td class="<? echo $cssSw->getClass() ?>" width="90%" align="center" colspan=3>
                            <?
                            echo Button::createCancel(_('Abbrechen'), 'cancel');
                            if ($_SESSION['sem_create_data']["modules_list"]["schedule"]) {
                                ?>
                                &nbsp;<?= Button::create(_('Weiter').' >>', 'jump_next') ?>
                                <?
                            } else {
                                ?>
                                &nbsp;<?= Button::create(_('�bernehmen'), 'jump_next') ?>
                                <?
                            }
                            ?>

                        </td>
                    </tr>
                </table>
        </tr>
    </table>
    <?php
    }

include ('lib/include/html_end.inc.php');
//save all the data back to database
page_close();
?>
