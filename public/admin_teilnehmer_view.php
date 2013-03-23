<?php
# Lifter001: TEST
# Lifter002: TEST
# Lifter007: TEST
# Lifter003: TEST
# Lifter010: DONE
/*
teilnehmer.php - Anzeige der Teilnehmer eines Seminares
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

page_open(array(
    'sess' => 'Seminar_Session',
    'auth' => 'Seminar_Auth',
    'perm' => 'Seminar_Perm',
    'user' => 'Seminar_User'
));
include 'lib/seminar_open.php'; //hier werden die sessions initialisiert

PageLayout::setTitle(_('Teilnehmeransicht konfigurieren'));
Navigation::activateItem('/admin/config/member_view');

$GLOBALS['perm']->check('root');

if (Request::submitted('assign')) {
    $fields = Request::getArray('fields');

    $update = DBManager::get()->prepare("REPLACE INTO teilnehmer_view (datafield_id, seminar_id) VALUES (?, ?)");
    $delete = DBManager::get()->prepare("DELETE FROM teilnehmer_view WHERE datafield_id = ? AND seminar_id = ?");

    foreach ($fields as $key => $data) {
        foreach ($data as $field => $value) {
            if ($value == 1) {
                $update->execute(array($field, $key));
            } else {
                $delete->execute(array($field, $key));
            }
        }
    }

    PageLayout::postMessage(MessageBox::success(_('Die Konfiguration wurde gespeichert.')));
    header('Location: ' . URLHelper::getURL());
    page_close();
    die;
}

// Read all active views for datafields from db
$active = array();

$query = "SELECT seminar_id, datafield_id FROM teilnehmer_view WHERE seminar_id IN (?)";
$statement = DBManager::get()->prepare($query);
$statement->execute(array(array_keys($SEM_CLASS)));
while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
    $active[ $row['seminar_id'] ][ $row['datafield_id'] ] = true;
}

// Prepare and display template
$template = $GLOBALS['template_factory']->open('admin/teilnehmer_view.php');
$template->set_layout('layouts/base_without_infobox');
$template->active = $active;
echo $template->render();
