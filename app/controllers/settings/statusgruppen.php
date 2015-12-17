<?php
/**
 * Settings_StatusgruppenController - Administration of all user and
 * statusgruppen related settings
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @since       2.4
 */

require_once 'settings.php';

class Settings_StatusgruppenController extends Settings_SettingsController
{
    /**
     * Set up this controller and define the infobox
     *
     * @param String $action Name of the action to be invoked
     * @param Array  $args   Arguments to be passed to the action method
     */
    public function before_filter(&$action, &$args)
    {
        if ($action === 'verify') {
            $action = 'index';
        }

        parent::before_filter($action, $args);

        require_once 'lib/statusgruppe.inc.php';

        PageLayout::setHelpKeyword('Basis.HomepageUniversit�reDaten');
        PageLayout::setTitle(_('Einrichtungsdaten bearbeiten'));
        Navigation::activateItem('/profile/edit/statusgruppen');
        SkipLinks::addIndex(_('Einrichtungsdaten bearbeiten'), 'layout_content', 100);

        Sidebar::get()->setImage('sidebar/group-sidebar.png');
    }

    /**
     * Displays the statusgruppen of a user.
     *
     * @param mixed $verify_action Optional name of an action to be verified
     * @param mixed $verify_id     Optional id that belongs to the action to
     *                             be verified
     */
    public function index_action($verify_action = null, $verify_id = null)
    {
        $all_rights = false;
        if ($this->user->username != $GLOBALS['user']->username) {
            $query = "SELECT Institut_id
                      FROM Institute
                      WHERE fakultaets_id = ? AND fakultaets_id != Institut_id
                      ORDER BY Name";
            $inner_statement = DBManager::get()->prepare($query);

            $parameters = array();
            if ($GLOBALS['perm']->have_perm('root')) {
                $all_rights = true;
                $query = "SELECT Institut_id, Name, 1 AS is_fak
                          FROM Institute
                          WHERE Institut_id = fakultaets_id
                          ORDER BY Name";
            } elseif ($GLOBALS['perm']->have_perm('admin')) {
                $query = "SELECT Institut_id, Name, b.Institut_id = b.fakultaets_id AS is_fak
                          FROM user_inst AS a
                          LEFT JOIN Institute AS b USING (Institut_id)
                          WHERE a.user_id = ? AND a.inst_perms = 'admin'
                          ORDER BY is_fak, Name";
                $parameters[] = $GLOBALS['user']->id;
            } else {
                $query = "SELECT a.Institut_id, Name
                          FROM user_inst AS a
                          LEFT JOIN Institute AS b USING (Institut_id)
                          WHERE inst_perms IN ('tutor', 'dozent') AND user_id = ?
                          ORDER BY Name";
                $parameters[] = $GLOBALS['user']->id;
            }

            $statement = DBManager::get()->prepare($query);
            $statement->execute($parameters);
            $institutes = $statement->fetchAll(PDO::FETCH_ASSOC);

            $admin_insts = array();
            foreach ($institutes as $institute) {
                $institute['groups'] = GetAllStatusgruppen($institute['Institut_id']) ?: array();

                if ($institute['is_fak']) {
                    $stmt = DBManager::get()->prepare("SELECT Institut_id, Name FROM Institute WHERE fakultaets_id = ? AND Institut_id != fakultaets_id ORDER BY Name");
                    $stmt->execute(array($institute['Institut_id']));
                    $institute['sub'] = $stmt->fetchGrouped(PDO::FETCH_ASSOC);
                    foreach ($institute['sub'] as $id => $sub) {
                        $sub['groups'] = GetAllStatusgruppen($id) ?: array();
                        $institute['sub'][$id] = $sub;
                    }
                }

                $admin_insts[] = $institute;
            }
        } else {
            $all_rights = true;
        }

        // get the roles the user is in
        $institutes = array();
        foreach ($this->about->user_inst as $inst_id => $details) {
            if ($details['inst_perms'] != 'user') {
                $institutes[$inst_id] = $details;

                $roles = GetAllStatusgruppen($inst_id, $this->user->user_id, true);
                $institutes[$inst_id]['roles'] = $roles ?: array();

                $institutes[$inst_id]['flattened'] = array_filter(Statusgruppe::getFlattenedRoles($roles), function ($role) {
                    return $role['user_there'];
                });

                $user_id = $this->user->user_id;
                $datafields = array();
                foreach ($institutes[$inst_id]['flattened'] as $role_id => $role) {
                    $datafields[$role_id] = DataFieldEntry::getDataFieldEntries(array($this->user->user_id, $role_id)) ?: array();
                }
                $institutes[$inst_id]['datafields'] = $datafields;
            }
        }

        // template for tree-view of roles, layout for infobox-location and content-variables
        $this->open       = $_SESSION['edit_about_data']['open']; // the ids of the currently opened statusgroups
        $this->institutes = $institutes;

        $this->verify_action  = $verify_action;
        $this->verify_id      = $verify_id;

        // data for edit_about_add_person_to_role
        $this->admin_insts     = $admin_insts;

        $this->locked = !$this->shallChange('', 'institute_data');
        if ($this->locked) {
            $message = LockRules::getObjectRule($this->user->user_id)->description;
            if ($message) {
                $this->reportInfo($message);
            }
        }
    }

    /**
     * Set defaults for a single datafield of a statusgruppe.
     *
     * @param String $inst_id Id of the institute in question
     * @param String $role_id Id of the statusgruppe in question
     * @param String $datafield_id Id of the datafield in question
     * @param bool $state Indicates whether the defaults should be used or not
     */
    public function default_action($inst_id, $role_id, $datafield_id, $state)
    {
        $value = 'default_value';
        if (!$state) {
            $defaults = DataFieldEntry::getDataFieldEntries(array($this->user->user_id, $inst_id));
            $value = $defaults[$datafield_id]->getValue();
        }

        $entry = new DatafieldEntryModel($datafield_id);
        $entry->range_id     = $this->user->user_id;
        $entry->sec_range_id = $role_id;
        $entry->content      = $value;
        $entry->store();

        $this->redirect('settings/statusgruppen#' . $role_id);
    }

    /**
     * Set defaults for all datafields of a statusgruppe.
     *
     * @param String $role_id Id of the statusgruppe in question
     * @param bool $state Indicates whether the defaults should be used or not
     */
    public function defaults_action($role_id, $state)
    {
        MakeDatafieldsDefault($this->user->user_id, $role_id, $state ? 'default_value' : '');

        $this->redirect('settings/statusgruppen#' . $role_id);
    }

    /**
     * Assign/add a user to a statusgruppe.
     */
    public function assign_action()
    {
        $this->check_ticket();

        $role_id  = Request::option('role_id');
        if ($role_id) {
            $group    = new Statusgruppe($role_id);
            $range_id = $group->getRange_id();

            $group = new Statusgruppe($range_id);
            while ($group->getRange_id()) {
                $range_id = $group->getRange_id();
                $group    = new Statusgruppe($range_id);
            }

            if (InsertPersonStatusgruppe($this->user->user_id, $role_id)) {
                $globalperms = get_global_perm($this->user->user_id);

                $query = "INSERT IGNORE INTO user_inst (Institut_id, user_id, inst_perms)
                          VALUES (?, ?, ?)
                          ON DUPLICATE KEY UPDATE inst_perms = VALUES(inst_perms)";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($range_id, $this->user->user_id, $globalperms));

                if ($statement->rowCount() == 1) {
                    log_event('INST_USER_ADD', $range_id, $this->user->user_id, $globalperms);
                } else if ($statement->rowCount() == 2) {
                    log_event('INST_USER_STATUS', $range_id, $this->user->user_id, $globalperms);
                }

                checkExternDefaultForUser($this->user->user_id);

                $_SESSION['edit_about_data']['open'] = $role_id;
                $this->reportSuccess(_('Die Person wurde in die ausgew�hlte Gruppe eingetragen!'));
            } else {
                $this->reportError(_('Fehler beim Eintragen in die Gruppe!'));
            }
        }

        $this->redirect('settings/statusgruppen#' . $role_id);
    }

    /**
     * Removes a user from a statusgruppe.
     *
     * @param String $id Id of the statusgruppe in question
     * @param bool $verified Indicates whether the action has been verified
     */
    public function delete_action($id, $verified = false)
    {
        if ($verified) {
            $this->check_ticket();

            $query = "DELETE FROM statusgruppe_user WHERE user_id = ? AND statusgruppe_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($this->user->user_id, $id));

            if ($statement->rowCount() > 0) {
                $this->reportSuccess(_('Die Person wurde aus der ausgew�hlten Gruppe gel�scht!'));
            }
        }

        $this->redirect('settings/statusgruppen');
    }

    /**
     * Moves a specific statusgruppe into the given direction.
     *
     * @param String $id Id of the statusgruppe in question
     * @param String $direction Either 'up' or 'down'
     */
    public function move_action($id, $direction)
    {
        if (in_array($this->about->check, words('user admin'))) {
            $query = "SELECT Institut_id
                      FROM user_inst
                      WHERE user_id = ? AND inst_perms != 'user'
                      ORDER BY priority ASC";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($this->user->user_id));
            $institutes = $statement->fetchAll(PDO::FETCH_COLUMN);
            $priorities = array_flip($institutes);

            $changed = false;
            $priority = $priorities[$id];
            if ($direction === 'down' && $priority + 1 < count($priorities)) {
                $priorities[$id] = $priority + 1;
                $priorities[$institutes[$priority + 1]] = $priority;
                $changed = true;
            } else if ($direction === 'up' && $priority > 0) {
                $priorities[$id] = $priority - 1;
                $priorities[$institutes[$priority - 1]] = $priority;
                $changed = true;
            }

            if ($changed) {
                $query = "UPDATE user_inst
                          SET priority = ?
                          WHERE user_id = ? AND Institut_id = ?";
                $statement = DBManager::get()->prepare($query);

                foreach ($priorities as $id => $priority) {
                    $statement->execute(array(
                        $priority,
                        $this->user->user_id,
                        $id
                    ));
                }
                $this->reportSuccess(_('Reihenfolge wurde ge�ndert'));
            }
        }
        $this->redirect('settings/statusgruppen#' . $id);
    }

    /**
     * Toggles whether a certain statusgruppe is open or not.
     *
     * @param String $id Id of the statusgruppe to be toggled.
     * @param mixed $open Optional new state (otherwise it's toggled)
     */
    public function switch_action($id, $open = null)
    {
        if ($open === null) {
            $open = ($_SESSION['edit_about_data']['open'] !== $id);
        }
        $_SESSION['edit_about_data']['open'] = $open ? $id : '';

        $this->redirect('settings/statusgruppen#' . $id);
    }

    /**
     * Stores the statusgruppen of a user.
     */
    public function store_action($type, $id)
    {
        if ($type === 'institute') {
            if ($status = Request::option('status')) {
                $query = "SELECT inst_perms FROM user_inst WHERE user_id = ? AND Institut_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($this->user->user_id, $id));
                $perms = $statement->fetchColumn();

                if (($status != $perms) && in_array($status, $this->about->allowedInstitutePerms())) {
                    $query = "UPDATE user_inst SET inst_perms = ? WHERE user_id = ? AND Institut_id = ?";
                    $statement = DBManager::get()->prepare($query);
                    $statement->execute(array(
                        $status,
                        $this->user->user_id,
                        $id
                    ));

                    log_event('INST_USER_STATUS', $id, $this->user->user_id, $perms .' -> '. $status);

                    $this->reportSuccess(_('Der Status wurde ge�ndert!'));
                }
            }

            if ($this->shallChange('', 'institute_data')) {
                $query = "UPDATE user_inst
                          SET raum = ?, sprechzeiten = ?, Telefon = ?, Fax = ?
                          WHERE Institut_id = ? AND user_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array(
                    Request::get('raum'),
                    Request::get('sprech'),
                    Request::get('tel'),
                    Request::get('fax'),
                    $id,
                    $this->user->user_id
                ));
                if ($statement->rowCount() > 0) {
                    $this->reportSuccess(_('Ihre Daten an der Einrichtung %s wurden ge�ndert.'), Request::get('name'));

                    setTempLanguage($this->user->user_id);
                    $this->postPrivateMessage(_("Ihre Daten an der Einrichtung %s wurden ge�ndert.\n"), Request::get('name'));
                    restoreLanguage();
                }
            }

            if ($default_institute = Request::int('default_institute', 0)) {
                $query = "UPDATE user_inst SET externdefault = 0 WHERE user_id = ?";
                $statement = DBManager::get()->prepare($query);
                $statement->execute(array($this->user->user_id));
            }

            $query = "UPDATE user_inst
                      SET externdefault = ?, visible = ?
                      WHERE Institut_id = ? AND user_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array(
                $default_institute,
                Request::int('invisible', 0) ? 0 : 1,
                $id,
                $this->user->user_id
            ));
        }
        if (in_array($type, words('institute role'))) {
            if ($datafields = Request::getArray('datafields')) {
                $errors = array();

                foreach ($datafields as $key => $value) {
                    $struct = new DataField($key);
                    $entry  = DataFieldEntry::createDataFieldEntry($struct, array($this->user->user_id, $id));
                    $entry->setValueFromSubmit($value);
                    if ($entry->isValid()) {
                        $entry->store();
                    } else {
                        $errors[] = sprintf(_('Fehlerhafter Eintrag im Feld <em>%s</em>: %s (Eintrag wurde nicht gespeichert)'),
                                            $entry->getName(),
                                            $entry->getDisplayValue());
                    }
                }
            }

            if (!empty($errors)) {
                $this->reportErrorWithDetails(_('Bitte �berpr�fen Sie Ihre Eingabe.'), $errors);
            }
        }
        $this->redirect('settings/statusgruppen#' . $id);
    }
}
