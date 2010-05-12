<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/*
seminar_open.php - Initialises a Stud.IP sesssion
Copyright (C) 2000 Stefan Suchi <suchi@data-quest.de>

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
require_once 'lib/classes/SemesterData.class.php';
require_once 'lib/functions.php';

// set default Values for messaging
function check_messaging_default() {
    global $my_messaging_settings;

    if (!$my_messaging_settings['show_only_buddys'])
        $my_messaging_settings['show_only_buddys'] = FALSE;
    if (!$my_messaging_settings['delete_messages_after_logout'])
        $my_messaging_settings['delete_messages_after_logout'] = FALSE;
    if (!$my_messaging_settings['start_messenger_at_startup'])
        $my_messaging_settings['start_messenger_at_startup'] = FALSE;
    if (!$my_messaging_settings['active_time'])
        $my_messaging_settings['active_time'] = 5;
    if (!$my_messaging_settings['default_setted'])
        $my_messaging_settings['default_setted'] = time();
    if (!$my_messaging_settings['last_login'])
        $my_messaging_settings['last_login'] = FALSE;
    if (!$my_messaging_settings['timefilter'])
        $my_messaging_settings['timefilter'] = "30d";
    if (!$my_messaging_settings['opennew'])
        $my_messaging_settings['opennew'] = 1;
    if (!$my_messaging_settings['logout_markreaded'])
        $my_messaging_settings['logout_markreaded'] = FALSE;
    if (!$my_messaging_settings['openall'])
        $my_messaging_settings['openall'] = FALSE;
    if (!$my_messaging_settings['addsignature'])
        $my_messaging_settings['addsignature'] = FALSE;
    if (!$my_messaging_settings['save_snd'])
        $my_messaging_settings['save_snd'] = 1;
    if (!$my_messaging_settings['sms_sig'])
        $my_messaging_settings['sms_sig'] = FALSE;
    if (!$my_messaging_settings['send_view'])
        $my_messaging_settings['send_view'] = FALSE;
    if (!$my_messaging_settings['last_box_visit'])
        $my_messaging_settings['last_box_visit'] = 1;
    if (!$my_messaging_settings['folder']['in'])
        $my_messaging_settings['folder']['in'][0] = "dummy";
    if (!$my_messaging_settings['folder']['out'])
        $my_messaging_settings['folder']['out'][0] = "dummy";
    if (!$my_messaging_settings['confirm_reading'])
        $my_messaging_settings['confirm_reading'] = 3;
    if (!$my_messaging_settings['show_sndpicture'])
        $my_messaging_settings['show_sndpicture'] = FALSE;
}

// set default Values for schedule (timetable)
function check_schedule_default() {
    global $my_schedule_settings;

    if (!$my_schedule_settings ||
        $my_schedule_settings['glb_start_time'] === NULL ||
        $my_schedule_settings['glb_end_time'] === NULL ) {
        $my_schedule_settings=array(
            "glb_start_time"=>8,
            "glb_end_time"=>19,
            "glb_days"=>array(
                "mo"=>"TRUE",
                "di"=>"TRUE",
                "mi"=>"TRUE",
                "do"=>"TRUE",
                "fr"=>"TRUE",
                "sa"=>"",
                "so"=>""
            ),
            "default_setted"=>time()
        );
    }
}

// set default Values for calendar
function check_calendar_default(){
    global $calendar_user_control_data;

    if(!$calendar_user_control_data){
        $calendar_user_control_data = array(
            "view"             => "showweek",
            "start"            => 9,
            "end"              => 20,
            "step_day"         => 900,
            "step_week"        => 3600,
            "type_week"        => "LONG",
            "holidays"         => TRUE,
            "sem_data"         => TRUE,
            "link_edit"        => TRUE,
            "bind_seminare"    => "",
            "ts_bind_seminare" => 0,
            "delete"           => 0
        );
    }
}

function check_semester_default(){
    if ($GLOBALS['perm']->have_perm('user')){
        $GLOBALS['sess']->register("_default_sem");
        $semester = SemesterData::GetInstance();
        $cfg = Config::GetInstance();
        $actual_sem = $semester->getSemesterDataByDate(time() + $cfg->getValue('SEMESTER_TIME_SWITCH') * 7 * 24 * 60 * 60);
        if (!is_array($actual_sem)) $actual_sem = $semester->getCurrentSemesterData();
        $GLOBALS['_default_sem'] = $actual_sem['semester_id'];
    }
}
//redirect the user whre he want to go today....
function startpage_redirect($page_code) {
    switch ($page_code) {
        case 1:
        case 2:
            $jump_page = "meine_seminare.php";
        break;
        case 3:
            $jump_page = "mein_stundenplan.php";
        break;
        case 4:
            $jump_page = "contact.php";
        break;
        case 5:
            $jump_page = "calendar.php";
        break;
    }
    page_close();
    header ("location: $jump_page");
    exit;
}


require_once('lib/language.inc.php');

global $i_page, $i_query,
       $SessionStart, $SessionSeminar, $SessSemName,
       $object_cache, $contact,
       $_language, $DEFAULT_LANGUAGE,
       $sess, $auth, $user, $perm,
       $CurrentLogin, $LastLogin, $forum, $writemode,
       $my_messaging_settings, $my_schedule_settings,
       $my_personal_sems, $my_studip_settings, $homepage_cache_own,
       $CALENDAR_ENABLE, $seminar_open_redirected, $_language_path;

//get the name of the current page in $i_page
$i_page = basename($_SERVER['PHP_SELF']);

// function to get the parameters of the current page in array $i_query

$i_query = explode('&',getenv("QUERY_STRING"));

//INITS
// session init starts here
if ($SessionStart == 0) {
    $SessionStart = time();
    $SessionSeminar = '';
    $SessSemName = array();
    $sess->register("SessionStart");
    $sess->register("SessionSeminar");
    $sess->register("SessSemName");
    $sess->register("object_cache");
    $sess->register("contact");
//??    $object_cache[] = " "; // sollte das Array hier geloescht oder initialisiert werden? js
    $object_cache = array();

    // Language Settings
    $sess->register("_language");
    // try to get accepted languages from browser
    if (!isset($_language))
        $_language = get_accepted_languages();
    if (!$_language)
        $_language = $DEFAULT_LANGUAGE; // else use system default
}

// user init starts here
if ($auth->is_authenticated() && is_object($user) && $user->id != "nobody") {
    if ($SessionStart > $CurrentLogin) {      // just logged in
        // register all user variables
        $LastLogin=$CurrentLogin;
        $CurrentLogin=$SessionStart;
        $user->register("CurrentLogin");
        $user->register("LastLogin");
        $user->register("forum");
        $user->register("writemode");  // forum postings
        $user->register("my_messaging_settings");
        $user->register("my_schedule_settings");
        $user->register("my_personal_sems");
        $user->register("my_studip_settings");
        $user->register("homepage_cache_own");


        // call default functions
        check_messaging_default();
        check_schedule_default();
        check_semester_default();

        if($CALENDAR_ENABLE){
            $user->register("calendar_user_control_data");
            check_calendar_default();
        }

        //redirect user to another page if he want to
        if (($my_studip_settings["startpage_redirect"]) && ($i_page == "index.php") && (!$perm->have_perm("root"))){
            $seminar_open_redirected = TRUE;
            startpage_redirect($my_studip_settings["startpage_redirect"]);
        }
    }
}


// init of output via I18N
$_language_path = init_i18n($_language);

//force reload of config to get translated data
include 'config.inc.php';

// Try to select the course or institute given by the parameter 'cid'
// in the current request. This also binds the global $SessionSeminar
// variable to the URL parameter 'cid' for all generated links.

URLHelper::bindLinkParam('cid', $SessionSeminar);

if (isset($SessionSeminar) && $SessionSeminar != '') {
    $course_id = $SessionSeminar;
    selectSem($course_id) || selectInst($course_id);
    unset($course_id);
}

// load the default set of plugins
PluginEngine::loadPlugins();
