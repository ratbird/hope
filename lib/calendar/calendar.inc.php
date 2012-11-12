<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter010: TODO

/**
 * calendar.inc.php
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Peter Thienel <thienel@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     calendar
 */

require_once('config.inc.php');
require_once('lib/visual.inc.php');
require_once('lib/functions.php');
require_once('lib/calendar_functions.inc.php');
require_once($RELATIVE_PATH_CALENDAR . '/calendar_visual.inc.php');
require_once($RELATIVE_PATH_CALENDAR . '/lib/calendar_misc_func.inc.php');
require_once($RELATIVE_PATH_CALENDAR . '/lib/DbCalendarEvent.class.php');
require_once($RELATIVE_PATH_CALENDAR . '/lib/SeminarEvent.class.php');
require_once($RELATIVE_PATH_CALENDAR . '/lib/Calendar.class.php');
$atime = Request::int('atime', time());
$termin_id = Request::option('termin_id');
$cmd = Request::option('cmd');
$cmd_cal = Request::option('cmd_cal');
$mod_prv = Request::option('mod_prv');
$mod = Request::option('mod');

// if the calendar-settings are not loaded yet, get them from the UserConfig
$calendar_user_control_data = UserConfig::get($GLOBALS['user']->id)->CALENDAR_SETTINGS ;

// switch to own calendar if called from header
if (!get_config('CALENDAR_GROUP_ENABLE') || Request::get('caluser') == 'self') {
    closeObject();
    $_SESSION['calendar_sess_control_data']['cal_select'] = 'user.' . $GLOABLS['user']->id;
} else {
    $cal_select = Request::get('cal_select');
}
if (!is_null($cal_select)) {
    list($cal_select_range, $cal_select_id) = explode('.', $cal_select);
    if ($cal_select_range == 'user') {
        $cal_select_id = get_userid($cal_select_id);
    }
    $_SESSION['calendar_sess_control_data']['cal_select'] = $cal_select_range . '.' . $cal_select_id;
    switch ($cal_select_range) {
        case 'sem':
            URLHelper::setBaseURL($GLOBALS['ABSOLUTE_URI_STUDIP']);
            header('Location: ' . URLHelper::getURL('calendar.php', array('cid' => $cal_select_id, 'cmd' => Request::option('cmd'), 'atime' => Request::int('atime', time()))));
            exit;
            break;
        case 'inst':
            URLHelper::setBaseURL($GLOBALS['ABSOLUTE_URI_STUDIP']);
            header('Location: ' . URLHelper::getURL('calendar.php', array('cid' => $cal_select_id, 'cmd' => Request::option('cmd'), 'atime' => Request::int('atime', time()))));
            exit;
            break;
        case 'group':
        default:
            break;

    }
} else if (isset($GLOBALS['SessSemName'][1]) && $GLOBALS['SessSemName'][1] != '') {
    checkObject();
    checkObjectModule('calendar');
    object_set_visit_module('calendar');
    $cal_select_range = 'sem';
    $cal_select_id = $GLOBALS['SessSemName'][1];
    $_SESSION['calendar_sess_control_data']['cal_select'] = $cal_select_range . '.' . $cal_select_id;
} else if ($_SESSION['calendar_sess_control_data']['cal_select']) {
    list($cal_select_range, $cal_select_id) = explode('.', $_SESSION['calendar_sess_control_data']['cal_select']);
} else {
    $cal_select_range = 'user';
    $cal_select_id = $GLOBALS['user']->id;
    $_SESSION['calendar_sess_control_data']['cal_select'] = $cal_select_range . '.' . $cal_select_id;
}

if (!get_config('COURSE_CALENDAR_ENABLE') && in_array($cal_select_range, array('inst', 'sem'))) {
    $cal_select_range = 'user';
    $cal_select_id = $GLOBALS['user']->id;
    $_SESSION['calendar_sess_control_data']['cal_select'] = $cal_select_range . '.' . $cal_select_id;
}

if (Request::option('cmd') == 'export'
        && array_shift(explode('.', $_SESSION['calendar_sess_control_data']['cal_select'])) == 'group') {
    $_calendar = Calendar::getInstance(Calendar::RANGE_USER, $GLOBALS['user']->id);
} else {
    $_calendar = Calendar::getInstance($cal_select_id);
}

// use current timestamp if no timestamp is given
if (Request::submitted('mod_s')) {
    $mod = 'SINGLE';
}
if (Request::submitted('mod_d')) {
    $mod = 'DAILY';
}
if (Request::submitted('mod_w')) {
    $mod = 'WEEKLY';
}
if (Request::submitted('mod_m')) {
    $mod = 'MONTHLY';
}
if (Request::submitted('mod_y')) {
    $mod = 'YEARLY';
}

if ($mod) {
    $cmd = 'edit';
}

if (Request::submitted('store') || Request::submitted('change')) {
    $cmd = 'add';
}

if (Request::submitted('del') && $termin_id) {
    $cmd = 'del';
}

$set_recur_x = Request::submitted('set_recur');
$back_recur_x = Request::submitted('back_recur');

if ($back_recur_x) {
    unset($set_recur_x);
}

if (Request::submitted('cancel')) {
    if ($_SESSION['calendar_sess_control_data']['source']) {
        $destination = $_SESSION['calendar_sess_control_data']['source'];
        $_SESSION['calendar_sess_control_data']['source'] = '';
        page_close();
        header("Location: $destination");
        exit;
    }
    if ($_SESSION['calendar_sess_control_data']['view_prv'])
        $cmd = $_SESSION['calendar_sess_control_data']['view_prv'];
    else
        $cmd = $_SESSION['calendar_sess_control_data']['view'];
}

// allowed time range
if ($atime <= 0 || $atime > Calendar::CALENDAR_END) {
    $atime = time();
}

// check date of "go-to-function"
if (Request::get('jmp_month') && check_date(Request::int('jmp_month'), Request::int('jmp_day'), Request::int('jmp_year'))) {
    $atime = mktime(12, 0, 0, Request::int('jmp_month'), Request::int('jmp_day'), Request::int('jmp_year'));
}

// delete all expired events and count events
$db_control = CalendarDriver::getInstance($GLOBALS['user']->id);
if ($cmd == 'add' && $calendar_user_control_data['delete'] > 0) {
    $expire_delete = mktime(date('G', time()), date('i', time()), 0, date('n', time()) - $calendar_user_control_data['delete'], date('j', time()), date('Y', time()));
    $db_control->deleteFromDatabase('EXPIRED', '', 0, $expire_delete);
}
$db_control->openDatabase('COUNT', 'CALENDAR_EVENTS');
$count_events = $db_control->getCountEvents();
if (Request::getArray('sem') && $_calendar->getRange() == Calendar::RANGE_USER) {
    $_calendar->updateBindSeminare();
}

if ($cmd == '') {
    if ($termin_id) {
        // if termin_id is given always change in edit mode
        $cmd = 'edit';
    } else {
        $cmd = $calendar_user_control_data['view'];
    }
}

$_calendar->setUserSettings($calendar_user_control_data);

$accepted_vars = array('start_m', 'start_h', 'start_day', 'start_month', 'start_year', 'end_m',
    'end_h', 'end_day', 'end_month', 'end_year', 'exp_day', 'exp_month',
    'exp_year', 'cat', 'priority', 'txt', 'content', 'loc', 'linterval_d',
    'linterval_w', 'type_d', 'type_m', 'linterval_m2', 'sinterval_m',
    'linterval_m1', 'wday_m', 'day_m', 'type_y', 'sinterval_y', 'wday_y',
    'day_y', 'month_y1', 'month_y2', 'atime', 'termin_id', 'exp_c', 'via',
    'cat_text', 'mod_prv', 'exc_day', 'exc_month', 'exc_year', 'exceptions',
    'add_exc_x', 'del_exc_x', 'exp_count', 'select_user', 'evtype');

if ($cmd == 'add' || $cmd == 'edit') {
    if (!isset($_SESSION['calendar_sess_forms_data'])) {
        $_SESSION['calendar_sess_forms_data'] = array();
    }

    if (Request::isPost()) {
        // Formulardaten uebernehmen
        foreach ($accepted_vars as $key) {
            if (!is_null(Request::get($key))) {
                $_SESSION['calendar_sess_forms_data'][$key] = Request::get($key);
            }
        }
        if (sizeof(Request::getArray('exc_delete'))) {
            $_SESSION['calendar_sess_forms_data']['exc_delete'] = Request::getArray('exc_delete');
        }
        if (sizeof(Request::getArray('wdays'))) {
            $_SESSION['calendar_sess_forms_data']['wdays'] = Request::intArray('wdays');
        }
    } else {
        $_SESSION['calendar_sess_control_data']['mod'] = '';
    }
    // checkbox-values
    if (!$set_recur_x) {
        $_SESSION['calendar_sess_forms_data']['wholeday'] = Request::get('wholeday');
    }
} elseif ($cmd != 'export') {
    unset($_SESSION['calendar_sess_forms_data']);
}

if (Request::quoted('source_page') && ($cmd == 'edit' || $cmd == 'add' || $cmd == 'delete')) {
    $_SESSION['calendar_sess_control_data']['source'] = preg_replace('![^0-9a-z+_?&#/=.-\[\]]!i', '', rawurldecode(Request::quoted('source_page')));
}

// Seitensteuerung
$HELP_KEYWORD = "Basis.Terminkalender";

// switch navigation by range
if ($_calendar->getRange() == Calendar::RANGE_SEM || $_calendar->getRange() == Calendar::RANGE_INST) {
    $calendar_range = 'course';
} else {
    $calendar_range = 'calendar';
}

if ($cmd == 'add') {
    // Ueberpruefung der Formulareingaben
    $err = Calendar::checkFormData($_SESSION['calendar_sess_forms_data']);
    // wenn alle Daten OK, dann Termin anlegen, oder bei vorhandener
    // termin_id updaten
    if (empty($err) && $count_events < $CALENDAR_MAX_EVENTS) {
        $_calendar->addEvent($termin_id, $select_user);
        $atime = $_calendar->event->getStart();
        if ($_SESSION['calendar_sess_control_data']['source']) {
            $destination = $_SESSION['calendar_sess_control_data']['source'] . "#a";
            $_SESSION['calendar_sess_control_data']['source'] = '';
            unset($_SESSION['calendar_sess_forms_data']);
            page_close();
            header('Location: ' . $destination);
            exit;
        }

        if (!empty($_SESSION['calendar_sess_control_data']['view_prv'])) {
            $cmd = $_SESSION['calendar_sess_control_data']['view_prv'];
        } else {
            $cmd = 'showday';
        }

        unset($_SESSION['calendar_sess_forms_data']);
    } else {
        // wrong data? -> switch back to edit mode
        $cmd = 'edit';
        $_calendar->restoreEvent($termin_id);
        $_calendar->setEventProperties($_SESSION['calendar_sess_forms_data'], $mod);
        $mod = $mod_prv ? $mod_prv : 'SINGLE';
        if ($back_recur_x) {
            $set_recur_x = 1;
            unset($back_recur_x);
        }
    }
}

if ($cmd == 'del') {
    $_calendar->deleteEvent($termin_id);

    if ($_SESSION['calendar_sess_control_data']['source']) {
        $destination = $_SESSION['calendar_sess_control_data']['source'];
        $_SESSION['calendar_sess_control_data']['source'] = '';
        header("Location: $destination");
        page_close();
        die;
    }

    if (!empty($_SESSION['calendar_sess_control_data']['view_prv'])) {
        $cmd = $_SESSION['calendar_sess_control_data']['view_prv'];
    } else {
        $cmd = 'showday';
    }

    unset($_SESSION['calendar_sess_forms_data']);
}

switch ($cmd) {
    /*
    case 'showlist':
        if ($_calendar->getRange() == Calendar::RANGE_GROUP) {
            $cmd = 'showweek';
            Navigation::activateItem("/$calendar_range/calendar/week");
        } else {
            Navigation::activateItem("/$calendar_range/calendar/list");
        }
        $_SESSION['calendar_sess_control_data']['view_prv'] = $cmd;
        break;
    */
    case 'showday':
        if ($_calendar->checkPermission(Calendar::PERMISSION_OWN)) {
            PageLayout::setTitle(_("Mein persönlicher Terminkalender - Tagesansicht"));
        } else if ($_calendar->getRange() == Calendar::RANGE_SEM || $_calendar->getRange() == Calendar::RANGE_INST) {
            PageLayout::setTitle(getHeaderLine($_calendar->user_id) . ' - ' . _("Terminkalender - Tagesansicht"));
        } else {
            PageLayout::setTitle(sprintf(_("Terminkalender von %s %s - Tagesansicht"), get_fullname($_calendar->getUserId()), $_calendar->perm_string));
        }
        $_SESSION['calendar_sess_control_data']['view_prv'] = $cmd;
        Navigation::activateItem("/$calendar_range/calendar/day");
        break;

    case 'showweek':
        if ($_calendar->checkPermission(Calendar::PERMISSION_OWN)) {
            PageLayout::setTitle(_("Mein persönlicher Terminkalender - Wochenansicht"));
        } else if ($_calendar->getRange() == Calendar::RANGE_SEM || $_calendar->getRange() == Calendar::RANGE_INST) {
            PageLayout::setTitle(getHeaderLine($_calendar->user_id) . ' - ' . _("Terminkalender - Wochenansicht"));
        } else {
            PageLayout::setTitle(sprintf(_("Terminkalender von %s %s - Wochenansicht"), get_fullname($_calendar->getUserId()), $_calendar->perm_string));
        }
        Navigation::activateItem("/$calendar_range/calendar/week");
        $_SESSION['calendar_sess_control_data']['view_prv'] = $cmd;
        break;

    case 'showmonth':
        if ($_calendar->checkPermission(Calendar::PERMISSION_OWN)) {
            PageLayout::setTitle(_("Mein persönlicher Terminkalender - Monatsansicht"));
        } else if ($_calendar->getRange() == Calendar::RANGE_SEM || $_calendar->getRange() == Calendar::RANGE_INST) {
            PageLayout::setTitle(getHeaderLine($_calendar->user_id) . ' - ' . _("Terminkalender - Monatsansicht"));
        } else {
            PageLayout::setTitle(sprintf(_("Terminkalender von %s %s - Monatsansicht"), get_fullname($_calendar->getUserId()), $_calendar->perm_string));
        }
        Navigation::activateItem("/$calendar_range/calendar/month");
        $_SESSION['calendar_sess_control_data']['view_prv'] = $cmd;
        break;

    case 'showyear':
        if ($_calendar->checkPermission(Calendar::PERMISSION_OWN)) {
            PageLayout::setTitle(_("Mein persönlicher Terminkalender - Jahresansicht"));
        } else if ($_calendar->getRange() == Calendar::RANGE_SEM || $_calendar->getRange() == Calendar::RANGE_INST) {
            PageLayout::setTitle(getHeaderLine($_calendar->user_id) . ' - ' . _("Terminkalender - Jahresansicht"));
        } else {
            PageLayout::setTitle(sprintf(_("Terminkalender von %s %s - Jahresansicht"), get_fullname($_calendar->getUserId()), $_calendar->perm_string));
        }
        Navigation::activateItem("/$calendar_range/calendar/year");
        $_SESSION['calendar_sess_control_data']['view_prv'] = $cmd;
        break;

    case 'export':
        Navigation::activateItem("/$calendar_range/calendar/export");
        if ($_calendar->getRange() == Calendar::RANGE_SEM || $_calendar->getRange() == Calendar::RANGE_INST) {
            PageLayout::setTitle(getHeaderLine($_calendar->user_id) . ' - ' . _("Terminkalender - Termine exportieren"));
        } else if ($_calendar->checkPermission(Calendar::PERMISSION_OWN)) {
            PageLayout::setTitle(_("Mein persönlicher Terminkalender - Termindaten importieren, exportieren und synchronisieren"));
        } else {
            PageLayout::setTitle(sprintf(_("Terminkalender von %s %s - Termindaten exportieren"), get_fullname($_calendar->getUserId()), $_calendar->perm_string));
        }
        break;

    case 'bind':
        PageLayout::setHelpKeyword("Basis.TerminkalenderEinbinden");
        PageLayout::setTitle(_("Mein persönlicher Terminkalender - Veranstaltungstermine einbinden"));
        Navigation::activateItem('/calendar/calendar/course');
        break;

    case 'edit':
        PageLayout::setHelpKeyword("Basis.TerminkalenderBearbeiten");
        Navigation::activateItem("/$calendar_range/calendar/edit");

        if ($termin_id) {
            $evtype = Request::get('evtype', '');
            if ($evtype == 'sem' || $evtype == 'semcal') {
                $_calendar->createSeminarEvent($evtype);
                if (!$_calendar->event->restore($termin_id)) {
                    // something wrong... better to go back to the last view
                    page_close();
                    header('Location: ' . URLHelper::getLink('?cmd=' . $_SESSION['calendar_sess_control_data']['view_prv'] . '&atime=' .$atime));
                    exit;
                }
                $atime = $_calendar->event->getStart();
            } else {
                // get event from database
                $_calendar->restoreEvent($termin_id);
                if (!$mod) {
                    $mod = $_calendar->event->getRepeat('rtype');
                }
                $atime = $_calendar->event->getStart();
            }
            if ($_calendar->getRange() == Calendar::RANGE_SEM || $_calendar->getRange() == Calendar::RANGE_INST) {
                PageLayout::setTitle(getHeaderLine($_calendar->user_id) . ' - ' . _("Terminkalender - Termin bearbeiten"));
            } else if ($_calendar instanceof GroupCalendar) {
                PageLayout::setTitle(sprintf(_("Terminkalender der Gruppe %s - Termin bearbeiten"), $_calendar->getGroupName()));
            } else if ($_calendar->checkPermission(Calendar::PERMISSION_OWN)) {
                PageLayout::setTitle(_("Mein persönlicher Terminkalender - Termin bearbeiten"));
            } else {
                PageLayout::setTitle(sprintf(_("Terminkalender von %s %s - Termin bearbeiten"), get_fullname($_calendar->getUserId()), $text_permission));
            }
        } elseif ($_calendar->havePermission(Calendar::PERMISSION_WRITABLE)) {
            if ($_calendar->getRange() == Calendar::RANGE_SEM || $_calendar->getRange() == Calendar::RANGE_INST) {
                PageLayout::setTitle(getHeaderLine($_calendar->user_id) . ' - ' . _("Terminkalender - Termin anlegen"));
            } else if ($_calendar instanceof GroupCalendar) {
                PageLayout::setTitle(sprintf(_("Terminkalender der Gruppe %s - Termin anlegen"), $_calendar->getGroupName()));
            } else if ($_calendar->checkPermission(Calendar::PERMISSION_OWN)) {
                PageLayout::setTitle(_("Mein persönlicher Terminkalender - Termin anlegen"));
            } else {
                PageLayout::setTitle(sprintf(_("Terminkalender von %s %s - Termin anlegen"), get_fullname($_calendar->getUserId()), $text_permission));
            }
            // call from dayview for new event -> set default values
            if ($atime && !Request::isPost()) {
                if (Request::option('devent')) {
                    $properties = array(
                        'DTSTART' => mktime(0, 0, 0, date('n', $atime), date('j', $atime), date('Y', $atime)),
                        'DTEND' => mktime(23, 59, 59, date('n', $atime), date('j', $atime), date('Y', $atime)),
                        'SUMMARY' => _("Kein Titel"),
                        'STUDIP_CATEGORY' => 1,
                        'CATEGORIES' => '',
                        'CLASS' => 'PRIVATE',
                        'RRULE' => array('rtype' => 'SINGLE'));
                    $_calendar->createEvent($properties);
                    $_calendar->event->setDayEvent(true);
                } else {
                    $properties = array(
                        'DTSTART' => $atime,
                        'DTEND' => mktime(date('G', $atime) + 1, date('i', $atime), 0, date('n', $atime), date('j', $atime), date('Y', $atime)),
                        'SUMMARY' => _("Kein Titel"),
                        'STUDIP_CATEGORY' => 1,
                        'CATEGORIES' => '',
                        'CLASS' => 'PRIVATE',
                        'RRULE' => array('rtype' => 'SINGLE'));
                    $_calendar->createEvent($properties);
                }

                //      $_calendar->event->setRepeat(array('rtype' => 'SINGLE'));
            } else {
                $properties = array();
                $_calendar->createEvent($properties);
            }
        } else {
            page_close();
            header('Location: ' . URLHelper::getLink('?cmd=' . $_SESSION['calendar_sess_control_data']['view_prv'] . '&atime='. $atime));
            exit;
        }
        if ($_calendar->havePermission(Calendar::PERMISSION_READABLE)) {
            if (!Request::isPost()) {
                $_calendar->getEventProperties($_SESSION['calendar_sess_forms_data']);
            } else {
                $err = Calendar::checkFormData($_SESSION['calendar_sess_forms_data']);
                if (empty($err)) {
                    $_calendar->setEventProperties($_SESSION['calendar_sess_forms_data'], $mod);
                } else {
                    if ($back_recur_x) {
                        $set_recur_x = 1;
                    } elseif ($set_recur_x && $err['set_recur']) {
                        $mod = $mod_prv;
                    } elseif ($set_recur_x) {
                        unset($set_recur_x);
                    }
                }
            }
            extract($_SESSION['calendar_sess_forms_data'], EXTR_OVERWRITE);
        }
        break;
}
/*
if (!$_calendar->havePermission(Calendar::PERMISSION_WRITABLE)) {
    Navigation::removeItem("/$calendar_range/calendar/edit");
}
*/
// Tagesuebersicht anzeigen ***************************************************
if ($cmd == 'showday') {

    $at = date('G', $atime);
    if ($at >= $calendar_user_control_data['start']
            && $at <= $calendar_user_control_data['end'] || !$atime) {
        $st = $calendar_user_control_data['start'];
        $et = $calendar_user_control_data['end'];
    } elseif ($at < $calendar_user_control_data['start']) {
        $st = 0;
        $et = $calendar_user_control_data['start'] + 2;
    } else {
        $st = $calendar_user_control_data['end'] - 2;
        $et = 23;
    }

    $tmpl = $GLOBALS['template_factory']->open('calendar/day_view');
    $tmpl->_calendar = $_calendar;
    $tmpl->atime = $atime;
    $tmpl->cmd = $cmd;
    $tmpl->st = $st;
    $tmpl->et = $et;
    $view = $tmpl->render();
    include('lib/include/html_head.inc.php');
    include('lib/include/header.php');
    echo $view;
}

// Wochenuebersicht anzeigen **************************************************
if ($cmd == 'showweek') {
    $at = date('G', $atime);
    if ($at >= $calendar_user_control_data['start']
            && $at <= $calendar_user_control_data['end'] || !$atime) {
        $st = $calendar_user_control_data['start'];
        $et = $calendar_user_control_data['end'];
    } elseif ($at < $calendar_user_control_data['start']) {
        $st = 0;
        $et = $calendar_user_control_data['start'] + 2;
    } else {
        $st = $calendar_user_control_data['end'] - 2;
        $et = 23;
    }

    include_once($RELATIVE_PATH_CALENDAR . '/lib/DbCalendarWeek.class.php');

    $tmpl = $GLOBALS['template_factory']->open('calendar/week_view');
    $tmpl->_calendar = $_calendar;
    $tmpl->atime = $atime;
    $tmpl->cmd = $cmd;
    $tmpl->st = $st;
    $tmpl->et = $et;
    $view = $tmpl->render();
    include('lib/include/html_head.inc.php');
    include('lib/include/header.php');
    echo $view;

}

// Monatsuebersicht anzeigen **************************************************

if ($cmd == 'showmonth') {

    include($RELATIVE_PATH_CALENDAR . "/views/month.inc.php");
}

// Jahresuebersicht ***********************************************************

if ($cmd == 'showyear') {

    include($RELATIVE_PATH_CALENDAR . "/views/year.inc.php");
}

// Listenansicht ***************************************************************
/*
if ($cmd == 'showlist') {
    require_once($RELATIVE_PATH_CALENDAR . "/lib/DbCalendarEventList.class.php");
    $event_list_start = $atime;
    $event_list_end = mktime(23, 59, 59, date('n', $event_list_start), date('j', $event_list_start) + 14, date('Y', $event_list_start));

    if ($_calendar->getPermission() == Calendar::PERMISSION_OWN) {
        $view = new DbCalendarEventList($_calendar, $event_list_start, $event_list_end, true, Calendar::getBindSeminare(), Request::int('cal_restrict'));
    } else {
        $view = new DbCalendarEventList($_calendar, $event_list_start, $event_list_end, true, Calendar::getBindSeminare($_calendar->getUserId()), Request::int('cal_restrict', ''));
    }

    if (isset($_REQUEST['dopen'])) {
        $_SESSION['calendar_sess_control_data']['dopen'] = htmlentities(substr(Request::get('dopen'), 0, 45));
    }
    if (isset($dclose)) {
        unset($_SESSION['calendar_sess_control_data']['dopen']);
    }
    if (isset($_SESSION['calendar_sess_control_data']['dopen'])) {
        $_REQUEST['dopen'] = $_SESSION['calendar_sess_control_data']['dopen'];
    }

    if ($_calendar->getRange() == Calendar::RANGE_SEM || $_calendar->getRange() == Calendar::RANGE_INST) {
        PageLayout::setTitle(getHeaderLine($_calendar->user_id) . ' - ' . _("Terminkalender - Listenansicht"));
    } else if ($_calendar->checkPermission(Calendar::PERMISSION_OWN)) {
        PageLayout::setTitle(_("Mein persönlicher Terminkalender - Listenansicht"));
    } else {
        PageLayout::setTitle(sprintf(_("Terminkalender von %s %s - Listenansicht"), get_fullname($_calendar->getUserId()), $_calendar->perm_string));
    }

    include($RELATIVE_PATH_CALENDAR . "/views/list.inc.php");
}
*/
// edit an event *********************************************************
// ist $termin_id an das Skript uebergeben worden, dann bearbeite diesen Termin
// ist $atime an das Skript uebergeben worden, dann erzeuge neuen Termin (s.o.)
if ($cmd == 'edit') {
    if ($_calendar->havePermission(Calendar::PERMISSION_READABLE)) {
        if (!$mod) {
            $mod = 'SINGLE';
        }

        // start and end time in 5 minute steps
        $start_m = $start_m - ($start_m % 5);
        $end_m = $end_m - ($end_m % 5);

        if ($_calendar->event) {
            $repeat = $_calendar->event->getRepeat();
        }

        include $RELATIVE_PATH_CALENDAR . '/views/edit.inc.php';
    }
}

// Seminartermine einbinden **************************************************

if ($cmd == 'bind') {

    include $RELATIVE_PATH_CALENDAR . '/views/bind.inc.php';
}

// Termine importieren/exportieren/synchronisieren ***************************
if ($cmd == 'export') {

    include $RELATIVE_PATH_CALENDAR . '/views/export.inc.php';
}

echo "</td></tr>\n</table>\n";

include ('lib/include/html_end.inc.php');
page_close();

