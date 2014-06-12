<?php
# Lifter001: DONE
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: TODO
/*
seminar_main.php - Die Eingangs- und Uebersichtsseite fuer ein Seminar
Copyright (C) 2000 Stefan Suchi <suchi@gmx.de>, Ralf Stockmann <rstockm@gwdg.de>

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
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Default_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$auth->login_if(Request::get('again') && ($auth->auth["uid"] == "nobody"));

if (Request::option('auswahl')) {
    Request::set('cid', Request::option('auswahl'));
}

include ('lib/seminar_open.php'); // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page

$course_id = $_SESSION['SessionSeminar'];

//set visitdate for course, when coming from my_courses
if (Request::get('auswahl')) {
    object_set_visit($course_id, "sem");
}

// gibt es eine Anweisung zur Umleitung?
if (Request::get('redirect_to')) {
    $query_parts = explode('&', stristr(urldecode($_SERVER['QUERY_STRING']), 'redirect_to'));
    list( , $where_to) = explode('=', array_shift($query_parts));
    $new_query = $where_to . '?' . join('&', $query_parts);
    $new_query = preg_replace('/[^:0-9a-z+_#?&=.-\/]/i', '', $new_query);
    header('Location: '.URLHelper::getURL($new_query, array('cid' => $course_id)));
    die;
}


$sem = new Seminar($course_id);
$sem_class = $GLOBALS['SEM_CLASS'][$GLOBALS['SEM_TYPE'][$sem->status]['class']];
$sem_class || $sem_class = SemClass::getDefaultSemClass();

if ($sem_class->getSlotModule("overview")) {
    foreach ($sem_class->getNavigationForSlot("overview") as $nav) {
        header('Location: '.URLHelper::getURL($nav->getURL()));
        die;
    }
} else {
    $Modules = new Modules();
    $course_modules = $Modules->getLocalModules($course_id);
    if (!$course_modules['overview'] && !$sem_class->isSlotMandatory("overview")) {
        //Keine �bersichtsseite. Anstatt eines Fehler wird der Nutzer zum ersten
        //Reiter der Veranstaltung weiter geleitet.
        if (Navigation::hasItem("/course")) {
            foreach (Navigation::getItem("/course")->getSubNavigation() as $navigation) {
                header('Location: '.URLHelper::getURL($navigation->getURL()));
                die;
            }
        }
    }
}
