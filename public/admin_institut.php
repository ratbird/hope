<?php
# Lifter001: TEST
# Lifter002: TEST
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: TODO

// TODO by tleilax:
// Although this code has been overhauled, it is still pretty tough to read
// and should be converted into a trails app as soon as possible (probably
// not really possible until all files using admin_search_form.inc.php have
// been prepared - what a bummer!)

/*
admin_institut.php - Einrichtungs-Verwaltung von Stud.IP.
Copyright (C) 2002 Cornelis Kater <ckater@gwdg.de>, Stefan Suchi <suchi@gmx.de>

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

// Ensure only admins gain access to this page
$perm->check('admin');

if (Request::option('admin_inst_id')) {
    Request::set('cid', Request::option('admin_inst_id'));
}

include 'lib/seminar_open.php'; // initialise Stud.IP-Session

$i_view = Request::option('i_view');
## If is set 'cancel', we leave the adminstration form...
if (Request::option('cancel')) {
    unset ($i_view);
}

require_once 'lib/visual.inc.php';
require_once 'config.inc.php';
require_once 'lib/forum.inc.php';
require_once 'lib/datei.inc.php';
require_once 'lib/statusgruppe.inc.php';
require_once 'lib/functions.php';
require_once 'lib/classes/Modules.class.php';
require_once 'lib/classes/DataFieldEntry.class.php';
require_once 'lib/classes/StudipLitList.class.php';
require_once 'lib/classes/StudipLitSearch.class.php';
require_once 'lib/classes/StudipNews.class.php';
require_once 'lib/log_events.inc.php';
require_once 'lib/classes/InstituteAvatar.class.php';
require_once 'lib/classes/LockRules.class.php';
// require_once 'lib/classes/Institute.class.php';

if (get_config('RESOURCES_ENABLE')) {
    include_once($RELATIVE_PATH_RESOURCES . '/lib/DeleteResourcesUser.class.php');
}

if (get_config('EXTERN_ENABLE')) {
    require_once($RELATIVE_PATH_EXTERN . '/lib/ExternConfig.class.php');
}

//needed to build this to not break following switch strucure
$test_tasks = array('create', 'i_edit', 'i_kill', 'i_trykill');
$submitted_task = '';
foreach($test_tasks as $val) {
    if(Request::submitted($val)) {
        $submitted_task = $val;
    }
}

// Check if there was a submission
switch ($submitted_task) {

    // Create a new Institut
    case 'create':
        if (!$perm->have_perm("root") && !($perm->is_fak_admin() && get_config('INST_FAK_ADMIN_PERMS') != 'none'))  {
            PageLayout::postMessage(Messagebox::error(_('Sie haben nicht die Berechtigung, um neue Einrichtungen zu erstellen!')));
            break;
        }
        // Do we have all necessary data?
        if (!Request::quoted('Name')) {
            PageLayout::postMessage(Messagebox::error(_('Bitte geben Sie eine Bezeichnung für die Einrichtung ein!')));
            $i_view = 'new';
            break;
        }

        // Does the Institut already exist?
        // NOTE: This should be a transaction, but it is not...
        $query      = "SELECT 1 FROM Institute WHERE Name = ?";
        $parameters = array(Request::get('Name'));

        $Fakultaet = Request::option('Fakultaet');
        if ($Fakultaet) {
            $query .= " AND fakultaets_id = ?";
            $parameters[] = $Fakultaet;
        }

        $statement = DBManager::get()->prepare($query);
        $statement->execute($parameters);

        if ($statement->fetchColumn()) {
            $message = sprintf(_('Die Einrichtung "%s" existiert bereits!'), htmlReady(Request::get('Name')));
            PageLayout::postMessage(Messagebox::error($message));
            break;
        }

        // Create an id
        $i_id = md5(uniqid('Institute', true));
        if (!$Fakultaet) {
            if ($perm->have_perm('root')) {
                $Fakultaet = $i_id;
            } else {
                PageLayout::postMessage(Messagebox::error(_('Sie haben nicht die Berechtigung, neue Fakultäten zu erstellen')));
                break;
            }
        }

        $query = "INSERT INTO Institute
                      (Institut_id, Name, fakultaets_id, Strasse, Plz, url, telefon, email,
                       fax, type, lit_plugin_name, lock_rule, mkdate, chdate)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            $i_id,
            Request::get('Name'),
            $Fakultaet,
            Request::get('strasse'),
            Request::get('plz'), // Beware: Despite the name, this contains both zip code AND city name
            Request::get('home'),
            Request::get('telefon'),
            Request::get('email'),
            Request::get('fax'),
            Request::int('type'),
            Request::get('lit_plugin_name'),
            Request::option('lock_rule'),
        ));

        if ($statement->rowCount() == 0) {
            PageLayout::postMessage(Messagebox::error(_('Datenbankoperation gescheitert:') . " $query"));
            break;
        }

        log_event("INST_CREATE", $i_id, NULL, NULL, $query); // logging

        // Set the default list of modules
        $Modules = new Modules;
        $Modules->writeDefaultStatus($i_id);

        // Create default folder and discussion
        CreateTopic(_('Allgemeine Diskussionen'), ' ', _('Hier ist Raum für allgemeine Diskussionen'), 0, 0, $i_id, 0);

        $query = "INSERT INTO folder (folder_id, range_id, name, description, mkdate, chdate)
                  VALUES (?, ?, ?, ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            md5(uniqid('folder')),
            $i_id,
            _('Allgemeiner Dateiordner'),
            _('Ablage für allgemeine Ordner und Dokumente der Einrichtung'),
        ));

        $message = sprintf(_('Die Einrichtung "%s" wurde erfolgreich angelegt.'), htmlReady(Request::get('Name')));
        PageLayout::postMessage(Messagebox::success($message));

        $i_view = $i_id;

        //This will select the new institute later for navigation (=>admin_search_form.inc.php)
        $admin_inst_id = $i_id;
        openInst($i_id);
      break;

    //change institut's data
    case 'i_edit':

        if (!$perm->have_studip_perm("admin",Request::option('i_id'))){
            PageLayout::postMessage(Messagebox::error(_('Sie haben nicht die Berechtigung diese Einrichtungen zu verändern!')));
            break;
        }

        //do we have all necessary data?
        if (!(Request::quoted('Name'))) {
            PageLayout::postMessage(Messagebox::error(_('Bitte geben Sie einen Namen für die Einrichtung ein!')));
            break;
        }

        //update Institut information.
        $query = "UPDATE Institute
                  SET Name = ?, fakultaets_id = ?, Strasse = ?, Plz = ?, url = ?, telefon = ?, fax = ?,
                      email = ?, type = ?, lit_plugin_name = ?, lock_rule = ?, chdate = UNIX_TIMESTAMP()
                  WHERE Institut_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            Request::get('Name'),
            Request::option('Fakultaet'),
            Request::get('strasse'),
            Request::get('plz'),
            Request::get('home'),
            Request::get('telefon'),
            Request::get('fax'),
            Request::get('email'),
            Request::int('type'),
            Request::get('lit_plugin_name'),
            Request::option('lock_rule'),
            Request::option('i_id'),
        ));
        if ($statement->rowCount() == 0) {
            PageLayout::postMessage(Messagebox::error(_('Datenbankoperation gescheitert:') . " $query"));
            break;
        } else {
            $message = sprintf(_('Die Änderung der Einrichtung "%s" wurde erfolgreich gespeichert.'), htmlReady(Request::get('Name')));
            PageLayout::postMessage(Messagebox::success($message));
        }
        // update additional datafields
        $datafields = Request::getArray('datafields');
        if (is_array($datafields)) {
            $invalidEntries = array();
            foreach (DataFieldEntry::getDataFieldEntries(Request::option('i_id'), 'inst') as $entry) {
                if(isset($datafields[$entry->getId()])){
                    $entry->setValueFromSubmit($datafields[$entry->getId()]);
                    if ($entry->isValid())
                        $entry->store();
                    else
                        $invalidEntries[$entry->getId()] = $entry;
                }
            }
            if (count($invalidEntries)  > 0) {
                PageLayout::postMessage(Messagebox::error(_('ungültige Eingaben (s.u.) wurden nicht gespeichert')));
            } else {
                $message = sprintf(_('Die Daten der Einrichtung "%s" wurden verändert.'), htmlReady(Request::get('Name')));
                PageLayout::postMessage(Messagebox::success($message));
            }
        }
        break;

    // Delete the Institut
    case 'i_kill':
        if ( !check_ticket(Request::option('studipticket'))) {
            PageLayout::postMessage(Messagebox::error(_('Ihr Ticket ist abgelaufen. Versuchen Sie die letzte Aktion erneut.')));
            break;
        }

        if (!$perm->have_perm("root") && !($perm->is_fak_admin() && get_config('INST_FAK_ADMIN_PERMS') == 'all')) {
            PageLayout::postMessage(Messagebox::error(_('Sie haben nicht die Berechtigung Fakultäten zu löschen!')));
            break;
        }
        $i_id = Request::option('i_id');
        // Institut in use?
        $query = "SELECT 1 FROM seminare WHERE Institut_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($i_id));
        if ($statement->fetchColumn()) {
            PageLayout::postMessage(Messagebox::error(_('Diese Einrichtung kann nicht gelöscht werden, da noch Veranstaltungen an dieser Einrichtung existieren!')));
            break;
        }

        $query = "SELECT a.Institut_id, a.Name, a.Institut_id = a.fakultaets_id AS is_fak, COUNT(b.Institut_id) AS num_inst
                  FROM Institute AS a
                    LEFT JOIN Institute AS b ON (a.Institut_id=b.fakultaets_id)
                  WHERE a.Institut_id = ? AND b.Institut_id != ? AND a.Institut_id = a.fakultaets_id
                  GROUP BY a.Institut_id";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($i_id, $i_id));
        $temp = $statement->fetch(PDO::FETCH_ASSOC);

        if ($temp['num_inst']) {
            PageLayout::postMessage(Messagebox::error(_("Diese Einrichtung kann nicht gelöscht werden, da sie den Status Fakultät hat, und noch andere Einrichtungen zugeordnet sind!")));
            break;
        }

        if ($temp['is_fak'] && !$perm->have_perm("root")) {
            PageLayout::postMessage(Messagebox::error(_("Sie haben nicht die Berechtigung Fakultäten zu löschen!")));
            break;
        }

        // delete users in user_inst
        $query = "SELECT user_id FROM user_inst WHERE institut_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($i_id));
        while ($user_id = $statement->fetchColumn()) {
            log_event('INST_USER_DEL', $i_id, $user_id);
        }

        $query = "DELETE FROM user_inst WHERE Institut_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($i_id));

        if (($db_ar = $statement->rowCount()) > 0) {
            $message = sprintf(_('%s Mitarbeiter gelöscht.'), $db_ar);
            PageLayout::postMessage(Messagebox::success($message));
        }

        // delete participations in seminar_inst
        $query = "DELETE FROM seminar_inst WHERE Institut_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($i_id));
        if (($db_ar = $statement->rowCount()) > 0) {
            $message = sprintf(_('%s Beteiligungen an Veranstaltungen gelöscht'), $db_ar);
            PageLayout::postMessage(Messagebox::success($message));
        }

        // delete literatur
        $del_lit = StudipLitList::DeleteListsByRange($i_id);
        if ($del_lit) {
            $message = sprintf(_('%s Literaturlisten gelöscht.'), $del_lit['list']);
            PageLayout::postMessage(Messagebox::success($message));
        }

        // SCM löschen
        $query = "DELETE FROM scm WHERE range_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($i_id));
        if (($db_ar = $statement->rowCount()) > 0) {
            PageLayout::postMessage(Messagebox::success(_('Freie Seite der Einrichtung gelöscht.')));
        }

        // delete news-links
        StudipNews::DeleteNewsRanges($i_id);

        //delete entry in news_rss_range
        StudipNews::UnsetRssId($i_id);

        //updating range_tree
        $query = "UPDATE range_tree SET name = ?, studip_object = '', studip_object_id = '' WHERE studip_object_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(
            _('(in Stud.IP gelöscht)'),
            $i_id,
        ));

        if (($db_ar = $statement->rowCount()) > 0) {
            $message = sprintf(_('%s Bereiche im Einrichtungsbaum angepasst.'), $db_ar);
            PageLayout::postMessage(Messagebox::success($message));
        }

        // Statusgruppen entfernen
        if ($db_ar = DeleteAllStatusgruppen($i_id) > 0) {
            $message = sprintf(_('%s Funktionen/Gruppen gelöscht.'), $db_ar);
            PageLayout::postMessage(Messagebox::success($message));
        }

        //kill the datafields
        DataFieldEntry::removeAll($i_id);

        //kill all wiki-pages
        foreach (array('', '_links', '_locks') as $area) {
            $query = "DELETE FROM wiki{$area} WHERE range_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($i_id));
        }

        // kill all the ressources that are assigned to the Veranstaltung (and all the linked or subordinated stuff!)
        if (get_config('RESOURCES_ENABLE')) {
            $killAssign = new DeleteResourcesUser($i_id);
            $killAssign->delete();
        }

        // delete all configuration files for the "extern modules"
        if (get_config('EXTERN_ENABLE')) {
            $counts = ExternConfig::DeleteAllConfigurations($i_id);
            if ($counts) {
                $message = sprintf(_('%s Konfigurationsdateien für externe Seiten gelöscht.'), $counts);
                PageLayout::postMessage(Messagebox::success($message));
            }
        }

        // delete folders and discussions
        $query = "DELETE FROM px_topics WHERE Seminar_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($i_id));
        if (($db_ar = $statement->rowCount()) > 0) {
            $message = sprintf(_('%s Postings aus dem Forum der Einrichtung gelöscht.'), $db_ar);
            PageLayout::postMessage(Messagebox::success($message));
        }

        $db_ar = delete_all_documents($i_id);
        if ($db_ar > 0) {
            $message = sprintf(_('%s Dokumente gelöscht.'), $db_ar);
            PageLayout::postMessage(Messagebox::success($message));
        }

        //kill the object_user_vists for this institut

        object_kill_visits(null, $i_id);

        // Delete that Institut.
        $query = "DELETE FROM Institute WHERE Institut_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($i_id));
        if ($statement->rowCount() == 0) {
            PageLayout::postMessage(Messagebox::error(_('Datenbankoperation gescheitert:') . " $query"));
            break;
        } else {
            $message = sprintf(_('Die Einrichtung "%s" wurde gelöscht!'), htmlReady(Request::get('Name')));
            PageLayout::postMessage(Messagebox::success($message));

            // logging - put institute's name in info - it's no longer derivable from id afterwards
            log_event("INST_DEL",$i_id,NULL,Request::quoted('Name'));

            URLHelper::removeLinkParam('cid');
            header('Location: ' . URLHelper::getURL('admin_institut.php?list=true', array('deleted' => Request::get('Name'))));
            page_close();
            die;
        }

        // We deleted that intitute, so we have to unset the selection
        closeObject();
        break;
    case 'i_trykill':
        $message              = _('Sind Sie sicher, dass Sie diese Einrichtung löschen wollen?');
        $post['i_id']         = Request::option('i_id');
        $post['i_kill']       = 1;
        $post['Name']         = Request::quoted('Name');
        $post['studipticket'] = get_ticket();
        echo createQuestion($message, $post);
        break;
}

//workaround
if ($i_view == 'new') {
    closeObject();
}

PageLayout::setTitle(_('Verwaltung der Grunddaten'));
Navigation::activateItem('/admin/institute/details');

//get ID from a open Institut
if ($SessSemName[1]) {
    $i_view = $SessSemName[1];
}

$header_line = getHeaderLine($i_view);
if ($header_line) {
    PageLayout::setTitle($header_line . ' - ' . PageLayout::getTitle());
}

// We need to copy this condition from admin_search_form.inc.php to determine whether
// we need to include the header, since admin_search_form DIES and thus prevents templating
require_once 'lib/admin_search.inc.php';
if ((!$SessSemName[1] || $SessSemName['class'] == 'sem') && Request::option('list') && ($GLOBALS['view_mode'] == 'inst')) {
    include ('lib/include/html_head.inc.php'); // Output of html head
    include ('lib/include/header.php');   //hier wird der "Kopf" nachgeladen

    if ($deleted = Request::get('deleted')) {
        $message = sprintf(_('Die Einrichtung "%s" wurde gelöscht!'), htmlReady(Request::get('deleted')));
        echo '<table class="default blank"><tr><td>' . Messagebox::success($message) . '</td></tr></table>'; 
    }

    include 'lib/include/admin_search_form.inc.php';
    die; // just to be sure
}

$lockrule = LockRules::getObjectRule($i_view);
if ($lockrule->description && LockRules::CheckLockRulePermission($i_view)) {
    PageLayout::postMessage(Messagebox::info(formatLinks($lockrule->description)));
}

// A bit hackish, thus TODO
if (!$perm->have_studip_perm('admin', $i_view) && $i_view != 'new') {
    PageLayout::postMessage(Messagebox::error(_('Sie sind nicht berechtigt, auf diesen Bereich zuzugreifen')));
    echo $GLOBALS['template_factory']->render('layouts/base_without_infobox');
    page_close();
    return;
}

// Load institute data
$institute = array();
if ($i_view != 'new') {
    $query = "SELECT a.*, b.Name AS fak_name, COUNT(Seminar_id) AS number
              FROM Institute AS a
              LEFT JOIN Institute AS b ON (b.Institut_id = a.fakultaets_id)
              LEFT JOIN seminare AS c ON (a.Institut_id = c.Institut_id)
              WHERE a.Institut_id = ?
              GROUP BY a.Institut_id";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($i_view));
    $institute = $statement->fetch(PDO::FETCH_ASSOC);

    $query = "SELECT COUNT(b.Institut_id)
              FROM Institute AS a
              LEFT JOIN Institute AS b ON (a.Institut_id = b.fakultaets_id)
              WHERE a.Institut_id = ? AND b.Institut_id != ? AND a.Institut_id = a.fakultaets_id
              GROUP BY a.Institut_id";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($i_view, $i_view));
    $_num_inst = $statement->fetchColumn();
}

//add the free administrable datafields
$datafields = array();

$localEntries = DataFieldEntry::getDataFieldEntries($institute['Institut_id'], 'inst');
if ($localEntries) {
    foreach ($localEntries as $entry) {
        $value = $entry->getValue();
        $color = '#000000';
        $id = $entry->structure->getID();
        if ($invalidEntries[$id]) {
            $entry = $invalidEntries[$id];
            $color = '#ff0000';
        }
        if (!$entry->structure->accessAllowed($perm)) {
            continue;
        }
        $datafields[] = array(
            'color' => $color,
            'title' => $entry->getName(),
            'value' => ($perm->have_perm($entry->structure->getEditPerms())
                        && !LockRules::Check($institute['Institut_id'], $entry->getId()))
                     ? $entry->getHTML('datafields')
                     : $entry->getDisplayValue(),
        );
    }
}

// Prepare, populate and display template
$template = $GLOBALS['template_factory']->open('admin/institute');
$template->institute      = $institute;
$template->i_view         = $i_view;
$template->num_institutes = $_num_inst;
$template->datafields     = $datafields;

// Select correct layout and create infobox if neccessary
if ($i_view != 'new') {
    $template->set_layout('layouts/base');

    $aktionen = array();
    $aktionen[] = array(
        'icon' => 'icons/16/black/edit.png',
        'text' => sprintf('<a href="%s">%s</a>',
                          URLHelper::getLink('dispatch.php/institute/avatar/update/' . $institute['Institut_id']),
                          _('Bild ändern')),
    );
    $aktionen[] = array(
        'icon' => 'icons/16/black/trash.png',
        'text' => sprintf('<a href="%s">%s</a>',
                          URLHelper::getLink('dispatch.php/institute/avatar/delete/' . $institute['Institut_id']),
                          _('Bild löschen')),
    );
    $template->infobox = array(
        'content' => array(
            array(
                'kategorie' => _('Aktionen:'),
                'eintrag'   => $aktionen
            )
        ),
        'picture' => InstituteAvatar::getAvatar($institute['Institut_id'])->getUrl(Avatar::NORMAL),
    );
} else {
    $template->set_layout('layouts/base_without_infobox');
}

echo $template->render();

page_close();
