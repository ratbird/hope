<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
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


require '../lib/bootstrap.php';

page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check("tutor");

include ("lib/seminar_open.php"); // initialise Stud.IP-Session
require_once('lib/raumzeit/raumzeit_functions.inc.php');
unQuoteAll();
$sess->register('viewModeFilter');

$id = Request::option('cid', Request::option('seminar_id'));

if (!Request::option('list')) {
    if (Request::option('seminar_id')) {
        URLHelper::bindLinkParam('seminar_id', $id);
    }
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

PageLayout::setTitle(_("Verwaltung der Themen des Ablaufplans"));

if ($perm->have_perm('admin')) {
    Navigation::activateItem('/admin/course/schedule');
} else {
    Navigation::activateItem('/course/schedule/edit');
}

//Change header_line if open object
$header_line = getHeaderLine($id);
if ($header_line)
    PageLayout::setTitle($header_line." - ".PageLayout::getTitle());

switch ($viewModeFilter) {
    case 'expert':
        PageLayout::setHelpKeyword("Basis.VeranstaltungenVerwaltenAblaufplanExpertenansicht");        
        include('lib/raumzeit/themen_expert.php');
        break;

    default:
        PageLayout::setHelpKeyword("Basis.VeranstaltungenVerwaltenAblaufplan");
        include('lib/raumzeit/themen_ablaufplan.php');
        break;
        
}
