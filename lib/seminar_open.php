<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
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

/**
 * @addtogroup notifications
 *
 * Logging in triggers a UserDidLogin notification. The user's ID is
 * transmitted as subject of the notification.
 */

require_once 'lib/classes/SemesterData.class.php';
require_once 'lib/functions.php';

// set default Values for messaging
function check_messaging_default($my_messaging_settings) {

    if (!$my_messaging_settings['show_only_buddys'])
        $my_messaging_settings['show_only_buddys'] = FALSE;
    if (!$my_messaging_settings['delete_messages_after_logout'])
        $my_messaging_settings['delete_messages_after_logout'] = FALSE;
    if (!$my_messaging_settings['start_messenger_at_startup'])
        $my_messaging_settings['start_messenger_at_startup'] = FALSE;
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
    return $my_messaging_settings;
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
        $semester = SemesterData::GetInstance();
        $cfg = Config::GetInstance();
        $actual_sem = $semester->getSemesterDataByDate(time() + $cfg->getValue('SEMESTER_TIME_SWITCH') * 7 * 24 * 60 * 60);
        if (!is_array($actual_sem)) $actual_sem = $semester->getCurrentSemesterData();
        $_SESSION['_default_sem'] = $actual_sem['semester_id'];
    }
}
//redirect the user where he want to go today....
function startpage_redirect($page_code) {
    switch ($page_code) {
        case 1:
        case 2:
            $jump_page = "meine_seminare.php";
        break;
        case 3:
            $jump_page = "dispatch.php/calendar/schedule";
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

global $i_page,
       $DEFAULT_LANGUAGE, $SessSemName, $SessionSeminar,
       $sess, $auth, $user, $perm,
       $CALENDAR_ENABLE, $_language_path;

//get the name of the current page in $i_page
$i_page = basename($_SERVER['PHP_SELF']);

//INITS
$seminar_open_redirected = false;
$user_did_login = false;

// session init starts here
if ($_SESSION['SessionStart'] == 0) {
    $_SESSION['SessionStart'] = time();
    $_SESSION['object_cache'] = array();

    // try to get accepted languages from browser
    if (!isset($_SESSION['_language']))
        $_SESSION['_language'] = get_accepted_languages();
    if (!$_SESSION['_language'])
        $_SESSION['_language'] = $DEFAULT_LANGUAGE; // else use system default
}

// user init starts here
if ($auth->is_authenticated() && is_object($user) && $user->id != "nobody") {
    if ($_SESSION['SessionStart'] > UserConfig::get($user->id)->__get('CurrentLogin')) {      // just logged in
        // register all user variables
        //TODO: was wird hier noch gebraucht? was kann in UserConfig?
        $LastLogin = $CurrentLogin;
        $CurrentLogin = $_SESSION['SessionStart'];
        UserConfig::get($user->id)->store('CurrentLogin', $CurrentLogin);
        UserConfig::get($user->id)->store('LastLogin', $LastLogin);
               // call default functions
        check_semester_default();

        if($CALENDAR_ENABLE){
            $user->register("calendar_user_control_data");
            check_calendar_default();
        }
        $my_studip_settings = UserConfig::get($user->id)->__get('my_studip_settings');
        //redirect user to another page if he want to
        if (($my_studip_settings["startpage_redirect"]) && ($i_page == "index.php") && (!$perm->have_perm("root"))){
            $seminar_open_redirected = TRUE;
        }
        $user_did_login = true;
    }

    // lauch stud.ip messenger after login
    $my_messaging_settings = json_decode( UserConfig::get($user->id)->__get('my_messaging_settings'), true );
    $my_messaging_settings = check_messaging_default($my_messaging_settings);
    if ($my_messaging_settings['start_messenger_at_startup'] && $auth->auth['jscript'] &&
        !$seminar_open_redirected && !$_SESSION['messenger_started']) {
        PageLayout::addHeadElement('script', array('type' => 'text/javascript'),
                'fenster = window.open("'.URLHelper::getURL('studipim.php').'", "im_'.$user->id.'", "scrollbars=yes,width=400,height=300", "resizable=no");');
        $_SESSION['messenger_started'] = true;
    }
}


// init of output via I18N
$_language_path = init_i18n($_SESSION['_language']);

// Try to select the course or institute given by the parameter 'cid'
// in the current request. For compatibility reasons there is a fallback to
// the last selected one from the session

$course_id = Request::option('cid', $_SESSION['SessionSeminar']);

// Select the current course or institute if we got one from 'cid' or session.
// This also binds the global $_SESSION['SessionSeminar']
// variable to the URL parameter 'cid' for all generated links.
if (isset($course_id)) {
    selectSem($course_id) || selectInst($course_id);
    unset($course_id);
}

// load the default set of plugins
PluginEngine::loadPlugins();

// add navigation item: add modules
if (Navigation::hasItem('/course/admin')
    && ($perm->have_studip_perm('tutor', $SessSemName[1]) && $SessSemName['class'] == 'sem')
    && ($SessSemName['class'] != 'sem' || !$GLOBALS['SEM_CLASS'][$GLOBALS['SEM_TYPE'][$SessSemName['art_num']]['class']]['studygroup_mode'])) {
    $plus_nav = new Navigation('+', 'dispatch.php/course/plus/index');
    $plus_nav->setDescription(_("Inhaltselemente konfigurieren"));
    Navigation::addItem('/course/modules', $plus_nav);
}
// add navigation item for profile: add modules
if (Navigation::hasItem('/profile')
    && (!Request::option('username') || Request::option('username') == $auth->auth['uname'] || $perm->have_perm('root'))) {
    $plus_nav = new Navigation('+', 'dispatch.php/profilemodules');
    $plus_nav->setDescription(_("Inhaltselemente konfigurieren"));
    Navigation::addItem('/profile/modules', $plus_nav);
}
if ($user_did_login) {
    NotificationCenter::postNotification('UserDidLogin', $user->id);
}
if ($seminar_open_redirected) {
    startpage_redirect($my_studip_settings["startpage_redirect"]);
}
