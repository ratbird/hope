<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO

/*
adminarea_start.php - Dummy zum Einstieg in Adminbereich
Copyright (C) 2001 Cornelis Kater <ckater@gwdg.de>

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

if (Request::option('select_sem_id')) {
    Request::set('cid', Request::option('select_sem_id'));
}

include ('lib/seminar_open.php'); // initialise Stud.IP-Session
require_once 'lib/admin_search.inc.php';

// -- here you have to put initialisations for the current page

PageLayout::setHelpKeyword("Basis.VeranstaltungenVerwalten");
PageLayout::setTitle(_('Verwaltung von Veranstaltungen'));
if (Navigation::hasItem('/admin/course/adminarea_start')) {
    Navigation::activateItem('/admin/course/adminarea_start');
} else {
    Navigation::activateItem('/admin/course');
}

// Start of Output
include 'lib/include/admin_search_form.inc.php';

require_once 'lib/visual.inc.php';

$template = $GLOBALS['template_factory']->open('adminarea-start.php');
$template->set_layout('layouts/base.php');
$template->display = isset($GLOBALS['SessSemName'][1]);
$template->name    = $GLOBALS['SessSemName'][0];
$template->refered_from_seminar = $_SESSION['links_admin_data']['referred_from'] === 'sem';
echo $template->render();

page_close();
