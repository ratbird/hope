<?php
# Lifter010: TODO
/**
 * plugin.php - role administration controller
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Dennis Reil
 * @author      Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 * @package     admin
 */

require_once 'app/controllers/authenticated_controller.php';

class Admin_RoleController extends AuthenticatedController
{
    /**
     * Common tasks for all actions.
     */
    public function before_filter(&$action, &$args)
    {
        global $perm;

        parent::before_filter($action, $args);

        // user must have root permission
        $perm->check('root');

        // set page title and navigation
        PageLayout::setTitle(_('Verwaltung von Rollen'));
        Navigation::activateItem('/admin/config/roles');
    }

    /**
     * Validate ticket (passed via request environment).
     * This method always checks $_REQUEST['ticket'].
     *
     * @throws InvalidArgumentException  if ticket is not valid
     */
    private function check_ticket()
    {
        if (!check_ticket(Request::option('ticket'))) {
            throw new InvalidArgumentException(_('Das Ticket für diese Aktion ist ungültig.'));
        }

    }

    /**
     * Get statistics about the given list of roles. This includes
     * the number of users and the number of plugins with each role.
     *
     * @param array     list of Role objects
     */
    private function get_role_stats($roles)
    {
        // Prepare count users statement
        $query = "SELECT COUNT(*)
                  FROM roles_user
                  WHERE roleid = ? AND userid != 'nobody'";
        $users_statement = DBManager::get()->prepare($query);

        // Prepare count plugins statement
        $query = "SELECT COUNT(*)
                  FROM roles_plugins
                  WHERE roleid = ?";
        $plugins_statement = DBManager::get()->prepare($query);

        foreach ($roles as $role) {
            $roleid = $role->getRoleid();

            $users_statement->execute(array($roleid));
            $stats[$roleid]['users'] = $users_statement->fetchColumn();
            $users_statement->closeCursor();

            $plugins_statement->execute(array($roleid));
            $stats[$roleid]['plugins'] = $plugins_statement->fetchColumn();
            $plugins_statement->closeCursor();
        }

        return $stats;
    }

    /**
     * Display a list of all existing roles and some statistics.
     */
    public function index_action()
    {
        $this->roles = RolePersistence::getAllRoles();
        $this->stats = $this->get_role_stats($this->roles);
    }

    /**
     * Create a new role.
     */
    public function create_role_action()
    {
        $name = Request::get('name');

        $this->check_ticket();

        if ($name != '') {
            $role = new Role();
            $role->setRolename($name);
            RolePersistence::saveRole($role);

            $this->flash['success'] = sprintf(_('Die Rolle "%s" wurde angelegt.'), htmlReady($name));
        } else {
            $this->flash['error'] = _('Sie haben keinen Namen eingegeben.');
        }

        $this->redirect('admin/role');
    }

    /**
     * Ask for confirmation from the user before deleting a role.
     *
     * @param integer   id of role to delete
     */
    public function ask_remove_role_action($roleid)
    {
        $this->delete_role = $roleid;
        $this->roles = RolePersistence::getAllRoles();
        $this->stats = $this->get_role_stats($this->roles);

        $this->render_action('index');
    }

    /**
     * Completely delete a role (including all its assignments).
     *
     * @param integer   id of role to delete
     */
    public function remove_role_action($roleid)
    {
        $this->check_ticket();

        $roles = RolePersistence::getAllRoles();
        RolePersistence::deleteRole($roles[$roleid]);

        $this->flash['success'] = _('Die Rolle und alle dazugehörigen Zuweisungen wurden gelöscht.');
        $this->redirect('admin/role');
    }

    /**
     * Search for users containing the given string in either
     * first name, last name oder user name.
     *
     * @param string    text to match agaist
     *
     * @return array    list of StudIPUser objects
     */
    private function search_user($searchtxt)
    {
        $searchtxt = '%' . $searchtxt . '%';
        $stmt = DBManager::get()->prepare(
          'SELECT user_id FROM auth_user_md5 '.
          'WHERE username LIKE ? OR Vorname LIKE ? OR Nachname LIKE ? '.
          'ORDER BY Vorname, Nachname, username');

        $stmt->execute(array($searchtxt, $searchtxt, $searchtxt));
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $users = array();

        foreach ($result as $row) {
            $users[$row['user_id']] = new StudIPUser($row['user_id']);
        }

        return $users;
    }

    /**
     * Display all roles assigned to a particular user.
     *
     * @param string    user id (optional)
     */
    public function assign_role_action($userid = NULL)
    {
        $usersel = Request::option('usersel', $userid);

        // user search was started
        if (Request::submitted('search')) {
            $username = Request::get('username');

            if ($username == '') {
                $this->error = _('Es wurde kein Suchwort eingegeben.');
            } else {
                $this->users = $this->search_user($username);

                if (empty($this->users)) {
                    $this->error = _('Es wurde kein Benutzer gefunden.');
                    $this->username = $username;
                }
            }
        }

        // a user was selected
        if (isset($usersel)) {
            $this->users[$usersel] = new StudIPUser($usersel);
            $this->currentuser = $this->users[$usersel];
            $this->assignedroles = $this->currentuser->getAssignedRoles();
            $this->all_userroles = $this->currentuser->getAssignedRoles(true);
            $this->roles = RolePersistence::getAllRoles();
        }
    }

    /**
     * Change the roles assigned to a particular user.
     *
     * @param string    user id
     */
    public function save_role_action($userid)
    {
        $roles = RolePersistence::getAllRoles();
        $selecteduser = new StudIPUser($userid);

        $this->check_ticket();

        if (Request::submitted('assign_role')) {
            // assign roles
            foreach (Request::intArray('rolesel') as $selroleid) {
                $role = $roles[$selroleid];
                RolePersistence::assignRole($selecteduser, $role);
            }
        } else if (Request::submitted('remove_role')) {
            // delete role assignment
            foreach (Request::intArray('assignedroles') as $roleid) {
                $role = $roles[$roleid];
                RolePersistence::deleteRoleAssignment($selecteduser, $role);
            }
        }

        $this->flash['success'] = _('Die Rollenzuweisungen wurden gespeichert.');
        $this->redirect('admin/role/assign_role/'.$userid);
    }

    /**
     * Display all roles assigned to a particular plugin.
     *
     * @param integer   plugin id (optional)
     */
    public function assign_plugin_role_action($pluginid = NULL)
    {
        $pluginid = Request::int('pluginid', $pluginid);

        $this->plugins = PluginManager::getInstance()->getPluginInfos();
        $this->assigned = RolePersistence::getAssignedPluginRoles($pluginid);
        $this->roles = RolePersistence::getAllRoles();
        $this->pluginid = $pluginid;
    }

    /**
     * Change the roles assigned to a particular plugin.
     *
     * @param integer   plugin id
     */
    public function save_plugin_role_action($pluginid)
    {
        $this->check_ticket();

        if (Request::submitted('assign_role')) {
            // assign roles
            $selroles = Request::intArray('rolesel');
            RolePersistence::assignPluginRoles($pluginid, $selroles);
        } else if (Request::submitted('remove_role')) {
            // delete role assignment
            $delassignedrols = Request::intArray('assignedroles');
            RolePersistence::deleteAssignedPluginRoles($pluginid, $delassignedrols);
        }

        $this->flash['success'] = _('Die Rechteeinstellungen wurden gespeichert.');
        $this->redirect('admin/role/assign_plugin_role/'.$pluginid);
    }

    /**
     * Check role access permission for the given plugin.
     *
     * @param array     plugin meta data
     * @param integer   role id of role
     */
    private function check_role_access($plugin, $role_id)
    {
        $plugin_roles = RolePersistence::getAssignedPluginRoles($plugin['id']);

        foreach ($plugin_roles as $plugin_role) {
            if ($plugin_role->getRoleid() == $role_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Display a list of user and role assignments for a given role.
     *
     * @param integer   role id (optional)
     */
    public function show_role_action($roleid = NULL)
    {
        $db = DBManager::get();
        $roleid = Request::int('role', $roleid);

        $this->roles = RolePersistence::getAllRoles();

        if (isset($roleid)) {
            $sql = "SELECT *
                    FROM auth_user_md5
                    JOIN roles_user ON userid = user_id
                    WHERE roleid = ?
                    ORDER BY Nachname, Vorname";
            $statement = DBManager::get()->prepare($sql);
            $statement->execute(array($roleid));

            $users = $statement->fetchAll(PDO::FETCH_ASSOC);
            $plugins = PluginManager::getInstance()->getPluginInfos();

            foreach ($plugins as $id => $plugin) {
                if (!$this->check_role_access($plugin, $roleid)) {
                    unset($plugins[$id]);
                }
            }

            $this->users = $users;
            $this->plugins = $plugins;
            $this->role = $this->roles[$roleid];
            $this->roleid = $roleid;
        }
    }
}
