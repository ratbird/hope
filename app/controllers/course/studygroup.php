<?php
/*
 * studygroup.php - contains Course_StudygroupController
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Klaßen <andre.klassen@elan-ev.de>
 * @copyright   2009-2010 ELAN e.V.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     studygroup
 * @since 1.10
 */

require_once 'app/controllers/authenticated_controller.php';
require_once 'lib/classes/Seminar.class.php';
require_once 'lib/classes/Modules.class.php';
require_once 'lib/classes/StudygroupAvatar.class.php';
require_once 'app/models/studygroup.php';
require_once 'lib/messaging.inc.php';
require_once 'lib/user_visible.inc.php';

// classes required for global-module-settings
require_once('lib/classes/AdminModules.class.php');
require_once('lib/classes/Config.class.php');

if (!defined('ELEMENTS_PER_PAGE')) define("ELEMENTS_PER_PAGE", 20);

/**
 * This controller realises the basal functionalities of a studygroup.
 *
 */
class Course_StudygroupController extends AuthenticatedController {

    // see Trails_Controller#before_filter
    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        if (Config::GetInstance()->getValue('STUDYGROUPS_ENABLE')
            || in_array($action, words('globalmodules savemodules deactivate'))) {

        // args at position zero is always the studygroup-id
        if ($args[0]) {
                if (SeminarCategories::GetBySeminarId($args[0])->studygroup_mode == false) {
                throw new Exception(_('Dieses Seminar ist keine Studiengruppe!'));
            }
        }
        $GLOBALS['CURRENT_PAGE'] =  _("Studiengruppe bearbeiten");
        $GLOBALS['HELP_KEYWORD'] = 'Basis.Studiengruppen';
        } else {
            throw new Exception(_("Die von Ihnen gewählte Option ist im System nicht aktiviert."));
        }
    }

    /**
     * shows details of a studygroup
     *
     * @param string id of a studygroup     *
     * @return void
     */
    function details_action($id)
    {
        global $perm;

        $GLOBALS['CURRENT_PAGE'] = getHeaderLine($id) . ' - ' . _('Studiengruppendetails');
        //TODO Navigation::activateItem('/course/main/details');

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
        if (!preg_match('/^(' . preg_quote($GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP'],'/') . ')?([a-zA-Z0-9_-]+\.php)([a-zA-Z0-9_?&=-]*)$/', $_REQUEST['send_from_search_page'])) {
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
        closeObject();

        $GLOBALS['CURRENT_PAGE'] = _("Studiengruppe anlegen");
        Navigation::activateItem('/community/studygroups/new');
        $this->terms             = Config::GetInstance()->getValue('STUDYGROUP_TERMS');
        $this->available_modules = StudygroupModel::getAvailableModules();
        $this->available_plugins = StudygroupModel::getAvailablePlugins();
        $this->modules           = new Modules();
        $this->groupaccess       = $this->flash['request']['groupaccess'];
    }

    /**
     * creates a new studygroup with respect to given form data
     *
     * @return void
     */
    function create_action()
    {
        global $perm;

        $admin  = $perm->have_perm('admin');
        $errors = array();

        if (Request::getArray('founders')) {
            $founders = Request::getArray('founders');
            $this->flash['founders'] = Request::getArray('founders');
        }

        // search for founder
        if ($admin && (Request::get('search_founder') || Request::get('search_founder_x'))) {
            $search_for_founder = Request::get('search_for_founder');

            // do not allow to search with the empty string
            if ($search_for_founder) {

                // search for the user
                $pdo = DBManager::get();
                $search_for_founder = $pdo->quote('%' . $search_for_founder . '%');
                $stmt = $pdo->query("SELECT user_id, {$GLOBALS['_fullname_sql']['full_rev']} as fullname, username, perms"
                            . " FROM auth_user_md5"
                            . " LEFT JOIN user_info USING (user_id)"
                            . " WHERE username LIKE $search_for_founder OR Vorname LIKE $search_for_founder"
                            . " OR Nachname LIKE $search_for_founder"
                            . " LIMIT 500");

                while ($data = $stmt->fetch()) {
                    $results_founders[$data['user_id']] = array(
                        'fullname' => $data['fullname'],
                        'username' => $data['username'],
                        'perms'    => $data['perms']
                    );
                }
            }
            $this->flash['create']                  = true;
            $this->flash['results_choose_founders'] = $results_founders;
            $this->flash['request']                 = Request::getInstance();

            // go to the form again
            $this->redirect('course/studygroup/new/');
        }

        // add a new founder
        else if ($admin && (Request::get('add_founder') || Request::get('add_founder_x'))) {

            $founders[Request::get('choose_founder')] = array(
                'username' => Request::get('choose_founder'),
                'fullname' => get_fullname_from_uname(Request::get('choose_founder'), 'full_rev')
            );

            $this->flash['founders'] = $founders;
            $this->flash['create']   = true;
            $this->flash['request']  = Request::getInstance();

            $this->redirect('course/studygroup/new/');
        }

        // remove a founder
        else if ($admin && (Request::get('remove_founder') || Request::get('remove_founder_x'))) {

            if (Request::get('remove_founder')) {
                $name = Request::get('remove_founder');
            } else {
                $name = Request::get('remove_founder_x');
            }

            unset($founders[$name]);

            $this->flash['founders']  = $founders;
            $this->flash['create']    = true;
            $this->flash['request']   = Request::getInstance();

            $this->redirect('course/studygroup/new/');
        }

        // reset search
        else if ($admin && (Request::get('new_search') || Request::get('new_search_x'))) {

            $this->flash['create']  = true;
            $this->flash['request'] = Request::getInstance();

            $this->redirect('course/studygroup/new/');
        }

        //checks
        else {
            if (!Request::get('groupname')) {
                $errors[] = _("Bitte Gruppennamen angeben");
            } else {
                $pdo = DBManager::get();
                $stmt = $pdo->query($query = "SELECT * FROM seminare WHERE name = "
                                           . $pdo->quote(Request::get('groupname')));
                if ($stmt->fetch()) {
                    $errors[] = _("Eine Veranstaltung/Studiengruppe mit diesem Namen existiert bereits. Bitte wählen Sie einen anderen Namen");
                }
            }

            if (!Request::get('grouptermsofuse_ok')) {
                $errors[] = _("Sie müssen die Nutzungsbedingungen durch Setzen des Häkchens bei 'Einverstanden' akzeptieren.");
            }

            if ($admin && (!is_array($founders) || !sizeof($founders))) {
                $errors[] = _("Sie müssen mindestens einen Gruppengründer eintragen!");
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
                $sem->institut_id    = Config::GetInstance()->getValue('STUDYGROUP_DEFAULT_INST');
                $mods                = new Modules();
                $bitmask             = 0;
                $sem->admission_type = 0;
                if (Request::get('groupaccess') == 'all') {
                    $sem->admission_prelim = 0;
                } else {
                    $sem->admission_prelim    = 1;
                    $sem->admission_prelim_txt = _("Die ModeratorInnen der Studiengruppe können Ihren Aufnahmewunsch bestätigen oder ablehnen. Erst nach Bestätigung erhalten Sie vollen Zugriff auf die Gruppe.");
                }
                $sem->admission_endtime     = -1;
                $sem->admission_binding     = 0;
                $sem->admission_starttime   = -1;
                $sem->admission_endtime_sem = -1;
                $sem->visible               = 1;

                $semdata                     = new SemesterData();
                $this_semester               = $semdata->getSemesterDataByDate(time());
                $sem->semester_start_time    = $this_semester['beginn'];
                $sem->semester_duration_time = -1;

                if ($admin) {
                    // insert founder(s)
                    foreach ($founders as $username => $fullname) {
                        $cur_user_id = get_userid( $username );
                        $stmt = DBManager::get()->prepare("INSERT INTO seminar_user
                            (seminar_id, user_id, status, gruppe)
                            VALUES (?, ?, 'dozent', 8)");
                        $stmt->execute(array( $sem->id, $cur_user_id ));
                    }

                    $this->founders = null;
                    $this->flash['founders'] = null;
                } else {
                    $user_id = $GLOBALS['auth']->auth['uid'];
                    // insert dozent
                    DBManager::get()->query("INSERT INTO seminar_user SET "
                                        . "seminar_id = '$sem->id', "
                                        . "user_id    = '$user_id', "
                                        . "status     = 'dozent', "
                                        . "gruppe     = 8");
                }

                // now add the studygroup_dozent dozent who's supposed to be invisible
                DBManager::get()->query("INSERT INTO seminar_user "
                                    . "SET seminar_id='$sem->id',"
                                    . " user_id=MD5('studygroup_dozent'), "
                                    . "status='dozent', visible='no'");

                // de-/activate modules
                $mods              = new Modules();
                $bitmask           = 0;
                $available_modules = StudygroupModel::getAvailableModules();
                $groupmodule       = Request::getArray('groupmodule');

                foreach ($groupmodule as $key => $enable) {
                    if ($key=='schedule') continue; // no schedule for studygroups
                    if ($available_modules[$key] && $enable) {
                        $mods->setBit($bitmask, $mods->registered_modules[$key]["id"]);
                    }
                }
                // always activate participants list
                $mods->setBit($bitmask, $mods->registered_modules["participants"]["id"]);

                $sem->modules = $bitmask;
                $sem->store();

                // de-/activate plugins
                $available_plugins = StudygroupModel::getAvailablePlugins();
                $plugin_manager    = PluginManager::getInstance();
                $groupplugin       = Request::getArray('groupplugin');

                foreach ($available_plugins as $key => $name) {
                    $plugin    = $plugin_manager->getPlugin($key);
                    $plugin_id = $plugin->getPluginId();

                    if ($groupplugin[$key] && $name) {
                        $plugin_manager->setPluginActivated($plugin_id, $sem->id, true);
                    } else {
                        $plugin_manager->setPluginActivated($plugin_id, $sem->id, false);
                    }
                }

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

        // if we are permitted to edit the studygroup get some data...
        if ($perm->have_studip_perm('dozent', $id)) {

            $GLOBALS['CURRENT_PAGE'] = getHeaderLine($id) . ' - ' . _('Studiengruppe bearbeiten');
            Navigation::activateItem('/course/admin');

            $sem                     = new Seminar($id);
            $this->sem_id            = $id;
            $this->sem               = $sem;
            $this->available_modules = StudygroupModel::getAvailableModules();
            $this->available_plugins = StudygroupModel::getAvailablePlugins();
            $this->enabled_plugins   = StudygroupModel::getEnabledPlugins($id);
            $this->modules           = new Modules();
            $this->founders          = StudygroupModel::getFounders( $id );

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

            if ($admin && Request::get('search_founder') || Request::get('search_founder_x')) {
                $search_for_founder = Request::get('search_for_founder');

                // do not allow to search with the empty string
                if ($search_for_founder) {

                    // search for the user
                    $pdo = DBManager::get();
                    $search_for_founder = $pdo->quote('%' . $search_for_founder . '%');
                    $stmt = $pdo->query("SELECT user_id, {$GLOBALS['_fullname_sql']['full_rev']} as fullname, username, perms"
                                . " FROM auth_user_md5"
                                . " LEFT JOIN user_info USING (user_id)"
                                . " WHERE username LIKE $search_for_founder"
                                . " OR Vorname LIKE $search_for_founder"
                                . " OR Nachname LIKE $search_for_founder"
                                . " LIMIT 500");
                    while ($data = $stmt->fetch()) {
                        $results_founders[$data['user_id']] = array(
                            'fullname' => $data['fullname'],
                            'username' => $data['username'],
                            'perms'    => $data['perms']
                        );
                    }

                }
                $this->flash['create'] = true;
                $this->flash['results_choose_founders'] = $results_founders;
                $this->flash['request'] = Request::getInstance();
            }

            // add a founder
            else if ($admin && (Request::get('add_founder') || Request::get('add_founder_x'))) {
                if (Request::get('choose_founder')) {
                    $name = Request::get('choose_founder');
                } else {
                    $name = Request::get('choose_founder_x');
                }

                StudygroupModel::addFounder($name, $id );

                $this->flash['success'] = sprintf(_("Der Nutzer %s wurde als Gruppengründer hinzugefügt!"), $name);
            }

            // remove a founder
            else if ( $admin && (Request::get('remove_founder') || Request::get('remove_founder_x'))) {
                if (sizeof($founders) == 1) {
                    $this->flash['edit'] = true;
                    $this->flash['errors'] = array(
                        _("Jede Studiengruppe muss mindestens einen Gruppengründer haben!")
                    );
                } else {
                    if (Request::get('remove_founder')) {
                        $name = Request::get('remove_founder');
                    } else {
                        $name = Request::get('remove_founder_x');
                    }

                    StudygroupModel::removeFounder( $name, $id );

                    $this->flash['success'] = sprintf(_("Der Nutzer %s wurde als Gruppengründer entfernt!"), $name);
                }
            }

            //checks
            else {
                // test whether we have a group name...
                if (!Request::get('groupname')) {
                    $errors[] = _("Bitte Gruppennamen angeben");
                //... if so, test if this is not taken by another group
                } else {
                    $pdo = DBManager::get();
                    $stmt = $pdo->query($query = "SELECT * FROM seminare WHERE name = "
                                               . $pdo->quote(Request::get('groupname'))
                                               . " AND Seminar_id != " . $pdo->quote( $id ));
                    if ($stmt->fetch()) {
                        $errors[] = _("Eine Veranstaltung/Studiengruppe mit diesem Namen existiert bereits. Bitte wählen Sie einen anderen Namen");
                    }
                }
                if (count($errors)) {
                    $this->flash['errors'] =  $errors;
                    $this->flash['edit'] = true;
                // Everything seems fine, let's update the studygroup
                } else {
                    $sem                 = new Seminar($id);
                    $sem->name           = Request::get('groupname');         // seminar-class quotes itself
                    $sem->description    = Request::get('groupdescription');  // seminar-class quotes itself
                    $sem->read_level     = 1;
                    $sem->write_level    = 1;
                    $sem->admission_type = 0;

                    if (Request::get('groupaccess') == 'all') {
                        $sem->admission_prelim = 0;
                    } else {
                        $sem->admission_prelim = 1;
                        $sem->admission_prelim_txt = _("Die ModeratorInnen der Studiengruppe können Ihren Aufnahmewunsch bestätigen oder ablehnen. Erst nach Bestätigung erhalten Sie vollen Zugriff auf die Gruppe.");
                    }

                    // get the current bitmask
                    $mods = new Modules();
                    $bitmask = $sem->modules;

                    // de-/activate modules
                    $available_modules = StudygroupModel::getAvailableModules();

                    foreach (array_keys($available_modules) as $key){
                        if($key == 'participants') continue;
                        if ($_REQUEST['groupmodule'][$key]) {
                            $mods->setBit($bitmask, $mods->registered_modules[$key]["id"]);
                        } else {
                            $mods->clearBit($bitmask, $mods->registered_modules[$key]["id"]);
                        }
                    }

                    $sem->modules = $bitmask;
                    $sem->store();

                    // de-/activate plugins
                    $available_plugins = StudygroupModel::getAvailablePlugins();
                    $plugin_manager    = PluginManager::getInstance();
                    $groupplugin       = Request::getArray('groupplugin');

                    foreach ($available_plugins as $key => $name) {
                        $plugin = $plugin_manager->getPlugin($key);
                        $plugin_id = $plugin->getPluginId();
                        if ($groupplugin[$key] && $name) {
                            $plugin_manager->setPluginActivated($plugin_id, $id, true);
                        } else {
                            $plugin_manager->setPluginActivated($plugin_id, $id, false);
                        }
                    }
                }
            }
        }

        if (!$this->flash['errors']) {
             // Everything seems fine
            $this->flash['success'] = _("Die Änderungen wurden erfolgreich übernommen.");
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
        $GLOBALS['CURRENT_PAGE'] = getHeaderLine($id) . ' - ' . _("TeilnehmerInnen");
        Navigation::activateItem('/course/members');

        $sem          = new Seminar($id);
        $this->page   = $page;
        $this->anzahl = StudygroupModel::countMembers($id);

        if($this->page < 1 || $this->page > ceil($this->anzahl/ELEMENTS_PER_PAGE)) $this->page = 1;

        $this->lower_bound      = ($this->page - 1) * ELEMENTS_PER_PAGE;
        $this->cmembers         = StudygroupModel::getMembers($id, $this->lower_bound, ELEMENTS_PER_PAGE);
        usort($this->cmembers, array('StudygroupModel','compare_status'));
        $this->groupname        = $sem->name;
        $this->sem_id           = $id;
        $this->groupdescription = $sem->description;
        $this->moderators       = $sem->getMembers('dozent');
        unset($this->moderators[md5('studygroup_dozent')]);
        $this->tutors           =  $sem->getMembers('tutor');
        $this->accepted         = $sem->getAdmissionMembers('accepted');
        $this->rechte           = $GLOBALS['perm']->have_studip_perm("tutor", $id);
    }

    /**
     * offers specific member functions wrt perms
     *
     * @param string id of a studypgroup
     * @param string user username of a user
     * @param string action that has to be performed
     * @param string status if applicable (e.g. tutor)
     *
     * @return void
     */
    function edit_members_action($id, $user, $action, $status = '', $studipticket = false)
    {   
        global $perm;
        if ($perm->have_studip_perm('tutor', $id)) {

            if (!$action) {
                $this->flash['success'] = _("Es wurde keine korrekte Option gewählt.");
            } elseif ($action == 'accept') {
                StudygroupModel::accept_user($user,$id);
                $this->flash['success'] = sprintf(_("Der Nutzer %s wurde akzeptiert."), get_fullname_from_uname($user));
            } elseif ($action == 'deny') {
                StudygroupModel::deny_user($user,$id);
                $this->flash['success'] = sprintf(_("Der Nutzer %s wurde nicht akzeptiert."), get_fullname_from_uname($user));
            } elseif ($action == 'add_invites') {
                if (Request::submitted('search_member')) {
                    $search_for_member = Request::get('search_for_member');
                    if ($search_for_member) {
                        // search for the user
                        $pdo = DBManager::get();
                        $search_for_member = $pdo->quote('%' . $search_for_member . '%');
                        $stmt = $pdo->query("SELECT auth_user_md5.user_id, {$GLOBALS['_fullname_sql']['full_rev']} as fullname, username, perms "
                                    . "FROM auth_user_md5 "
                                    . "LEFT JOIN user_info ON (auth_user_md5.user_id = user_info.user_id) "
                                    . "LEFT JOIN seminar_user ON (auth_user_md5.user_id = seminar_user.user_id AND seminar_user.Seminar_id = '$id') "
                                    . "WHERE perms  NOT IN ('root', 'admin') "
                                    . "AND seminar_user.Seminar_id IS NULL "
                                    . "AND " . get_vis_query()
                                    . " AND (username LIKE $search_for_member OR Vorname LIKE $search_for_member "
                                    . "OR Nachname LIKE $search_for_member)"
                                    . "LIMIT 50");
                        while ($data = $stmt->fetch()) {
                            $results_members[$data['user_id']] = array(
                                'fullname' => $data['fullname'],
                                'username' => $data['username'],
                                'perms'    => $data['perms']
                            );
                        }
                    }
                    if (isset($results_members)) {
                        $count_members = sizeof($results_members);
                        $msg .= $count_members == 1 ? _("Es wurde 1 NutzerIn gefunden.") : sprintf(_("Es wurden %s NutzerInnen gefunden."), $count_members);
                        if ($count_members == 50){
                            $msg = sprintf(_("Es werden immer nur die ersten %s Treffer angezeigt! Bitte konkretisieren sie gegebenenfalls den Suchbegriff."), $count_members);
                        }
                        $this->flash['success'] = $msg;
                    } else {
                        $this->flash['info'] = sprintf(_("Der Suchbegriff %s ergab keine Treffer."), Request::get('search_for_member'));
                    }
                    $this->flash['results_choose_members'] = $results_members;
                    $this->flash['request'] = Request::getInstance();

                }
                if (Request::get('choose_member') || Request::get('add_member_x')) {
                    $msg = new Messaging();
                    $receiver = Request::get('choose_member');
                    $sem = new Seminar($id);
                    $u_name = get_fullname_from_uname(get_username($user));
                    $message = sprintf(_("%s möchte Sie auf die Studiengruppe %s aufmerksam machen. Klicken Sie auf den untenstehenden Link "
                             . "um direkt zur Studiengruppe zu gelangen.\n\n %s"),
                             $u_name, $sem->name, URLHelper::getlink("dispatch.php/course/studygroup/details/" . $id, array('cid' => NULL)));
                    $subject = _("Sie wurden in eine Studiengruppe eingeladen");
                    $msg->insert_message($message, $receiver,'', '', '', '', '', $subject);
                    $this->flash['success'] = sprintf(_("Der Nutzer %s wurde in die Studiengruppe eingeladen."), get_fullname_from_uname($receiver));
                }
            }
            if ($perm->have_studip_perm('dozent', $id) && $action != 'add_invites') {
                if(!$perm->have_studip_perm('dozent',$id,get_userid($user))) {
                    if ($action == 'promote') {
                        StudygroupModel::promote_user($user,$id,$status);
                        $this->flash['success'] = sprintf(_("Der Status des Nutzer %s wurde geändert."), get_fullname_from_uname($user));
                    } elseif ($action == 'remove') {
                        $this->flash['question'] = sprintf(_("Möchten Sie wirklich den Nutzer %s aus der Studiengruppe entfernen?"), get_fullname_from_uname($user));
                        $this->flash['candidate'] = $user;
                       
                    } elseif ($action == 'remove_approved' && check_ticket($studipticket)) {
                        StudygroupModel::remove_user($user,$id);
                        $this->flash['success'] = sprintf(_("Der Nutzer %s wurde aus der Studiengruppe entfernt."), get_fullname_from_uname($user));
                    }
                } else {
                    $this->flash['messages'] = array(
                        'error' => array (
                            'title' => _("Jede Studiengruppe muss mindestens einen Gruppengründer haben!")
                        )
                    );
                }
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
                $this->redirect('course/studygroup/new');
            } else if (!$approveDelete) {
                $template = $GLOBALS['template_factory']->open('shared/question');

                $template->set_attribute('approvalLink', $this->url_for('course/studygroup/delete/' . $id . '/true/' . get_ticket()));
                $template->set_attribute('disapprovalLink', $this->url_for('course/studygroup/edit/' . $id));
                $template->set_attribute('question', _("Sind Sie sicher, dass Sie diese Studiengruppe löschen möchten?"));

                $this->flash['question'] = $template->render();
                $this->redirect('course/studygroup/edit/' . $id);
            } else {
                $this->redirect(URLHelper::getURL('seminar_main.php?auswahl=' . $id));
            }
        } else {
            $this->redirect(URLHelper::getURL('seminar_main.php?auswahl=' . $id));
        }

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
        $GLOBALS['HELP_KEYWORD'] = 'Admin.Studiengruppen';

        // get available modules
        $modules = StudygroupModel::getInstalledModules() + StudygroupModel::getInstalledPlugins();
        $enabled = StudygroupModel::getAvailability($modules);

        // get institutes
        $institutes = StudygroupModel::getInstitutes();
        $default_inst = Config::GetInstance()->getValue('STUDYGROUP_DEFAULT_INST');

        // Nutzungsbedingungen
        $terms = Config::GetInstance()->getValue('STUDYGROUP_TERMS');

        if ($this->flash['institute']) {
            $default_inst = $this->flash['institute'];
        }
        if ($this->flash['modules']) {
            foreach ($this->flash['modules'] as $module => $status) {
                $enabled[$module] = ($status == 'on') ? true : false;
            }
        }
        if ($this->flash['terms']) $terms = $this->flash['terms'];

        $GLOBALS['CURRENT_PAGE'] = _('Verwaltung studentischer Arbeitsgruppen');
        Navigation::activateItem('/admin/config/studygroup');

        $db = DBManager::get()->query("SELECT COUNT(*) as c FROM seminare"
                . " WHERE status IN ('"
                . implode("', '", studygroup_sem_types()) . "')");

        // set variables for view
        $this->can_deactivate = ($db->fetchColumn() != 0) ? false : true;
        $this->current_page   = _("Verwaltung erlaubter Module und Plugins für Studiengruppen");
        $this->configured     = count(studygroup_sem_types()) > 0;
        $this->modules        = $modules;
        $this->enabled        = $enabled;
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
        $GLOBALS['HELP_KEYWORD'] = 'Admin.Studiengruppen';

        foreach (Request::getArray('modules') as $key => $value) {
            if ($value=='invalid') {
                $errors[] = _("Sie müssen sich bei jedem Module entscheiden, ob es zur Verfügung stehen soll oder nicht!");
                break;
            }
        }

        if (Request::quoted('institute') == 'invalid') {
            $errors[] = _("Bitte wählen Sie eine Einrichtung aus, der die Studiengruppen zugeordnet werden sollen!");
        }

        if (!Request::quoted('terms') || Request::quoted('terms') == 'invalid') {
            $errors[] = _("Bitte tragen Sie Nutzungsbedingungen ein!");
        }

        if ($errors) {
            $this->flash['messages'] = array('error' => array('title' => 'Die Studiengruppen konnten nicht aktiviert werden!', 'details' =>  $errors));
            $this->flash['institute'] = Request::get('institute');
            $this->flash['modules']   = Request::getArray('modules');
            $this->flash['terms']     = Request::get('terms');
        }

        if (!$errors) {
            $cfg=new Config("STUDYGROUPS_ENABLE");
            if ($cfg->getValue()==FALSE && count(studygroup_sem_types()) > 0) {
                $cfg->setValue(TRUE,"STUDYGROUPS_ENABLE","Studiengruppen");
                $this->flash['success'] = _("Die Studiengruppen wurden aktiviert.");
            }

            if (is_array($_REQUEST['modules']) ) {
                // $config_string contains modul/pluginname=0/1|...
                foreach ($_REQUEST['modules'] as $key => $value) {
                    if (in_array($key, array('participants','schedule'))) continue;
                    $config_string[] = $key . ':' . ($value == 'on' ? '1' : '0');
                }
                $config_string[] = 'participants:1';
                $config_string[] = 'schedule:0';

                Config::GetInstance()->setValue(implode('|', $config_string), 'STUDYGROUP_SETTINGS');
                Config::GetInstance()->setValue( Request::quoted('institute'), 'STUDYGROUP_DEFAULT_INST');
                Config::GetInstance()->setValue( Request::quoted('terms'), 'STUDYGROUP_TERMS');
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
        $GLOBALS['HELP_KEYWORD'] = 'Admin.Studiengruppen';

        $db = DBManager::get()->query("SELECT COUNT(*) as c FROM seminare"
                . " WHERE status IN ('" . implode("', '", studygroup_sem_types()) . "')");
        if (($count = $db->fetchColumn()) != 0) {
            $this->flash['messages'] = array('error' => array(
                'title' => sprintf(_("Sie können die Studiengruppen nicht deaktivieren, da noch %s Studiengruppen vorhanden sind!"), $count)
            ));
        } else {
            $cfg = new Config();
            $cfg->setValue(FALSE, "STUDYGROUPS_ENABLE","Studiengruppen");
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
        $sem        = new Seminar($id);
        $source     = 'dispatch.php/course/studygroup/member/' . $id;
        if (strlen($sem->getName()) > 32) //cut subject if to long
            $subject = sprintf(_("[Studiengruppe: %s...]"),substr($sem->getName(), 0, 30));
        else
            $subject = sprintf(_("[Studiengruppe: %s]"),$sem->getName());
                

        $this->redirect(URLHelper::getURL('sms_send.php', array('sms_source_page' => $source, 'course_id' => $id, 'emailrequest' => 1, 'subject' => $subject, 'filter' => 'all')));
    }


}
