<?php
# Lifter001: TEST
# Lifter002: TEST
# Lifter003: TEST
# Lifter007: TODO
# Lifter010: DONE - not applicable
/**
* my_archiv.php
*
* overview for achived Veranstaltungen
*
*
* @author       Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
* @access       public
* @modulegroup  views
* @module       my_archiv.php
* @package      studip_core
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// my_archiv.php
// Anzeigeseite fuer persoenliche, archivierte Veranstaltungen
// Copyright (C) 2003 Cornelis Kater <ckater@gwdg.de>, Suchi & Berg GmbH <info@data-quest.de>
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

unregister_globals();
page_open(array(
    'sess' => 'Seminar_Session',
    'auth' => 'Seminar_Auth',
    'perm' => 'Seminar_Perm',
    'user' => 'Seminar_User',
));

$perm->check('user');

include 'lib/seminar_open.php'; // initialise Stud.IP-Session

// -- here you have to put initialisations for the current page
require_once 'lib/visual.inc.php';        // htmlReady fuer die Veranstaltungsnamen
require_once 'lib/dates.inc.php';     // Semester-Namen fuer Admins
require_once 'lib/datei.inc.php';

// we are defintely not in an lecture or institute
closeObject();

$_SESSION['links_admin_data'] = '';    //Auch im Adminbereich gesetzte Veranstaltungen muessen geloescht werden.

PageLayout::setHelpKeyword('Basis.MeinArchiv');
PageLayout::setTitle(_('Meine archivierten Veranstaltungen'));

if (!$perm->have_perm('root')) {
    Navigation::activateItem('/browse/my_courses/archive');
}

// add skip link
SkipLinks::addIndex(_('Hauptinhalt'), 'layout_content', 100);

$sortby = Request::option('sortby', 'name');

$query = "SELECT semester, name, seminar_id, status, archiv_file_id,
                 LENGTH(forumdump) > 0 AS forumdump, # Test for existence
                 LENGTH(wikidump) > 0 AS wikidump    # Test for existence
          FROM archiv_user
          LEFT JOIN archiv USING (seminar_id)
          WHERE user_id = :user_id
          GROUP BY seminar_id
          ORDER BY start_time DESC, :sortby";
$statement = DBManager::get()->prepare($query);
$statement->bindValue(':user_id', $user->id);
$statement->bindValue(':sortby', $sortby, StudipPDO::PARAM_COLUMN);
$statement->execute();
$seminars = $statement->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC); // Groups by semester

// Berechnung der uebrigen Seminare
$count = DBManager::get()->query("SELECT COUNT(*) FROM archiv")->fetchColumn();
$anzahltext = sprintf(_('Es befinden sich zur Zeit %s Veranstaltungen im Archiv.'), $count);

$infobox = array(
    'picture' => 'infobox/archiv.jpg',
    'content' => array(
        array(
            'kategorie' => _('Information:'),
            'eintrag'   => array(
                array(
                    'icon' => 'icons/16/black/info.png',
                    'text' => $anzahltext
                ),
            ),
        ),
        array(
            'kategorie' => _('Aktionen:'),
            'eintrag'   => array(
                array(
                    'icon' => 'icons/16/black/search.png',
                    'text' => sprintf(_('Um Informationen über andere archivierte Veranstaltungen '
                                       .'anzuzeigen nutzen Sie die %sSuche im Archiv%s'),
                                      '<a href="'. URLHelper::getLink('archiv.php') .'">',
                                      '</a>')
                )
            )
        )
    )
);

$template = $GLOBALS['template_factory']->open('archiv/my_archiv');
$template->set_layout('layouts/base');

$template->infobox = $infobox;
$template->seminars = $seminars;

// would use Trails_Flash here, but this is not trails
// TODO: This should be removed as soon as archive_assi uses PageLayout::postMessage() 
if (isset($_SESSION['archive_message'])) {
    $template->meldung = $_SESSION['archive_message'];
    unset($_SESSION['archive_message']);
}

echo $template->render();

page_close();
