<?php
# Lifter010: TODO

/*
 * Copyright (C) 2002 Cornelis Kater <ckater@gwdg.de>, Stefan Suchi <suchi@gmx.de>
 * Copyright (C) 2015 - Arne Schröder <schroeder@data-quest.de>
 *
 * formerly admin_institut.php - Grunddaten fuer ein Institut
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'app/controllers/authenticated_controller.php';

class Institute_BasicdataController extends AuthenticatedController
{

    /**
     * show institute basicdata page
     *
     * @return void
     */
    function index_action()
    {
        // Ensure only admins gain access to this page
        if (!$GLOBALS['perm']->have_perm("admin")) {
                throw new AccessDeniedException(_('Keine Berechtigung in diesem Bereich.'));
        }

        PageLayout::setTitle(_('Verwaltung der Grunddaten'));
        Navigation::activateItem('/admin/institute/details');

        //get ID from an open Institut
        if (Request::option('i_view')) {
            $i_view = Request::option('i_view');
        } elseif ($GLOBALS['SessSemName'][1]) {
            $i_view = $GLOBALS['SessSemName'][1];
        }

        //  allow only inst-admin and root to view / edit
        if ($i_view && !$GLOBALS['perm']->have_studip_perm('admin', $i_view) && $i_view != 'new') {
            throw new AccessDeniedException(_('Sie sind nicht berechtigt, auf diesen Bereich zuzugreifen.'));
        }

        // If is set 'cancel', we leave the adminstration form...
        if (Request::option('cancel')) {
            unset ($i_view);
        }

        //Change header_line if open object
        $header_line = getHeaderLine($i_view);
        if ($header_line) {
            PageLayout::setTitle($header_line . ' - ' . PageLayout::getTitle());
        }

        if (get_config('RESOURCES_ENABLE')) {
            include_once($GLOBALS['RELATIVE_PATH_RESOURCES'] . '/lib/DeleteResourcesUser.class.php');
        }

        if (get_config('EXTERN_ENABLE')) {
            require_once($GLOBALS['RELATIVE_PATH_EXTERN'] . '/lib/ExternConfig.class.php');
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
                if (!$GLOBALS['perm']->have_perm("root") && !($GLOBALS['perm']->is_fak_admin() && get_config('INST_FAK_ADMIN_PERMS') != 'none'))  {
                    PageLayout::postMessage(MessageBox::error(_('Sie haben nicht die Berechtigung, um neue Einrichtungen zu erstellen!')));
                    break;
                }
                // Do we have all necessary data?
                if (!Request::get('Name')) {
                    PageLayout::postMessage(MessageBox::error(_('Bitte geben Sie eine Bezeichnung für die Einrichtung ein!')));
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
                    PageLayout::postMessage(MessageBox::error($message));
                    break;
                }

                if (!$Fakultaet && !$GLOBALS['perm']->have_perm('root')) {
                    PageLayout::postMessage(MessageBox::error(_('Sie haben nicht die Berechtigung, neue Fakultäten zu erstellen')));
                    break;
                }

                $data = array('name' => Request::get('Name'),
                              'fakultaets_id' => $Fakultaet,
                              'strasse' => Request::get('strasse'),
                              'plz' => Request::get('plz'), // Beware: Despite the name, this contains both zip code AND city name
                              'url' => Request::get('home'),
                              'telefon' => Request::get('telefon'),
                              'email' => Request::get('email'),
                              'fax' => Request::get('fax'),
                              'type' => Request::int('type'),
                              'lit_plugin_name' => Request::get('lit_plugin_name'),
                              'lock_rule' => Request::option('lock_rule'));

                // Set the default list of modules
                $Modules = new Modules;
                $data['modules'] = $Modules->getDefaultBinValue('', 'inst', $data['type']);

                $institute = new Institute();
                $institute->setData($data, true);

                if (!$institute->store()) {
                    PageLayout::postMessage(MessageBox::error(_('Die Einrichtung konnte nicht angelegt werden.')));
                    break;
                }

                $i_id = $institute->getId();

                if (!$Fakultaet) {
                    $institute->setValue('fakultaets_id', $i_id);
                    if (!$institute->store()) {
                        PageLayout::postMessage(MessageBox::error(_('Die Einrichtung konnte nicht angelegt werden.')));
                        break;
                    }
                }

                log_event("INST_CREATE", $i_id, NULL, NULL, ''); // logging

                $module_list = $Modules->getLocalModules($i_id, 'inst', $institute->modules, $institute->type);

                if (isset($module_list['documents'])) {
                    create_folder(
                        _('Allgemeiner Dateiordner'),
                        _('Ablage für allgemeine Ordner und Dokumente der Einrichtung'),
                        $i_id,
                        7,
                        $i_id);
                }

                $message = sprintf(_('Die Einrichtung "%s" wurde erfolgreich angelegt.'), htmlReady(Request::get('Name')));
                PageLayout::postMessage(MessageBox::success($message));

                $i_view = $i_id;

                openInst($i_id);
                break;

            //change institut's data
            case 'i_edit':

                if (!$GLOBALS['perm']->have_studip_perm("admin",Request::option('i_id'))){
                    PageLayout::postMessage(MessageBox::error(_('Sie haben nicht die Berechtigung diese Einrichtungen zu verändern!')));
                    break;
                }

                //do we have all necessary data?
                if (Request::get('Name') !== null && !strlen(Request::get('Name'))) {
                    PageLayout::postMessage(MessageBox::error(_('Bitte geben Sie einen Namen für die Einrichtung ein!')));
                    break;
                }

                $data = array('name' => Request::get('Name'),
                              'fakultaets_id' => Request::option('Fakultaet'),
                              'strasse' => Request::get('strasse'),
                              'plz' => Request::get('plz'), // Beware: Despite the name, this contains both zip code AND city name
                              'url' => Request::get('home'),
                              'telefon' => Request::get('telefon'),
                              'email' => Request::get('email'),
                              'fax' => Request::get('fax'),
                              'type' => Request::int('type'),
                              'lit_plugin_name' => Request::get('lit_plugin_name'),
                              'lock_rule' => Request::option('lock_rule'));
                $data = array_filter($data, function ($v) {return $v !== null;});
                //update Institut information.
                $institute = Institute::find(Request::option('i_id'));
                if ($institute) {
                    $institute->setData($data, false);
                    $ok = $institute->store();
                    if ($ok === false) {
                        PageLayout::postMessage(MessageBox::error(_('Die Änderungen konnten nicht gespeichert werden.')));
                        break;
                    } elseif ($ok) {
                        $message = sprintf(_('Die Änderung der Einrichtung "%s" wurde erfolgreich gespeichert.'), htmlReady($institute->name));
                        PageLayout::postMessage(MessageBox::success($message));
                    }

                    // update additional datafields
                    $datafields = Request::getArray('datafields');
                    if (is_array($datafields)) {
                        $invalidEntries = array();
                        foreach (DataFieldEntry::getDataFieldEntries(Request::option('i_id'), 'inst') as $entry) {
                            if(isset($datafields[$entry->getId()])){
                                $valueBefore = $entry->getValue();
                                $entry->setValueFromSubmit($datafields[$entry->getId()]);
                                if ($valueBefore != $entry->getValue()) {
                                    if ($entry->isValid()) {
                                        $df_stored++;
                                        $entry->store();
                                    } else {
                                        $invalidEntries[$entry->getId()] = $entry;
                                    }
                                }
                            }
                        }
                        if (count($invalidEntries)  > 0) {
                            PageLayout::postMessage(MessageBox::error(_('ungültige Eingaben (s.u.) wurden nicht gespeichert')));
                        } elseif ($df_stored) {
                            $message = sprintf(_('Die Daten der Einrichtung "%s" wurden verändert.'), htmlReady($institute->name));
                            PageLayout::postMessage(MessageBox::success($message));
                        }
                    }
                }
                break;

            // Delete the Institut
            case 'i_kill':
                if ( !check_ticket(Request::option('studipticket'))) {
                    PageLayout::postMessage(MessageBox::error(_('Ihr Ticket ist abgelaufen. Versuchen Sie die letzte Aktion erneut.')));
                    break;
                }

                if (!$GLOBALS['perm']->have_perm("root") && !($GLOBALS['perm']->is_fak_admin() && get_config('INST_FAK_ADMIN_PERMS') == 'all')) {
                    PageLayout::postMessage(MessageBox::error(_('Sie haben nicht die Berechtigung Fakultäten zu löschen!')));
                    break;
                }
                $i_id = Request::option('i_id');
                // Institut in use?
                $query = "SELECT 1 FROM seminare WHERE Institut_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($i_id));
                if ($statement->fetchColumn()) {
                    PageLayout::postMessage(MessageBox::error(_('Diese Einrichtung kann nicht gelöscht werden, da noch Veranstaltungen an dieser Einrichtung existieren!')));
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
                    PageLayout::postMessage(MessageBox::error(_("Diese Einrichtung kann nicht gelöscht werden, da sie den Status Fakultät hat, und noch andere Einrichtungen zugeordnet sind!")));
                    break;
                }

                if ($temp['is_fak'] && !$GLOBALS['perm']->have_perm("root")) {
                    PageLayout::postMessage(MessageBox::error(_("Sie haben nicht die Berechtigung Fakultäten zu löschen!")));
                    break;
                }

                // delete users in user_inst
                $user_ids = array();
                $query = "SELECT user_id FROM user_inst WHERE institut_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($i_id));
                while ($user_id = $statement->fetchColumn()) {
                    $user_ids[] = $user_id;
                    log_event('INST_USER_DEL', $i_id, $user_id);
                }

                $query = "DELETE FROM user_inst WHERE Institut_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($i_id));

                if (($db_ar = $statement->rowCount()) > 0) {
                    $message = sprintf(_('%s Mitarbeiter gelöscht.'), $db_ar);
                    PageLayout::postMessage(MessageBox::success($message));
                }

                // set a suitable default institute for each user
                foreach ($user_ids as $user_id) {
                    checkExternDefaultForUser($user_id);
                }

                // delete participations in seminar_inst
                $query = "DELETE FROM seminar_inst WHERE Institut_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($i_id));
                if (($db_ar = $statement->rowCount()) > 0) {
                    $message = sprintf(_('%s Beteiligungen an Veranstaltungen gelöscht'), $db_ar);
                    PageLayout::postMessage(MessageBox::success($message));
                }

                // delete literatur
                $del_lit = StudipLitList::DeleteListsByRange($i_id);
                if ($del_lit) {
                    $message = sprintf(_('%s Literaturlisten gelöscht.'), $del_lit['list']);
                    PageLayout::postMessage(MessageBox::success($message));
                }

                // SCM löschen
                $query = "DELETE FROM scm WHERE range_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($i_id));
                if (($db_ar = $statement->rowCount()) > 0) {
                    PageLayout::postMessage(MessageBox::success(_('Freie Seite der Einrichtung gelöscht.')));
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
                    PageLayout::postMessage(MessageBox::success($message));
                }

                // Statusgruppen entfernen
                if ($db_ar = DeleteAllStatusgruppen($i_id) > 0) {
                    $message = sprintf(_('%s Funktionen/Gruppen gelöscht.'), $db_ar);
                    PageLayout::postMessage(MessageBox::success($message));
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
                        PageLayout::postMessage(MessageBox::success($message));
                    }
                }

                // delete all contents in forum-modules
                foreach (PluginEngine::getPlugins('ForumModule') as $plugin) {
                    $plugin->deleteContents($i_id);  // delete content irrespective of plugin-activation in the seminar

                    if ($plugin->isActivated($i_id)) {   // only show a message, if the plugin is activated, to not confuse the user
                        $message = sprintf(_('Einträge in %s gelöscht.'), $plugin->getPluginName());
                        PageLayout::postMessage(MessageBox::success($message));
                    }
                }

                $db_ar = delete_all_documents($i_id);
                if ($db_ar > 0) {
                    $message = sprintf(_('%s Dokumente gelöscht.'), $db_ar);
                    PageLayout::postMessage(MessageBox::success($message));
                }

                //kill the object_user_vists for this institut
                object_kill_visits(null, $i_id);

                // Delete that Institut.
                $query = "DELETE FROM Institute WHERE Institut_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($i_id));
                if ($statement->rowCount() == 0) {
                    PageLayout::postMessage(MessageBox::error(_('Datenbankoperation gescheitert:') . " $query"));
                    break;
                } else {
                    $message = sprintf(_('Die Einrichtung "%s" wurde gelöscht!'), htmlReady(Request::get('Name')));
                    PageLayout::postMessage(MessageBox::success($message));

                    // logging - put institute's name in info - it's no longer derivable from id afterwards
                    log_event("INST_DEL",$i_id,NULL, Request::quoted('Name'));

                    $this->redirect($this->url_for('institute/basicdata/index', array('cid' => '')));
                    return;
                }

                // We deleted that intitute, so we have to unset the selection
                closeObject();
                break;
            case 'i_trykill':
                $message              = _('Sind Sie sicher, dass Sie diese Einrichtung löschen wollen?');
                $post['i_id']         = Request::option('i_id');
                $post['i_kill']       = 1;
                $post['Name']         = Request::get('Name');
                $post['studipticket'] = get_ticket();
                echo createQuestion($message, $post);
                break;
        }

        if ($i_view == 'new') {
            closeObject();
        } else {
            require_once 'lib/admin_search.inc.php';
        }

        $lockrule = LockRules::getObjectRule($i_view);
        if ($lockrule->description && LockRules::CheckLockRulePermission($i_view)) {
            PageLayout::postMessage(MessageBox::info(formatLinks($lockrule->description)));
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

        // Read faculties if neccessary
        if (!$_num_inst) {
            if ($GLOBALS['perm']->have_perm('root')) {
                $query = "SELECT Institut_id, Name
                          FROM Institute
                          WHERE Institut_id = fakultaets_id AND fakultaets_id != ?
                          ORDER BY Name";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($i_view ?: ''));
            } else {
                $query = "SELECT a.Institut_id, Name
                          FROM user_inst AS a
                          LEFT JOIN Institute USING (Institut_id)
                          WHERE user_id = ? AND inst_perms = 'admin'
                          AND a.Institut_id=fakultaets_id
                          AND fakultaets_id <> ?
                          ORDER BY Name";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($GLOBALS['user']->id, $i_view ?: ''));
            }
            $faculties = $statement->fetchGrouped(PDO::FETCH_COLUMN);
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
                if (!$entry->structure->accessAllowed($GLOBALS['perm'])) {
                    continue;
                }
                $datafields[] = array(
                    'color' => $color,
                    'title' => $entry->getName(),
                    'value' => ($GLOBALS['perm']->have_perm($entry->structure->getEditPerms())
                                && !LockRules::Check($institute['Institut_id'], $entry->getId()))
                             ? $entry->getHTML('datafields')
                             : $entry->getDisplayValue(),
                );
            }
        }

        //Set infotext for disabled delete-button
        $reason_txt = _('Löschen nicht möglich.');
        $reason_txt .= $institute['number'] > 0 ?
                ' ' . sprintf(ngettext(_('Es ist eine Veranstaltung zugeordnet.'), _('Es sind %u Veranstaltungen zugeordnet.'),
                        $institute['number']), $institute['number']): '';
        $reason_txt .= $_num_inst > 0 ?
                ' ' . sprintf(ngettext(_('Es ist eine Einrichtung zugeordnet.'), _('Es sind %u Einrichtungen zugeordnet.'),
                        $_num_inst), $_num_inst): '';

        // Prepare template
        $this->institute      = $institute;
        $this->i_view         = $i_view;
        $this->num_institutes = $_num_inst;
        $this->datafields     = $datafields;
        $this->faculties      = $faculties;
        $this->reason_txt = $reason_txt;
        // Indicates whether the current user is allowed to delete the institute
        $this->may_delete = $i_view != 'new' && !($institute['number'] || $num_institutes)
                    && ($GLOBALS['perm']->have_perm('root')
                        || ($GLOBALS['perm']->is_fak_admin() && get_config('INST_FAK_ADMIN_PERMS') == 'all'));
        // Indicates whether the current user is allowed to change the faculty
        $this->may_edit_faculty = $GLOBALS['perm']->is_fak_admin()
                          && ! LockRules::Check($institute['Institut_id'], 'fakultaets_id')
                          && ($GLOBALS['perm']->have_studip_perm('admin', $institute['fakultaets_id']) || $i_view == 'new');
    }
}