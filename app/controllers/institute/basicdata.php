<?php
/**
 * formerly admin_institut.php - Grunddaten fuer ein Institut
 *
 * @author  Arne Schr�der <schroeder@data-quest.de>
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @author  Cornelis Kater <ckater@gwdg.de>
 * @author  Stefan Suchi <suchi@gmx.de>
 * @license GPL2 or any version
 * @since   Stud.IP 3.3
 */

require_once 'app/controllers/authenticated_controller.php';

class Institute_BasicdataController extends AuthenticatedController
{
    /**
     * common tasks for all actions
     */
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        // Ensure only admins gain access to this page
        if (!$GLOBALS['perm']->have_perm("admin")) {
            throw new AccessDeniedException();
        }

        if (get_config('RESOURCES_ENABLE')) {
            include_once $GLOBALS['RELATIVE_PATH_RESOURCES'] . '/lib/DeleteResourcesUser.class.php';
        }

        if (get_config('EXTERN_ENABLE')) {
            require_once $GLOBALS['RELATIVE_PATH_EXTERN'] . '/lib/ExternConfig.class.php';
        }
    }

    /**
     * show institute basicdata page
     *
     * @param mixed $i_id Optional institute id 
     * @throws AccessDeniedException
     */
    public function index_action($i_id = false)
    {
        PageLayout::setTitle(_('Verwaltung der Grunddaten'));
        Navigation::activateItem('/admin/institute/details');

        //get ID from an open Institut
        $i_view = $i_id ?: Request::option('i_view', $GLOBALS['SessSemName'][1]);

        if (!$i_view) {
            require_once 'lib/admin_search.inc.php';

            // This search just died a little inside, so it should be safe to
            // continue here but we nevertheless return just to be sure
            return;
        } elseif ($i_view === 'new') {
            closeObject();
        }

        //  allow only inst-admin and root to view / edit
        if ($i_view && !$GLOBALS['perm']->have_studip_perm('admin', $i_view) && $i_view !== 'new') {
            throw new AccessDeniedException();
        }

        //Change header_line if open object
        $header_line = getHeaderLine($i_view);
        if ($header_line) {
            PageLayout::setTitle($header_line . ' - ' . PageLayout::getTitle());
        }

        if (Request::get('i_trykill')) {
            $message              = _('Sind Sie sicher, dass Sie diese Einrichtung l�schen wollen?');
            $post['i_kill']       = 1;
            $post['studipticket'] = get_ticket();
            $this->question = createQuestion2($message, $post, array(), $this->url_for('institute/basicdata/delete/' . $i_view));
        }

        $lockrule = LockRules::getObjectRule($i_view);
        if ($lockrule->description && LockRules::CheckLockRulePermission($i_view)) {
            PageLayout::postMessage(MessageBox::info(formatLinks($lockrule->description)));
        }

        // Load institute data
        $institute = new Institute($i_view === 'new' ? null : $i_view);

        //add the free administrable datafields
        $datafields = array();
        $localEntries = DataFieldEntry::getDataFieldEntries($institute->id, 'inst');
        if ($localEntries) {
            $invalidEntries = $this->flash['invalid_entries'] ?: array();
            foreach ($localEntries as $entry) {
                if (!$entry->structure->accessAllowed($GLOBALS['perm'])) {
                    continue;
                }

                $color = '#000000';
                if (in_array($entry->getId(), $invalidEntries)) {
                    $color = '#ff0000';
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

        // Read faculties if neccessary
        if (count($institute->sub_institutes) === 0) {
            if ($GLOBALS['perm']->have_perm('root')) {
                $this->faculties = Institute::findBySQL('Institut_id = fakultaets_id ORDER BY Name ASC', array($i_view));
            } else {
                $temp = User::find($GLOBALS['user']->id)
                            ->institute_memberships->findBy('inst_perms', 'admin')
                            ->pluck('institute');
                $institutes = SimpleORMapCollection::createFromArray($temp);
                $faculties  = $institutes->filter(function ($institute) {
                    return $institute->is_fak;
                });
                $this->faculties = $faculties;
            }
        }

        // Indicates whether the current user is allowed to delete the institute
        $this->may_delete = $i_view !== 'new'
                         && !(count($institute->home_courses) || count($institute->sub_institutes))
                         && ($GLOBALS['perm']->have_perm('root')
                             || ($GLOBALS['perm']->is_fak_admin() && get_config('INST_FAK_ADMIN_PERMS') == 'all'));
        if (!$this->may_delete) {
            //Set infotext for disabled delete-button
            $reason_txt = _('L�schen nicht m�glich.');
            if (count($institute->home_courses) > 0) {
                $reason_txt .= ' ';
                $reason_txt .= sprintf(ngettext('Es ist eine Veranstaltung zugeordnet.',
                                                'Es sind %u Veranstaltungen zugeordnet.',
                                                count($institute->home_courses)),
                                       count($institute->home_courses));
            }
            if (count($institute->sub_institutes) > 0) {
                $reason_txt .= ' ';
                $reason_txt .= sprintf(ngettext('Es ist eine Einrichtung zugeordnet.',
                                                'Es sind %u Einrichtungen zugeordnet.',
                                                count($institute->sub_institutes)),
                                       count($institute->sub_institutes));
            }
        }
        // Indicates whether the current user is allowed to change the faculty
        $this->may_edit_faculty = $GLOBALS['perm']->is_fak_admin()
                               && !LockRules::Check($institute['Institut_id'], 'fakultaets_id')
                               && ($GLOBALS['perm']->have_studip_perm('admin', $institute['fakultaets_id']) || $i_view === 'new');

        // Prepare template
        $this->institute      = $institute;
        $this->i_view         = $i_view;
        $this->datafields     = $datafields;
        $this->reason_txt     = $reason_txt;
    }

    /**
     * Stores the changed or created institute data
     *
     * @param String $i_id Institute id or 'new' to create
     * @throws MethodNotAllowedException
     */
    public function store_action($i_id)
    {
        // We won't accept anything but a POST request
        if (!Request::isPost()) {
            throw new MethodNotAllowedException();
        }
        
        $create_institute = $i_id === 'new';
        
        $institute = new Institute($create_institute ? null : $i_id);
        $institute->name            = trim(Request::get('Name', $institute->name));
        $institute->fakultaets_id   = Request::option('Fakultaet', $institute->fakultaets_id);
        $institute->strasse         = Request::get('strasse', $institute->strasse);
        // Beware: Despite the name, this contains both zip code AND city name
        $institute->plz             = Request::get('plz', $institute->plz);
        $institute->url             = Request::get('home', $institute->url);
        $institute->telefon         = Request::get('telefon', $institute->telefon);
        $institute->email           = Request::get('email', $institute->email);
        $institute->fax             = Request::get('fax', $institute->fax);
        $institute->type            = Request::int('type', $institute->type);
        $institute->lit_plugin_name = Request::get('lit_plugin_name', $institute->lit_plugin_name);
        $institute->lock_rule       = Request::option('lock_rule', $institute->lock_rule);


        // Do we have all necessary data?
        if (!$institute->name) {
            PageLayout::postMessage(MessageBox::error(_('Bitte geben Sie eine Bezeichnung f�r die Einrichtung ein!')));
            return $this->redirect('institute/basicdata/index/' . $i_id);
        }

        if ($create_institute) {
            $institute->id = $institute->getNewId();

            // Is the user allowed to create new faculties
            if (!$institute->fakultaets_id && !$GLOBALS['perm']->have_perm('root')) {
                PageLayout::postMessage(MessageBox::error(_('Sie haben nicht die Berechtigung, neue Fakult�ten zu erstellen')));
                return $this->redirect('institute/basicdata/index/new');
            }

            // Is the user allowed to create new institutes
            if (!$GLOBALS['perm']->have_perm('root') && !($GLOBALS['perm']->is_fak_admin() && get_config('INST_FAK_ADMIN_PERMS') !== 'none'))  {
                PageLayout::postMessage(MessageBox::error(_('Sie haben nicht die Berechtigung, um neue Einrichtungen zu erstellen!')));
                return $this->redirect('institute/basicdata/index/new');
            }

            // Does an institute with the given name already exist in the given faculty?
            if ($institute->fakultaets_id && Institute::findOneBySQL('Name = ? AND fakultaets_id = ?', array($institute->name, $institute->fakultaets_id)) !== null) {
                $message = sprintf(_('Die Einrichtung "%s" existiert bereits innerhalb der angegebenen Fakult�t!'), $institute->name);
                PageLayout::postMessage(MessageBox::error($message));
                return $this->redirect('institute/basicdata/index/new');
            }

            // Does a faculty with the given name already exist
            if (!$institute->fakultaets_id && Institute::findOneBySQL('Name = ? AND fakultaets_id = Institut_id', array($institute->name)) !== null) {
                $message = sprintf(_('Die Fakult�t "%s" existiert bereits!'), $institute->name);
                PageLayout::postMessage(MessageBox::error($message));
                return $this->redirect('institute/basicdata/index/new');
            }

            // Initialize modules
            $modules = new Modules;
            $institute->modules = $modules->getDefaultBinValue('', 'inst', $institute->type);

            // Declare faculty status if neccessary
            if (!$institute->fakultaets_id) {
                $institute->fakultaets_id = $institute->getId();
            }
        } else {
            // Is the user allowed to change the institute/faculty?
            if (!$GLOBALS['perm']->have_studip_perm('admin', $institute->id)) {
                PageLayout::postMessage(MessageBox::error(_('Sie haben nicht die Berechtigung diese Einrichtung zu ver�ndern!')));
                return $this->redirect('institute/basicdata/index/' . $institute->id);
            }

            // Save datafields
            $datafields = Request::getArray('datafields');
            $invalidEntries = array();
            $datafields_stored = 0;
            foreach (DataFieldEntry::getDataFieldEntries($institute->id, 'inst') as $entry) {
                if (isset($datafields[$entry->getId()])) {
                    $valueBefore = $entry->getValue();
                    $entry->setValueFromSubmit($datafields[$entry->getId()]);
                    if ($valueBefore != $entry->getValue()) {
                        if ($entry->isValid()) {
                            $datafields_stored += 1;
                            $entry->store();
                        } else {
                            $invalidEntries[] = $entry->getId();
                        }
                    }
                }
            }

            // If any errors occured while updating the datafields, report them
            if (count($invalidEntries) > 0) {
                $this->flash['invalid_entries'] = $invalidEntries;
                PageLayout::postMessage(MessageBox::error(_('ung�ltige Eingaben (s.u.) wurden nicht gespeichert')));
            }
        }

        // Try to store the institute, report any errors
        if ($institute->isDirty() && !$institute->store()) {
            if ($institute->isNew()) {
                PageLayout::postMessage(MessageBox::error(_('Die Einrichtung konnte nicht angelegt werden.')));
            } else {
                PageLayout::postMessage(MessageBox::error(_('Die �nderungen konnten nicht gespeichert werden.')));
            }
            return $this->redirect('institute/basicdata/index/' . $i_id);
        }

        if ($create_institute) {
            // Log creation of institute
            log_event('INST_CREATE', $institute->id, null, null, ''); // logging

            // Further initialize modules (the modules class setup is in
            // no way expensive, so it can be constructed twice, don't worry)
            $modules = new Modules;
            $module_list = $modules->getLocalModules($institute->id, 'inst', $institute->modules, $institute->type);
            if (isset($module_list['documents'])) {
                create_folder(
                    _('Allgemeiner Dateiordner'),
                    _('Ablage f�r allgemeine Ordner und Dokumente der Einrichtung'),
                    $institute->id,
                    7,
                    $institute->id
                );
            }

            // Report success
            $message = sprintf(_('Die Einrichtung "%s" wurde erfolgreich angelegt.'), $institute->name);
            PageLayout::postMessage(MessageBox::success($message));

            openInst($institute->id);
        } else {
            // Report success
            $message = sprintf(_('Die �nderung der Einrichtung "%s" wurde erfolgreich gespeichert.'), htmlReady($institute->name));
            PageLayout::postMessage(MessageBox::success($message));
        }
        
        $this->redirect('institute/basicdata/index/' . $institute->id, array('cid' => $institute->id));
    }

    /**
     * Deletes an institute
     * @param String $i_id Institute id
     */
    public function delete_action($i_id)
    {
        CSRFProtection::verifyUnsafeRequest();
        
        // Missing parameter
        if (!Request::get('i_kill')) {
            return $this->redirect('institute/basicdata/index/' . $i_id);
        }

        // Invalid ticket
        if (!check_ticket(Request::option('studipticket'))) {
            PageLayout::postMessage(MessageBox::error(_('Ihr Ticket ist abgelaufen. Versuchen Sie die letzte Aktion erneut.')));
            return $this->redirect('institute/basicdata/index/' . $i_id);
        }
    
        // User may not delete this institue
        if (!$GLOBALS['perm']->have_perm('root') && !($GLOBALS['perm']->is_fak_admin() && get_config('INST_FAK_ADMIN_PERMS') === 'all')) {
            PageLayout::postMessage(MessageBox::error(_('Sie haben nicht die Berechtigung Fakult�ten zu l�schen!')));
            return $this->redirect('institute/basicdata/index/' . $i_id);
        }

        $institute = Institute::find($i_id);
        if ($institute === null) {
            throw new Exception('Invalid institute id');
        }

        // Institut in use?
        if (count($institute->home_courses)) {
            PageLayout::postMessage(MessageBox::error(_('Diese Einrichtung kann nicht gel�scht werden, da noch Veranstaltungen an dieser Einrichtung existieren!')));
            return $this->redirect('institute/basicdata/index/' . $i_id);
        }

        // Institute has sub institutes?
        if (count($institute->sub_institutes)) {
            PageLayout::postMessage(MessageBox::error(_('Diese Einrichtung kann nicht gel�scht werden, da sie den Status Fakult�t hat und noch andere Einrichtungen zugeordnet sind!')));
            return $this->redirect('institute/basicdata/index/' . $i_id);
        }

        // Is the user allowed to delete faculties?
        if ($institute->is_fak && !$GLOBALS['perm']->have_perm('root')) {
            PageLayout::postMessage(MessageBox::error(_('Sie haben nicht die Berechtigung Fakult�ten zu l�schen!')));
            return $this->redirect('institute/basicdata/index/' . $i_id);
        }

        // Save users, name and number of courses
        $user_ids  = $institute->members->pluck('user_id');
        $i_name    = $institute->name;
        $i_courses = count($institute->courses);

        // Delete that institute
        if (!$institute->delete()) {
            PageLayout::postMessage(MessageBox::error(_('Die Einrichtung konnte nicht gel�scht werden.')));
        } else {
            $details = array();
            
            // logging - put institute's name in info - it's no longer derivable from id afterwards
            log_event('INST_DEL', $i_id, NULL, $i_name);
        
            // set a suitable default institute for each user
            foreach ($user_ids as $user_id) {
                log_event('INST_USER_DEL', $i_id, $user_id);
                checkExternDefaultForUser($user_id);
            }
            if (count($user_ids)) {
                $details[] = sprintf(_('%u Mitarbeiter gel�scht.'), count($user_ids));
            }

            // Report number of formerly associated courses
            if ($i_courses) {
                $details[] = sprintf(_('%u Beteiligungen an Veranstaltungen gel�scht'), $i_courses);
            }

            // delete literatur
            $del_lit = StudipLitList::DeleteListsByRange($i_id);
            if ($del_lit) {
                $details[] = sprintf(_('%u Literaturlisten gel�scht.'), $del_lit['list']);
            }

            // delete news-links
            StudipNews::DeleteNewsRanges($i_id);

            //delete entry in news_rss_range
            StudipNews::UnsetRssId($i_id);

            //updating range_tree
            $query = "UPDATE range_tree SET name = ?, studip_object = '', studip_object_id = '' WHERE studip_object_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                _('(in Stud.IP gel�scht)'),
                $i_id,
            ));
            if (($db_ar = $statement->rowCount()) > 0) {
                $details[] = sprintf(_('%u Bereiche im Einrichtungsbaum angepasst.'), $db_ar);
            }

            // Statusgruppen entfernen
            if ($db_ar = DeleteAllStatusgruppen($i_id) > 0) {
                $details[] = sprintf(_('%s Funktionen/Gruppen gel�scht.'), $db_ar);
            }

            //kill the datafields
            DataFieldEntry::removeAll($i_id);

            //kill all wiki-pages
            $removed_wiki_pages = 0;
            foreach (array('', '_links', '_locks') as $area) {
                $query = "DELETE FROM wiki{$area} WHERE range_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($i_id));
                $removed_wiki_pages += $statement->rowCount();
            }
            if ($removed_wiki_pages > 0) {
                $details[] = sprintf(_('%u Wikiseiten gel�scht.'));
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
                    $details[] = sprintf(_('%u Konfigurationsdateien f�r externe Seiten gel�scht.'), $counts);
                }
            }

            // delete all contents in forum-modules
            foreach (PluginEngine::getPlugins('ForumModule') as $plugin) {
                $plugin->deleteContents($i_id);  // delete content irrespective of plugin-activation in the seminar
                if ($plugin->isActivated($i_id)) {   // only show a message, if the plugin is activated, to not confuse the user
                    $details[] = sprintf(_('Eintr�ge in %s gel�scht.'), $plugin->getPluginName());
                }
            }

            // Delete assigned documents
            $db_ar = delete_all_documents($i_id);
            if ($db_ar > 0) {
                $details[] = sprintf(_('%u Dokumente gel�scht.'), $db_ar);
            }

            //kill the object_user_vists for this institut
            object_kill_visits(null, $i_id);

            // Report success with details
            $message = sprintf(_('Die Einrichtung "%s" wurde gel�scht!'), $i_name);
            PageLayout::postMessage(MessageBox::success($message, $details));
        }

        $this->redirect('institute/basicdata/index?cid=');
    }
}