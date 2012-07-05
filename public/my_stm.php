<?php
# Lifter002: TEST
# Lifter003: TEST
# Lifter007: TEST
# Lifter010: DONE - not applicable
/**
* my_stm.php
*
* overview for Studienmodule
*
* @author       André Noack <noack@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup  views
* @module       my_stm.php
* @package      studip_core
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// my_stm.php
// Anzeigeseite fuer
// Copyright (C) 2006 André Noack <noack@data-quest.de>, Suchi & Berg GmbH <info@data-quest.de>
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+

require '../lib/bootstrap.php';

page_open(array(
    'sess' => 'Seminar_Session',
    'auth' => 'Seminar_Auth',
    'perm' => 'Seminar_Perm',
    'user' => 'Seminar_User'
));
$perm->check('dozent');

include 'lib/seminar_open.php'; // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page
require_once 'lib/visual.inc.php';        // htmlReady fuer die Veranstaltungsnamen
require_once 'lib/classes/StudipStmInstance.class.php';

// we are defintely not in an lexture or institute
closeObject();
$_SESSION['links_admin_data'] = ''; //Auch im Adminbereich gesetzte Veranstaltungen muessen geloescht werden.

PageLayout::setTitle(_('Meine Studienmodule'));
if (!$perm->have_perm('root')) {
    Navigation::activateItem('/browse/my_courses/modules');
}

$num_my_mod = 0;
$my_stm = array();
$all_sems = array();
$stms = array();

// Obtain all study modules and their associated seminars
$query = "SELECT su.seminar_id, IF(s.visible = 0, CONCAT(s.Name, ?), s.Name) AS Name,
                 stm_instance_id,
                 sd1.name AS startsem, IF(duration_time = -1, ?, sd2.name) AS endsem
          FROM seminar_user AS su
          INNER JOIN seminare AS s ON (su.seminar_id = s.Seminar_id)
          INNER JOIN stm_instances_elements AS sie ON (su.seminar_id = sem_id)
          LEFT JOIN semester_data AS sd1 ON (start_time BETWEEN sd1.beginn AND sd1.ende)
          LEFT JOIN semester_data AS sd2 ON (start_time + duration_time BETWEEN sd2.beginn AND sd2.ende)
          WHERE su.user_id = ? AND su.status = 'dozent'";
$statement = DBManager::get()->prepare($query);
$statement->execute(array(_('(versteckt)'), _('unbegrenzt'), $user->id));
while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
    $my_stm[$row['stm_instance_id']][] = $row;
    if (!in_array($row['seminar_id'], $all_sems)) {
        $all_sems[] = $row['seminar_id'];
    }

    $stm = new StudipStmInstance($row['stm_instance_id']);
    if ($stm->getValue('responsible') == $GLOBALS['user']->id) {
        $num_my_mod += 1;
    }
    $stms[$row['stm_instance_id']] = array(
        'id'          => $stm->getId(),
        'displayname' => $stm->getValue('displayname'),
        'responsible' => $stm->getValue('responsible'),
        'complete'    => $stm->getValue('complete'),
    );
}

// Obtain all remaining study modules that have no associated seminars
$query = "SELECT stm_instance_id FROM stm_instances WHERE responsible = ?";
$statement = DBManager::get()->prepare($query);
$statement->execute(array($user->id));
while ($id = $statement->fetchColumn()) {
    if (!isset($my_stm[$id])) {
        $my_stm[$id] = array();
    }
}

$template = $GLOBALS['template_factory']->open('meine_seminare/my_stm');
$template->set_layout('layouts/base');

$template->my_stm     = $my_stm;
$template->stms       = $stms;
$template->num_my_mod = $num_my_mod;
$template->all_sems   = count($all_sems);

echo $template->render();

// Save data back to database.
page_close();
