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

require_once 'lib/functions.php';

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
    if ($_SESSION['SessionStart'] > UserConfig::get($user->id)->CURRENT_LOGIN_TIMESTAMP) {      // just logged in
        // store old CURRENT_LOGIN in LAST_LOGIN and set CURRENT_LOGIN to start of session
        UserConfig::get($user->id)->store('LAST_LOGIN_TIMESTAMP', UserConfig::get($user->id)->CURRENT_LOGIN_TIMESTAMP);
        UserConfig::get($user->id)->store('CURRENT_LOGIN_TIMESTAMP', $_SESSION['SessionStart']);
        //find current semester and store it in $_SESSION['_default_sem']
        $current_sem = Semester::findByTimestamp(time() + get_config('SEMESTER_TIME_SWITCH') * 7 * 24 * 60 * 60);
        if (!$current_sem ) $current_sem = Semester::findCurrent();
        $_SESSION['_default_sem'] = $current_sem->semester_id;
        //redirect user to another page if he want to, redirect is deferred to allow plugins to catch the UserDidLogin notification
        if (UserConfig::get($user->id)->PERSONAL_STARTPAGE > 0 && $i_page == "index.php" && !$perm->have_perm("root")) {
            $seminar_open_redirected = TRUE;
        }
        $user_did_login = true;
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
    && (!Request::username('username') || Request::username('username') == $user->username || $perm->have_perm('root'))) {
    $plus_nav = new Navigation('+', 'dispatch.php/profilemodules');
    $plus_nav->setDescription(_("Inhaltselemente konfigurieren"));
    Navigation::addItem('/profile/modules', $plus_nav);
}
if ($user_did_login) {
    NotificationCenter::postNotification('UserDidLogin', $user->id);
}
if ($seminar_open_redirected) {
    startpage_redirect(UserConfig::get($user->id)->PERSONAL_STARTPAGE);
}
