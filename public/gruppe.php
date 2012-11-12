<?php
# Lifter002: TEST
# Lifter003: TODO - DBView
# Lifter007: TODO
# Lifter010: TODO - not applicable
/*
gruppe.php - Zuordnung der abonnierten Seminare zu Gruppen
Copyright (C) 2000 Ralf Stockmann <rstockm@gwdg.de>, Stefan Suchi <suchi@gmx.de>

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

unregister_globals();
page_open(array(
    'sess' => 'Seminar_Session',
    'auth' => 'Seminar_Auth',
    'perm' => 'Seminar_Perm',
    'user' => 'Seminar_User'
));
$perm->check('user');

include 'lib/seminar_open.php'; // initialise Stud.IP-Session

if (!$auth->is_authenticated() || $user->id == 'nobody' || $perm->have_perm('admin')) {
    throw new AccessDeniedException();
}

// -- here you have to put initialisations for the current page
require_once 'lib/functions.php';
require_once 'lib/visual.inc.php';
require_once 'lib/meine_seminare_func.inc.php';

PageLayout::setTitle(_('Meine Veranstaltungen') . ' - ' . _('Gruppenzuordnung'));
PageLayout::setHelpKeyword('Basis.VeranstaltungenOrdnen');
Navigation::activateItem('/browse/my_courses/group');



$forced_grouping     = get_config('MY_COURSES_FORCE_GROUPING');
$no_grouping_allowed = ($forced_grouping == 'not_grouped' || !in_array($forced_grouping, getValidGroupingFields()));

$group_field = $user->cfg->MY_COURSES_GROUPING;
$_my_sem_open = $user->cfg->MY_COURSES_OPEN_GROUPS;
$groups = array();
$add_fields = '';
$add_query = '';

if (Request::option('open_my_sem')) {
    $_my_sem_open[Request::option('open_my_sem')] = true;
    $user->cfg->store('MY_COURSES_OPEN_GROUPS', $_my_sem_open);
}
if (Request::option('close_my_sem')) {
    unset($_my_sem_open[Request::option('close_my_sem')]);
    $user->cfg->store('MY_COURSES_OPEN_GROUPS', $_my_sem_open);
}

if ($group_field == 'sem_tree_id'){
    $add_fields = ', sem_tree_id';
    $add_query = "LEFT JOIN seminar_sem_tree sst ON (sst.seminar_id=seminare.Seminar_id)";
} else if ($group_field == 'dozent_id'){
    $add_fields = ', su1.user_id as dozent_id';
    $add_query = "LEFT JOIN seminar_user as su1 ON (su1.seminar_id=seminare.Seminar_id AND su1.status='dozent')";
}

$dbv = new DbView();

$query = "SELECT seminare.Seminar_id, seminare.VeranstaltungsNummer AS sem_nr, seminare.Name,
                 seminare.status AS sem_status, seminar_user.gruppe, seminare.visible,
                 {$dbv->sem_number_sql} AS sem_number,
                 {$dbv->sem_number_end_sql} AS sem_number_end {$add_fields}
          FROM seminar_user
          LEFT JOIN seminare USING (Seminar_id)
          {$add_query}
          WHERE seminar_user.user_id = ?";
if (get_config('DEPUTIES_ENABLE')) {
    $query .= " UNION "
            . getMyDeputySeminarsQuery('gruppe', $dbv->sem_number_sql, $dbv->sem_number_end_sql, $add_fields, $add_query);
}
$query .= " ORDER BY sem_nr ASC";

$statement = DBManager::get()->prepare($query);
$statement->execute(array($user->id));
while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
    $my_sem[$row['Seminar_id']] = array(
        'obj_type'       => 'sem',
        'name'           => $row['Name'],
        'visible'        => $row['visible'],
        'gruppe'         => $row['gruppe'],
        'sem_status'     => $row['sem_status'],
        'sem_number'     => $row['sem_number'],
        'sem_number_end' => $row['sem_number_end'],
    );
    if ($group_field) {
        fill_groups($groups, $row[$group_field], array(
            'seminar_id' => $row['Seminar_id'],
            'name'       => $row['Name'],
            'gruppe'     => $row['gruppe']
        ));
    }
}

if ($group_field == 'sem_number') {
    correct_group_sem_number($groups, $my_sem);
} else {
    add_sem_name($my_sem);
}

sort_groups($group_field, $groups);

$template = $GLOBALS['template_factory']->open('meine_seminare/gruppe');
$template->set_layout('layouts/base_without_infobox');

$template->no_grouping_allowed = $no_grouping_allowed;
$template->groups              = $groups;
$template->group_names         = get_group_names($group_field, $groups);
$template->group_field         = $group_field;
$template->my_sem              = $my_sem;
$template->_my_sem_open        = $_my_sem_open;

echo $template->render();

page_close();
