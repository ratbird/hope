<?php
# Lifter010: TODO
/*
 * studygroup.php - contains Course_StudygroupController
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Andr� Kla�en <andre.klassen@elan-ev.de>
 * @copyright   2009-2010 ELAN e.V.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     studygroup
 * @since       1.10
 */

require_once 'app/controllers/authenticated_controller.php';
require_once 'lib/classes/Seminar.class.php';
require_once 'app/models/studygroup.php';
require_once 'lib/messaging.inc.php';
require_once 'lib/user_visible.inc.php';

// classes required for global-module-settings
require_once 'lib/classes/AdminModules.class.php';


/**
 * This controller realises the basal functionalities of a studygroup.
 *
 */
class Course_StudygroupController extends AuthenticatedController {

    // see Trails_Controller#before_filter
    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        if (Config::Get()->STUDYGROUPS_ENABLE
            || in_array($action, words('globalmodules savemodules deactivate'))) {

        // args at position zero is always the studygroup-id
        if ($args[0]) {
                if (SeminarCategories::GetBySeminarId($args[0])->studygroup_mode == false) {
                throw new Exception(_('Dieses Seminar ist keine Studiengruppe!'));
            }
        }
        PageLayout::setTitle(_("Studiengruppe bearbeiten"));
        PageLayout::setHelpKeyword('Basis.Studiengruppen');
        } else {
            throw new Exception(_("Die von Ihnen gew�hlte Option ist im System nicht aktiviert."));
        }

        $this->set_layout('course/studygroup/layout');
    }

    /**
     * shows details of a studygroup
     *
     * @param string id of a studygroup
     *
     * @return void
     */
    function details_action($id)
    {
        global $perm;

        PageLayout::setTitle(getHeaderLine($id) . ' - ' . _('Studiengruppendetails'));
        PageLayout::setHelpKeyword('Basis.StudiengruppenAbonnieren');

        $stmt = DBManager::get()->prepare("SELECT * FROM admission_seminar_user"
                    . " WHERE user_id = ? AND seminar_id = ?");
        $stmt->execute(array($GLOBALS['user']->id, $id));
        $data = $stmt->fetch();

        if ($data['status'] == 'accepted') $this->membership_requested = true;

        if ($perm->have_studip_perm('autor', $id)) {
            $this->participant = true;
        } else {
            $this->participant = false;
        }

        $this->studygroup = new Seminar($id);
        if (!preg_match('/^(' . preg_quote($GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP'],'/') . ')?([a-zA-Z0-9_-]+\.php)([a-zA-Z0-9_?&=-]*)$/', Request::get('send_from_search_page'))) {
            $this->send_from_search_page = '';
        } else {
            $this->send_from_search_page = Request::get('send_from_search_page');
        }
    }

    /**
     * displays a form for creating studygroups
     *
     * @return void
     */
    function new_action()
    {
        PageLayout::setHelpKeyword('Basis.StudiengruppenAnlegen');
        closeObject();

        PageLayout::setTitle(_("Studiengruppe anlegen"));
        Navigation::activateItem('/community/studygroups/new');
        $this->terms             = Config::Get()->STUDYGROUP_TERMS;
        $this->available_modules = StudygroupModel::getInstalledModules();
        $this->available_plugins = StudygroupModel::getInstalledPlugins();
        $this->modules           = new Modules();
        $this->groupaccess       = $this->flash['request']['groupaccess'];
        foreach ($GLOBALS['SEM_CLASS'] as $key => $sem_class) {
            if ($sem_class['studygroup_mode']) {
                $this->sem_class = $sem_class;
                break;
    }
        }
    }

    /**
     * @addtogroup notifications
     *
     * Creating a new studygroup triggers a StudygroupDidCreate
     * notification. The ID of the studygroup is transmitted as
     * subject of the notification.
     */

    /**
     * creates a new studygroup with respect to given form data
     *
     * Triggers a StudygroupDidCreate notification using the ID of the
     * new studygroup as subject.
     *
     * @return void
     */
    function create_action()
    {
        global $perm;

        $admin  = $perm->have_perm('admin');
        $errors = array();

        CSRFProtection::verifyUnsafeRequest();

        foreach ($GLOBALS['SEM_CLASS'] as $key => $class) {
            if ($class['studygroup_mode']) {
                $sem_class = $class;
                break;
            }
        }

        if (Request::getArray('founders')) {
            $founders = Request::optionArray('founders');
            $this->flash['founders'] = $founders;
        }
        // search for founder
        if ($admin && Request::submitted('search_founder')) {
            $search_for_founder = Request::get('search_for_founder');

            // do not allow to search with the empty string
            if ($search_for_founder) {
                // search for the user
                $query = "SELECT user_id, {$GLOBALS['_fullname_sql']['full_rev']} AS fullname, username, perms
                          FROM auth_user_md5
                          LEFT JOIN user_info USING (user_id)
                          WHERE perms NOT IN ('root', 'admin')
                            AND (username LIKE CONCAT('%', :needle, '%')
                              OR Vorname LIKE CONCAT('%', :needle, '%')
                              OR Nachname LIKE CONCAT('%', :needle, '%'))
                          LIMIT 500";
                $statement = DBManager::get()->prepare($query);
                $statement->bindValue(':needle', $search_for_founder);
                $statement->execute();
                $results_founders = $statement->fetchGrouped(PDO::FETCH_ASSOC);
            }

            if(is_array($results_founders)) {
                $this->flash['success'] = sizeof($results_founders) == 1 ? sprintf(_("Es wurde %s Person gefunden:"),sizeof($results_founders)) : sprintf(_("Es wurden %s Personen gefunden:"),sizeof($results_founders));
            }
            else {
                $this->flash['info'] = _("Es wurden kein Personen gefunden.");
            }

            $this->flash['create']                  = true;
            $this->flash['results_choose_founders'] = $results_founders;
            $this->flash['request']                 = Request::getInstance();

            // go to the form again
            $this->redirect('course/studygroup/new/');
        }

        // add a new founder
        else if ($admin && Request::submitted('add_founder')) {
            $founders = array(Request::option('choose_founder'));

            $this->flash['founders'] = $founders;
            $this->flash['create']   = true;
            $this->flash['request']  = Request::getInstance();

            $this->redirect('course/studygroup/new/');
        }

        // remove a founder
        else if ($admin && Request::submitted('remove_founder')) {
            unset($founders);

            $this->flash['founders']  = $founders;
            $this->flash['create']    = true;
            $this->flash['request']   = Request::getInstance();

            $this->redirect('course/studygroup/new/');
        }

        // reset search
        else if ($admin && Request::submitted('new_search')) {

            $this->flash['create']  = true;
            $this->flash['request'] = Request::getInstance();

            $this->redirect('course/studygroup/new/');
        }

        //checks
        else {
            if (!Request::get('groupname')) {
                $errors[] = _("Bitte Gruppennamen angeben");
            } else {
                $query = "SELECT 1 FROM seminare WHERE name = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array(
                    Request::get('groupname')
                ));
                if ($statement->fetchColumn()) {
                    $errors[] = _("Eine Veranstaltung/Studiengruppe mit diesem Namen existiert bereits. Bitte w�hlen Sie einen anderen Namen");
                }
            }

            if (!Request::get('grouptermsofuse_ok')) {
                $errors[] = _("Sie m�ssen die Nutzungsbedingungen durch Setzen des H�kchens bei 'Einverstanden' akzeptieren.");
            }

            if ($admin && (!is_array($founders) || !sizeof($founders))) {
                $errors[] = _("Sie m�ssen mindestens einen Gruppengr�nder eintragen!");
            }

            if (count($errors)) {
                $this->flash['errors'] =  $errors;
                $this->flash['create'] = true;
                $this->flash['request'] = Request::getInstance();
                $this->redirect('course/studygroup/new/');
            } else {
                // Everything seems fine, let's create a studygroup

                $sem_types           = studygroup_sem_types();
                $sem                 = new Seminar();
                $sem->name           = Request::get('groupname');         // seminar-class quotes itself
                $sem->description    = Request::get('groupdescription');  // seminar-class quotes itself
                $sem->status         = $sem_types[0];
                $sem->read_level     = 1;
                $sem->write_level    = 1;
                $sem->institut_id    = Config::Get()->STUDYGROUP_DEFAULT_INST;
                $mods                = new Modules();
                $bitmask             = 0;
                $sem->admission_type = 0;
                $sem->visible        = 1;
                if (Request::get('groupaccess') == 'all') {
                    $sem->admission_prelim = 0;
                } else {
                    $sem->admission_prelim    = 1;
                    if (Config::get()->STUDYGROUPS_INVISIBLE_ALLOWED && Request::get('groupaccess') == 'invisible') {
                        $sem->visible        = 0;
                    }
                    $sem->admission_prelim_txt = _("Die ModeratorInnen der Studiengruppe k�nnen Ihren Aufnahmewunsch best�tigen oder ablehnen. Erst nach Best�tigung erhalten Sie vollen Zugriff auf die Gruppe.");
                }
                $sem->admission_endtime     = -1;
                $sem->admission_binding     = 0;
                $sem->admission_starttime   = -1;
                $sem->admission_endtime_sem = -1;
                
                $semdata                     = new SemesterData();
                $this_semester               = $semdata->getSemesterDataByDate(time());
                $sem->semester_start_time    = $this_semester['beginn'];
                $sem->semester_duration_time = -1;

                if ($admin) {
                    // insert founder(s)
                    foreach ($founders as $user_id) {
                        $stmt = DBManager::get()->prepare("INSERT INTO seminar_user
                            (seminar_id, user_id, status, gruppe)
                            VALUES (?, ?, 'dozent', 8)");
                        $stmt->execute(array( $sem->id, $user_id ));
                    }

                    $this->founders = null;
                    $this->flash['founders'] = null;
                } else {
                    $user_id = $GLOBALS['auth']->auth['uid'];
                    // insert dozent
                    $query = "INSERT INTO seminar_user (seminar_id, user_id, status, gruppe)
                              VALUES (?, ?, 'dozent', 8)";
                    $statement = DBManager::get()->prepare($query);
                    $statement->execute(array($sem->id, $user_id));
                }

                // de-/activate modules
                $mods              = new Modules();
                $admin_mods        = new AdminModules();
                $bitmask           = 0;
                $available_modules = StudygroupModel::getInstalledModules();
                $active_plugins    = Request::getArray('groupplugin');

                foreach ($available_modules as $key => $enable) {
                    $module_name = $sem_class->getSlotModule($key);
                    if ($module_name
                            && ($sem_class->isModuleMandatory($module_name)
                                || !$sem_class->isModuleAllowed($module_name))) {
                        continue;
                    }
                    if (!$module_name) {
                        $module_name = $key;
                    }

                    if ($active_plugins[$module_name]) {
                        // activate modules
                        $mods->setBit($bitmask, $mods->registered_modules[$key]["id"]);
                        $methodActivate = "module".ucfirst($key)."Activate";
                        if (method_exists($admin_mods, $methodActivate)) {
                            $admin_mods->$methodActivate($sem->id);
                        }
                    }
                }
                // always activate participants list
                $mods->setBit($bitmask, $mods->registered_modules["participants"]["id"]);

                $sem->modules = $bitmask;
                $sem->store();

                // de-/activate plugins
                $available_plugins = StudygroupModel::getInstalledPlugins();
                $plugin_manager    = PluginManager::getInstance();

                foreach ($available_plugins as $key => $name) {
                    if (!$sem_class->isModuleAllowed($key)) {
                        continue;
                    }
                    $plugin    = $plugin_manager->getPlugin($key);
                    $plugin_id = $plugin->getPluginId();

                    if ($active_plugins[$key] && $name) {
                        $plugin_manager->setPluginActivated($plugin_id, $sem->id, true);
                    } else {
                        $plugin_manager->setPluginActivated($plugin_id, $sem->id, false);
                    }
                }

                NotificationCenter::postNotification('StudygroupDidCreate', $sem->id);

                // the work is done. let's visit the brand new studygroup.
                $this->redirect(URLHelper::getURL('seminar_main.php?auswahl=' . $sem->id));

            }
        }
    }

    /**
     * displays a form for editing studygroups with corresponding data
     *
     * @param string id of a studygroup
     *
     * @return void
     */
    function edit_action($id)
    {
        global $perm;

        $this->flash->keep('deactivate_modules');
        $this->flash->keep('deactivate_plugins');
        PageLayout::setHelpKeyword('Basis.StudiengruppenBearbeiten');

        // if we are permitted to edit the studygroup get some data...
        if ($perm->have_studip_perm('dozent', $id)) {

            PageLayout::setTitle(getHeaderLine($id) . ' - ' . _('Studiengruppe bearbeiten'));
            Navigation::activateItem('/course/admin/main');

            $sem                     = new Seminar($id);
            $this->sem_id            = $id;
            $this->sem               = $sem;
            $this->sem_class         = $GLOBALS['SEM_CLASS'][$GLOBALS['SEM_TYPE'][$sem->status]['class']];
            $this->tutors            = $sem->getMembers('tutor');
            $this->available_modules = StudygroupModel::getInstalledModules();
            $this->available_plugins = StudygroupModel::getInstalledPlugins();
            $this->enabled_plugins   = StudygroupModel::getEnabledPlugins($id);
            $this->modules           = new Modules();
            $this->founders          = StudygroupModel::getFounders( $id );

            $this->deactivate_modules_names = "";
            if ($this->flash['deactivate_modules']) {
                $amodules = new AdminModules();
                foreach ($this->flash['deactivate_modules'] as $key) {
                        $this->deactivate_modules_names .= "- ".$amodules->registered_modules[$key]['name'] . "\n";
                }
            }
            if ($this->flash['deactivate_plugins']) {
                foreach ($this->flash['deactivate_plugins'] as $key => $name) {
                    $plugin = PluginManager::getInstance()->getPluginById($key);
                    $p_warning = $plugin->deactivationWarning($id);
                    $this->deactivate_modules_names .= "- ".$name
                            .($p_warning ? " : " . $p_warning : "")
                            ."\n";
                }
            }

        }
        // ... otherwise redirect us to the seminar
        else {
            $this->redirect(URLHelper::getURL('seminar_main.php?auswahl=' . $id));
        }
    }

    /**
     * updates studygroups with respect to the corresponding form data
     *
     * @param string id of a studygroup
     *
     * @return void
     */
    function update_action($id)
    {
        global $perm;
        // if we are permitted to edit the studygroup get some data...
        if ($perm->have_studip_perm('dozent', $id)) {
            $errors   = array();
            $admin    = $perm->have_studip_perm('admin', $id);
            $founders = StudygroupModel::getFounders($id);
            $sem       = new Seminar($id);
            $sem_class = $GLOBALS['SEM_CLASS'][$GLOBALS['SEM_TYPE'][$sem->status]['class']];

            CSRFProtection::verifyUnsafeRequest();

            if (Request::get('abort_deactivate')) {
                // let's do nothing and go back to the studygroup
                return $this->redirect('course/studygroup/edit/' . $id);

            } else if (Request::get('really_deactivate')) {

                $modules = Request::optionArray('deactivate_modules');
                $plugins = Request::optionArray('deactivate_plugins');

                // really deactive modules

                // 1. Modules
                if (is_array($modules)) {

                    $mods = new Modules();
                    $admin_mods = new AdminModules();
                    $bitmask = $sem->modules;
                    foreach ($modules as $key) {
                        $module_name = $sem_class->getSlotModule($key);
                        if ($module_name
                                && ($sem_class->isModuleMandatory($module_name)
                                    || !$sem_class->isModuleAllowed($module_name))) {
                            continue;
                        }
                        $mods->clearBit($bitmask, $mods->registered_modules[$key]["id"]);
                        $methodDeactivate = "module".ucfirst($key)."Deactivate";
                        if (method_exists($admin_mods, $methodDeactivate)) {
                            $admin_mods->$methodDeactivate($sem->id);
                            $studip_module = $sem_class->getModule($key);
                            if (is_a($studip_module, "StandardPlugin")) {
                                PluginManager::getInstance()->setPluginActivated(
                                    $studip_module->getPluginId(),
                                    $id,
                                    false
                                );
                            }
                        }
                    }

                    $sem->modules = $bitmask;
                    $sem->store();
                }

                // 2. Plugins

                if (is_array($plugins)) {
                    $plugin_manager = PluginManager::getInstance();
                    $available_plugins = StudygroupModel::getInstalledPlugins();
                    
                    foreach ($plugins as $class) {
                        $plugin = $plugin_manager->getPlugin($class);
                        // Deaktiviere Plugin
                        if ($available_plugins[$class]
                            && !$sem_class->isModuleMandatory($class)
                            && !$sem_class->isSlotModule($class))
                        {
                            $plugin_manager->setPluginActivated($plugin->getPluginId(), $id, false);
                        }
                    }
                }

                // Success message

                $this->flash['success'] .= _("Modul(e) erfolgreich deaktiviert.");
                return $this->redirect('course/studygroup/edit/' . $id);

            } else if (Request::submitted('replace_founder'))  {

                // retrieve old founder
                $old_dozent = current(StudygroupModel::getFounder($id));

                // remove old founder
                StudygroupModel::promote_user($old_dozent['uname'],$id,'tutor');

                // add new founder
                $new_founder = Request::option('choose_founder');
                StudygroupModel::promote_user(get_username($new_founder), $id,'dozent');

            //checks
            } else {
                // test whether we have a group name...
                if (!Request::get('groupname')) {
                    $errors[] = _("Bitte Gruppennamen angeben");
                //... if so, test if this is not taken by another group
                } else {
                    $query = "SELECT 1 FROM seminare WHERE name = ? AND Seminar_id != ?";
                    $statement = DBManager::get()->prepare($query);
                    $statement->execute(array(
                        Request::get('groupname'),
                        $id
                    ));
                    if ($statement->fetchColumn()) {
                        $errors[] = _("Eine Veranstaltung/Studiengruppe mit diesem Namen existiert bereits. Bitte w�hlen Sie einen anderen Namen");
                    }
                }
                if (count($errors)) {
                    $this->flash['errors'] =  $errors;
                    $this->flash['edit'] = true;
                // Everything seems fine, let's update the studygroup
                } else {
                    $sem->name           = Request::get('groupname');         // seminar-class quotes itself
                    $sem->description    = Request::get('groupdescription');  // seminar-class quotes itself
                    $sem->read_level     = 1;
                    $sem->write_level    = 1;
                    $sem->admission_type = 0;
                    $sem->visible = 1;
                    
                    if (Request::get('groupaccess') == 'all') {
                        $sem->admission_prelim = 0;
                    } else {
                        $sem->admission_prelim = 1;
                        if (Config::get()->STUDYGROUPS_INVISIBLE_ALLOWED && Request::get('groupaccess') == 'invisible') {
                            $sem->visible = 0;
                        }
                        $sem->admission_prelim_txt = _("Die ModeratorInnen der Studiengruppe k�nnen Ihren Aufnahmewunsch best�tigen oder ablehnen. Erst nach Best�tigung erhalten Sie vollen Zugriff auf die Gruppe.");
                    }

                    // get the current bitmask
                    $mods = new Modules();
                    $admin_mods = new AdminModules();
                    $bitmask = $sem->modules;

                    // de-/activate modules
                    $available_modules = StudygroupModel::getInstalledModules();
                    $orig_modules = $mods->getLocalModules($sem->id, "sem");
                    $active_plugins = Request::getArray("groupplugin");

                    $deactivate_modules = array();
                    foreach (array_keys($available_modules) as $key) {
                        $module_name = $sem_class->getSlotModule($key);
                        if (!$module_name || ($module_name
                                && ($sem_class->isModuleMandatory($module_name)
                                    || !$sem_class->isModuleAllowed($module_name)))) {
                            continue;
                        }
                        if (!$module_name) {
                            $module_name = $key;
                        }
                        if ($active_plugins[$module_name]) {
                            // activate modules
                            $mods->setBit($bitmask, $mods->registered_modules[$key]["id"]);
                            if (!$orig_modules[$key]) {
                                $methodActivate = "module".ucfirst($key)."Activate";
                                if (method_exists($admin_mods, $methodActivate)) {
                                    $admin_mods->$methodActivate($sem->id);
                                    $studip_module = $sem_class->getModule($key);
                                    if (is_a($studip_module, "StandardPlugin")) {
                                        PluginManager::getInstance()->setPluginActivated(
                                            $studip_module->getPluginId(),
                                            $id,
                                            true
                                        );
                                    }
                                }
                            }
                        } else {
                            // prepare for deactivation
                            // (user will have to confirm)
                            if ($orig_modules[$key]) {
                                $deactivate_modules[]=$key;
                            }
                        }
                    }
                    $this->flash['deactivate_modules'] = $deactivate_modules;

                    $sem->modules = $bitmask;
                    $sem->store();

                    // de-/activate plugins
                    $available_plugins = StudygroupModel::getInstalledPlugins();
                    $plugin_manager    = PluginManager::getInstance();
                    $deactivate_plugins = array();

                    foreach ($available_plugins as $key => $name) {
                        $plugin = $plugin_manager->getPlugin($key);
                        $plugin_id = $plugin->getPluginId();
                        if ($active_plugins[$key] && $name && $sem_class->isModuleAllowed($key)) {
                            $plugin_manager->setPluginActivated($plugin_id, $id, true);
                        } else {
                            if ($plugin_manager->isPluginActivated($plugin_id, $id) && !$sem_class->isSlotModule($key)) {
                                $deactivate_plugins[$plugin_id] = $key;
                            }
                        }
                    }
                    $this->flash['deactivate_plugins'] = $deactivate_plugins;
                }
            }
        }

        if (!$this->flash['errors'] && !$deactivate_modules && !$deactivate_plugins) {
           // Everything seems fine
            $this->flash['success'] = _("Die �nderungen wurden erfolgreich �bernommen.");
        }
        // let's go to the studygroup
        $this->redirect('course/studygroup/edit/' . $id);
    }



    /**
     * displays a paginated member overview of a studygroup
     *
     * @param string id of a studypgroup
     * @param string page number the current page
     *
     * @return void
     *
     */
    function members_action($id, $page = 1)
    {
        PageLayout::setTitle(getHeaderLine($id) . ' - ' . _("TeilnehmerInnen"));
        Navigation::activateItem('/course/members');
        PageLayout::setHelpKeyword('Basis.StudiengruppenBenutzer');

        Request::set('choose_member_parameter', $this->flash['choose_member_parameter']);

        object_set_visit_module('participants');
        $this->last_visitdate = object_get_visit($id, 'participants');

        $sem          = new Seminar($id);
        $this->page   = $page;
        $this->anzahl = StudygroupModel::countMembers($id);

        if($this->page < 1 || $this->page > ceil($this->anzahl/get_config('ENTRIES_PER_PAGE'))) $this->page = 1;

        $this->lower_bound      = ($this->page - 1) * get_config('ENTRIES_PER_PAGE');
        $this->cmembers         = StudygroupModel::getMembers($id, $this->lower_bound, get_config('ENTRIES_PER_PAGE'));
        usort($this->cmembers, array('StudygroupModel','compare_status'));
        $this->groupname        = $sem->name;
        $this->sem_id           = $id;
        $this->groupdescription = $sem->description;
        $this->moderators       = $sem->getMembers('dozent');
        $this->tutors           = $sem->getMembers('tutor');
        $this->accepted         = $sem->getAdmissionMembers('accepted');
        $this->inviting_search = new SQLSearch("SELECT auth_user_md5.user_id, {$GLOBALS['_fullname_sql']['full_rev']} as fullname, username, perms "
                                    . "FROM auth_user_md5 "
                                    . "LEFT JOIN user_info ON (auth_user_md5.user_id = user_info.user_id) "
                                    . "LEFT JOIN seminar_user ON (auth_user_md5.user_id = seminar_user.user_id AND seminar_user.Seminar_id = '".addslashes($id)."') "
                                    . "WHERE perms  NOT IN ('root', 'admin') "
                                    . "AND seminar_user.Seminar_id IS NULL "
                                    . "AND " . get_vis_query()
                                    . " AND (username LIKE :input OR Vorname LIKE :input "
                                    . "OR CONCAT(Vorname,' ',Nachname) LIKE :input "
                                    . "OR CONCAT(Nachname,' ',Vorname) LIKE :input "
                                    . "OR Nachname LIKE :input OR {$GLOBALS['_fullname_sql']['full_rev']} LIKE :input) "
                                    . "ORDER BY fullname ASC",
                                    _("Nutzer suchen"), "user_id");
        $this->rechte           = $GLOBALS['perm']->have_studip_perm("tutor", $id);
    }

    /**
     * offers specific member functions wrt perms
     *
     * @param string id of a studypgroup
     * @param string action that has to be performed
     * @param string status if applicable (e.g. tutor)
     *
     * @return void
     */
    function edit_members_action($id, $action, $status = '', $studipticket = false)
    {
        global $perm;

        $user = Request::get('user');
        $user = preg_replace('/[^\w@.-]/', '', $user);

        if ($perm->have_studip_perm('tutor', $id)) {

            if (!$action) {
                $this->flash['success'] = _("Es wurde keine korrekte Option gew�hlt.");
            } elseif ($action == 'accept') {
                StudygroupModel::accept_user($user,$id);
                $this->flash['success'] = sprintf(_("Der Nutzer %s wurde akzeptiert."), get_fullname_from_uname($user, 'full', true));
            } elseif ($action == 'deny') {
                StudygroupModel::deny_user($user,$id);
                $this->flash['success'] = sprintf(_("Der Nutzer %s wurde nicht akzeptiert."), get_fullname_from_uname($user, 'full', true));
            } elseif ($action == 'add_invites') {
                if (Request::get('choose_member') && Request::submitted('add_member')) {
                    $msg = new Messaging();
                    $receiver = Request::option('choose_member');
                    $sem = new Seminar($id);
                    $message = sprintf(_("%s m�chte Sie auf die Studiengruppe %s aufmerksam machen. Klicken Sie auf den untenstehenden Link, um direkt zur Studiengruppe zu gelangen.\n\n %s"),
                             get_fullname(), $sem->name, URLHelper::getlink("dispatch.php/course/studygroup/details/" . $id, array('cid' => NULL)));
                    $subject = _("Sie wurden in eine Studiengruppe eingeladen");
                    $msg->insert_message($message, get_username($receiver),'', '', '', '', '', $subject);
                    $this->flash['success'] = sprintf(_("%s wurde in die Studiengruppe eingeladen."), get_fullname($receiver, 'full', true));
                }
            } elseif ($perm->have_studip_perm('tutor', $id)) {
                if(!$perm->have_studip_perm('dozent',$id,get_userid($user))) {
                    if ($action == 'promote' && $status != 'dozent' && $perm->have_studip_perm('dozent',$id)) {
                        StudygroupModel::promote_user($user,$id,$status);
                        $this->flash['success'] = sprintf(_("Der Status des Nutzers %s wurde ge�ndert."), get_fullname_from_uname($user, 'full', true));
                    } elseif ($action == 'promote' && $status == 'autor' && $perm->have_studip_perm('tutor',$id) && $GLOBALS['auth']->auth['uname'] == $user) {
                        StudygroupModel::promote_user($user,$id,$status);
                        $this->flash['success'] = sprintf(_("Der Status des Nutzers %s wurde ge�ndert."), get_fullname_from_uname($user, 'full', true));
                    } elseif ($action == 'remove') {
                        $this->flash['question'] = sprintf(_("M�chten Sie wirklich den Nutzer %s aus der Studiengruppe entfernen?"), get_fullname_from_uname($user, 'full', true));
                        $this->flash['candidate'] = $user;

                    } elseif ($action == 'remove_approved' && check_ticket($studipticket)) {
                        StudygroupModel::remove_user($user,$id);
                        $this->flash['success'] = sprintf(_("Der Nutzer %s wurde aus der Studiengruppe entfernt."), get_fullname_from_uname($user, 'full', true));
                    }
                } else {
                    $this->flash['messages'] = array(
                        'error' => array (
                            'title' => _("Jede Studiengruppe muss mindestens einen Gruppengr�nder haben!")
                        )
                    );
                }
            }
            //F�r die QuickSearch-Suche:
            if (Request::get('choose_member_parameter') && Request::get('choose_member_parameter') !== _("Nutzer suchen") ) {
                $this->flash['choose_member_parameter'] = Request::get('choose_member_parameter');
            }
            $this->redirect('course/studygroup/members/' . $id);
        }   else {
            $this->redirect(URLHelper::getURL('seminar_main.php?auswahl=' . $id));
        }
    }

    /**
     * deletes a studygroup
     *
     * @param string id of a studypgroup
     * @param boolean approveDelete
     * @param string studipticket
     *
     * @return void
     *
     */
    function delete_action($id, $approveDelete = false, $studipticket = false)
    {
        global $perm;
        if ($perm->have_studip_perm( 'dozent',$id )) {

            if ($approveDelete && check_ticket($studipticket)) {
                $messages = array();
                $sem = new Seminar($id);
                $sem->delete();
                if ($messages = $sem->getStackedMessages()) {
                    $this->flash['messages'] = $messages;
                }
                unset($sem);
                
                // Weiterleitung auf die "meine Seminare", wenn es kein Admin
                // ist, ansonsten auf die Studiengruppenseite
                if (!$perm->have_perm('root')) {
                    $this->redirect(URLHelper::getURL('meine_seminare.php'));
                } else {
                    $this->redirect(URLHelper::getURL('dispatch.php/studygroup/browse'));
                }
                return;
            } else if (!$approveDelete) {
                $template = $GLOBALS['template_factory']->open('shared/question');

                $template->set_attribute('approvalLink', $this->url_for('course/studygroup/delete/' . $id . '/true/' . get_ticket()));
                $template->set_attribute('disapprovalLink', $this->url_for('course/studygroup/edit/' . $id));
                $template->set_attribute('question', _("Sind Sie sicher, dass Sie diese Studiengruppe l�schen m�chten?"));

                $this->flash['question'] = $template->render();
                $this->redirect('course/studygroup/edit/' . $id);
                return;
            }
        }
        throw new Trails_Exception(401);
    }


    /**
     * Displays admin settings concerning the modules and plugins which that are globally available for studygroups
     *
     * @return void
     */
    function globalmodules_action()
    {
        global $perm;
        $perm->check("root");
        PageLayout::setHelpKeyword('Admin.Studiengruppen');

        // get institutes
        $institutes = StudygroupModel::getInstitutes();
        $default_inst = Config::Get()->STUDYGROUP_DEFAULT_INST;

        // Nutzungsbedingungen
        $terms = Config::Get()->STUDYGROUP_TERMS;

        if ($this->flash['institute']) {
            $default_inst = $this->flash['institute'];
        }
        if ($this->flash['modules']) {
            foreach ($this->flash['modules'] as $module => $status) {
                $enabled[$module] = ($status == 'on') ? true : false;
            }
        }
        if ($this->flash['terms']) $terms = $this->flash['terms'];

        PageLayout::setTitle(_('Verwaltung studentischer Arbeitsgruppen'));
        Navigation::activateItem('/admin/config/studygroup');

        $query = "SELECT COUNT(*) FROM seminare WHERE status IN (?)";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(studygroup_sem_types()));

        // set variables for view
        $this->can_deactivate = $statement->fetchColumn() == 0;
        $this->current_page   = _("Verwaltung erlaubter Inhaltselemente und Plugins f�r Studiengruppen");
        $this->configured     = count(studygroup_sem_types()) > 0;
        $this->institutes     = $institutes;
        $this->default_inst   = $default_inst;
        $this->terms          = $terms;
    }

    /**
     * sets the global module and plugin settings for studygroups
     *
     * @return void
     */
    function savemodules_action()
    {
        global $perm;
        $perm->check("root");
        PageLayout::setHelpKeyword('Admin.Studiengruppen');

        if (Request::quoted('institute') == 'invalid') {
            $errors[] = _("Bitte w�hlen Sie eine Einrichtung aus, der die Studiengruppen zugeordnet werden sollen!");
        }

        if (!Request::quoted('terms') || Request::quoted('terms') == 'invalid') {
            $errors[] = _("Bitte tragen Sie Nutzungsbedingungen ein!");
        }

        if ($errors) {
            $this->flash['messages'] = array('error' => array('title' => 'Die Studiengruppen konnten nicht aktiviert werden!', 'details' =>  $errors));
            $this->flash['institute'] = Request::get('institute');
            $this->flash['terms']     = Request::get('terms');
        }

        if (!$errors) {
            $cfg = Config::get();
            if ($cfg->STUDYGROUPS_ENABLE == FALSE && count(studygroup_sem_types()) > 0) {
                $cfg->store("STUDYGROUPS_ENABLE", true);
                $this->flash['success'] = _("Die Studiengruppen wurden aktiviert.");
            }

            if (Request::get('institute')) {
                $cfg->store('STUDYGROUP_DEFAULT_INST', Request::quoted('institute'));
                $cfg->store('STUDYGROUP_TERMS', Request::quoted('terms'));
                $this->flash['success'] = _("Die Einstellungen wurden gespeichert!");
            } else {
                $this->flash['error'] = _("Fehler beim Speichern der Einstellung!");
            }
        }
        $this->redirect('course/studygroup/globalmodules');
    }

    /**
     * globally deactivates the studygroups
     *
     * @return void
     */
    function deactivate_action()
    {
        global $perm;
        $perm->check("root");
        PageLayout::setHelpKeyword('Admin.Studiengruppen');

        $query = "SELECT COUNT(*) FROM seminare WHERE status IN (?)";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(studygroup_sem_types()));

        if (($count = $statement->fetchColumn()) != 0) {
            $this->flash['messages'] = array('error' => array(
                'title' => sprintf(_("Sie k�nnen die Studiengruppen nicht deaktivieren, da noch %s Studiengruppen vorhanden sind!"), $count)
            ));
        } else {
            Config::get()->store("STUDYGROUPS_ENABLE", false);
            $this->flash['success'] = _("Die Studiengruppen wurden deaktiviert.");
        }
        $this->redirect('course/studygroup/globalmodules');
    }

    /**
     * sends a message to all members of a studygroup
     *
     * @param string id of a studygroup
     *
     * @return void
     */

    function message_action($id)
    {
        $sem         = Seminar::GetInstance($id);
        $source      = 'dispatch.php/course/studygroup/members/' . $id;
        if (studip_strlen($sem->getName()) > 32) //cut subject if to long
            $subject = sprintf(_("[Studiengruppe: %s...]"),studip_substr($sem->getName(), 0, 30));
        else
            $subject = sprintf(_("[Studiengruppe: %s]"),$sem->getName());

        $this->redirect(URLHelper::getURL('sms_send.php', array('sms_source_page' => $source, 'course_id' => $id, 'subject' => $subject, 'filter' => 'all')));
    }


}
