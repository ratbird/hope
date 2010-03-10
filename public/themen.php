<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/*
themen.php: Redirector page for theme administration view-modes

Copyright (C) 2005-2007 Till Glöggler <tgloeggl@uos.de>

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

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check("tutor");

include ("lib/seminar_open.php"); // initialise Stud.IP-Session
require_once('lib/raumzeit/raumzeit_functions.inc.php');
unQuoteAll();
$sess->register('viewModeFilter');

if ($list) {
    $sess->unregister('temporary_id');
    unset($temporary_id);
}

if (isset($_REQUEST['seminar_id'])) {
    $sess->register('temporary_id');
    $temporary_id = $_REQUEST['seminar_id'];
}

if (isset($temporary_id)) {
    $id = $temporary_id;
} else {
    $id = $SessSemName[1];
}

if (!$viewModeFilter) {
    $viewModeFilter = 'simple';
}

if ($cmd == 'changeViewMode') {
    $viewModeFilter = $_REQUEST['newFilter'];
}

// expert view enabled ?
if(!$GLOBALS["RESOURCES_ENABLE_EXPERT_SCHEDULE_VIEW"]){
    $viewModeFilter = 'simple';
}

switch ($viewModeFilter) {
    case 'expert':
        $HELP_KEYWORD="Basis.VeranstaltungenVerwaltenAblaufplanExpertenansicht";        
        include('lib/raumzeit/themen_expert.php');
        break;

    default:
        $HELP_KEYWORD="Basis.VeranstaltungenVerwaltenAblaufplan";
        include('lib/raumzeit/themen_ablaufplan.php');
        break;
        
}
