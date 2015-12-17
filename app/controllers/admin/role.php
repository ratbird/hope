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

class Admin_RoleController extends AuthenticatedController
{
    /**
     *
     */
    public static function getRole($role_id)
    {
        static $roles = null;
        if ($roles === null) {
            $roles = RolePersistence::getAllRoles();
        }

        return $roles[$role_id];
    }

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

        $this->setSidebar($action);
    }

    /**
     * Validate ticket (passed via request environment).
     * This method always checks Request::quoted('ticket').
     *
     * @throws InvalidArgumentException  if ticket is not valid
     */
    private function check_ticket()
    {
        if (!check_ticket(Request::option('ticket'))) {
            throw new InvalidArgumentException(_('Das Ticket f�r diese Aktion ist ung�ltig.'));
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
        $query = "SELECT COUNT(DISTINCT userid)
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
    public function add_action()
    {
        PageLayout::setTitle(_('Neue Rolle anlegen'));

        if (Request::isPost()) {
            $this->check_ticket();

            $name = Request::get('name');
            $name = trim($name);

            if ($name !== '') {
                $role = new Role();
                $role->setRolename($name);
                RolePersistence::saveRole($role);

                $message = sprintf(_('Die Rolle "%s" wurde angelegt.'), htmlReady($name));
            } else {
                $message = _('Sie haben keinen Namen eingegeben.');
            }
            PageLayout::postMessage(MessageBox::success($message));

            $this->redirect('admin/role');
        }
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

        $role = self::getRole($roleid);
        RolePersistence::deleteRole($role);

        $message = _('Die Rolle und alle dazugeh�rigen Zuweisungen wurden gel�scht.');
        PageLayout::postMessage(MessageBox::success($message));

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

                if (count($this->users) === 0) {
                    $this->error = _('Es wurde keine Person gefunden.');
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
            foreach ($this->assignedroles as $role) {
                $institutes = SimpleCollection::createFromArray(Institute::findMany(RolePersistence::getAssignedRoleInstitutes($usersel, $role->getRoleid())));
                $this->assignedroles_institutes[$role->getRoleid()] = $institutes->orderBy('name')->pluck('name');
            }
        }
    }

    /**
     * Change the roles assigned to a particular user.
     *
     * @param string    user id
     */
    public function save_role_action($userid)
    {
        $selecteduser = new StudIPUser($userid);

        $this->check_ticket();

        if (Request::submitted('assign_role')) {
            // assign roles
            foreach (Request::intArray('rolesel') as $selroleid) {
                $role = self::getRole($selroleid);
                RolePersistence::assignRole($selecteduser, $role);
            }
        } else if (Request::submitted('remove_role')) {
            // delete role assignment
            foreach (Request::intArray('assignedroles') as $roleid) {
                $role = self::getRole($roleid);
                RolePersistence::deleteRoleAssignment($selecteduser, $role);
            }
        }

        $message = _('Die Rollenzuweisungen wurden gespeichert.');
        PageLayout::postMessage(MessageBox::success($message));

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

        $message = _('Die Rechteeinstellungen wurden gespeichert.');
        PageLayout::postMessage(MessageBox::success($message));

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
            $sql = "SELECT DISTINCT Vorname,Nachname,user_id,username,perms
                    FROM auth_user_md5
                    JOIN roles_user ON userid = user_id
                    WHERE roleid = ?
                    ORDER BY Nachname, Vorname";
            $statement = DBManager::get()->prepare($sql);
            $statement->execute(array($roleid));

            $users = $statement->fetchAll(PDO::FETCH_ASSOC);
            foreach ($users as $key => $user) {
                $institutes = new SimpleCollection(Institute::findMany(RolePersistence::getAssignedRoleInstitutes($user['user_id'], $roleid)));
                $users[$key]['institutes'] = $institutes->orderBy('name')->pluck('name');
            }

            $plugins = PluginManager::getInstance()->getPluginInfos();
            foreach ($plugins as $id => $plugin) {
                if (!$this->check_role_access($plugin, $roleid)) {
                    unset($plugins[$id]);
                }
            }

            $this->users   = $users;
            $this->plugins = $plugins;
            $this->role    = self::getRole($roleid);
            $this->roleid  = $roleid;

            $this->mps = $this->getMultiPersonSearch($roleid);
        }
    }

    /**
     *
     */
    public function add_user_action($role_id, $user_id)
    {
        $role = self::getRole($role_id);
        $ids  = $this->getUsers($role_id, $user_id);

        foreach ($ids as $id) {
            $user = new StudIPUser($id);
            RolePersistence::assignRole($user, $role);
        }

        $template = ngettext('Der Rolle wurde eine weitere Person hinzugef�gt.',
                             'Der Rolle wurden %u weitere Personen hinzugef�gt.',
                             count($ids));
        $message = sprintf($template, count($ids));
        PageLayout::postMessage(MessageBox::success($message));

        $this->redirect('admin/role/show_role/' . $role_id);
    }

    /**
     *
     */
    public function remove_user_action($role_id, $user_id)
    {
        CSRFProtection::verifyUnsafeRequest();

        $role = self::getRole($role_id);
        $ids  = $this->getUsers($role_id, $user_id);

        foreach ($ids as $id) {
            $user = new StudIPUser($id);
            RolePersistence::deleteRoleAssignment($user, $role);
        }

        $template = ngettext('Einer Person wurde die Rolle entzogen.',
                             '%u Personen wurde die Rolle entzogen.',
                             count($ids));
        $message = sprintf($template, count($ids));
        PageLayout::postMessage(MessageBox::success($message));

        $this->redirect('admin/role/show_role/' . $role_id);
    }

    /**
     *
     */
    public function add_plugin_action($role_id)
    {
        PageLayout::setTitle(_('Plugins zur Rolle hinzuf�gen'));

        if (Request::isPost()) {
            CSRFProtection::verifyUnsafeRequest();

            $plugin_ids = Request::intArray('plugin_ids');

            if (count($plugin_ids) > 0) {
                foreach ($plugin_ids as $id) {
                    RolePersistence::assignPluginRoles($id, array($role_id));
                }

                $template = ngettext('Der Rolle wurde ein weiteres Plugin hinzugef�gt.',
                                     'Der Rolle wurden %u weitere Plugins hinzugef�gt.',
                                     count($plugin_ids));
                $message = sprintf($template, count($plugin_ids));
                PageLayout::postMessage(MessageBox::success($message));
            }

            $this->redirect('admin/role/show_role/' . $role_id);
        }

        $this->role_id = $role_id;

        $plugins    = PluginManager::getInstance()->getPluginInfos();
        $controller = $this;

        $this->plugins = array_filter($plugins, function ($plugin) use ($controller, $role_id) {
            return !$controller->check_role_access($plugin, $role_id);
        });
    }

    /**
     *
     */
    public function remove_plugin_action($role_id, $plugin_id)
    {
        CSRFProtection::verifyUnsafeRequest();

        $role = self::getRole($role_id);
        $ids  = $this->getPlugins($role_id, $plugin_id);

        foreach ($ids as $id) {
            RolePersistence::deleteAssignedPluginRoles($id, array($role_id));
        }

        $template = ngettext('Einem Plugin wurde die Rolle entzogen.',
                             '%u Plugins wurde die Rolle entzogen.',
                             count($ids));
        $message = sprintf($template, count($ids));
        PageLayout::postMessage(MessageBox::success($message));

        $this->redirect('admin/role/show_role/' . $role_id);
    }

    /**
     *
     */
    private function getUsers($role_id, $user_id)
    {
        // From form
        if (Request::getInstance()->offsetExists('ids')) {
            return Request::optionArray('ids');
        }

        // From multi person search
        if ($user_id === 'bulk') {
            return $this->getMultiPersonSearch($role_id)->getAddedUsers();
        }

        // From url
        return array($user_id);
    }

    /**
     *
     */
    private function getPlugins($role_id, $plugin_id)
    {
        // From form
        if (Request::getInstance()->offsetExists('ids')) {
            return Request::optionArray('ids');
        }

        // From url
        return array($plugin_id);
    }

    /**
     *
     */
    protected function getMultiPersonSearch($role_id)
    {
        // Multiperson search
        $query = "SELECT aum.user_id,
                         {$GLOBALS['_fullname_sql']['full_rev']} AS fullname,
                         username, perms
                  FROM auth_user_md5 AS aum
                  LEFT JOIN user_info AS ui ON (aum.user_id = ui.user_id)
                  LEFT JOIN roles_user AS ru ON (aum.user_id = ru.userid AND ru.roleid = {$role_id})
                  WHERE ru.userid IS NULL
                     AND (username LIKE :input
                     OR Vorname LIKE :input
                     OR Nachname LIKE :input
                     OR CONCAT(Vorname, ' ', Nachname) LIKE :input
                     OR CONCAT(Nachname, ' ', Vorname) LIKE :input
                     OR {$GLOBALS['_fullname_sql']['full_rev']} LIKE :input)
                  ORDER BY fullname ASC";
        $url = URLHelper::getURL('dispatch.php/admin/role/add_user/' . $role_id . '/bulk');
        return MultiPersonSearch::get('add_role_users')
            ->setLinkText(_('Personen hinzuf�gen'))
            ->setTitle(_('Personen zur Rolle hinzuf�gen'))
            ->setExecuteURL($url)
            ->setSearchObject(new SQLSearch($query, _('Nutzer suchen'), 'user_id'));
    }

    function assign_role_institutes_action($role_id,$user_id)
    {
        if (Request::isXhr()) {
            $this->set_layout(null);
            $this->set_content_type('text/html;charset=windows-1252');
            $this->response->add_header('X-No-Buttons', 1);
            $this->response->add_header('X-Title', PageLayout::getTitle());
            foreach (array_keys($_POST) as $param) {
                Request::set($param, studip_utf8decode(Request::get($param)));
            }
        }
        if (Request::submitted('add_institute') && $institut_id = Request::option('institute_id')) {
            $roles = RolePersistence::getAllRoles();
            $role = $roles[$role_id];
            $user = new StudIPUser($user_id);
            RolePersistence::assignRole($user, $role, Request::option('institute_id'));
            PageLayout::postMessage(MessageBox::success(_("Die Einrichtung wurde zugewiesen.")));
        }
        if ($remove_institut_id = Request::option('remove_institute')) {
            $roles = RolePersistence::getAllRoles();
            $role = $roles[$role_id];
            $user = new StudIPUser($user_id);
            RolePersistence::deleteRoleAssignment($user, $role, $remove_institut_id);
            PageLayout::postMessage(MessageBox::success(_("Die Einrichtung wurde entfernt.")));

        }
        $roles = RolePersistence::getAllRoles();
        $this->role = $roles[$role_id];
        $this->user = new User($user_id);
        $this->institutes = SimpleCollection::createFromArray(Institute::findMany(RolePersistence::getAssignedRoleInstitutes($user_id, $role_id)));
        $this->institutes->orderBy('name');
        $this->qsearch = QuickSearch::get("institute_id", new StandardSearch("Institut_id"));
        if (Request::isXhr()) {
            $this->qsearch->withoutButton();
        }
    }

    private function setSidebar($action)
    {
        $sidebar = Sidebar::Get();
        $sidebar->setTitle(PageLayout::getTitle() ?: _('Rollen'));
        $sidebar->setImage('sidebar/roles-sidebar.png');

        $views = new ViewsWidget();
        $views->addLink(_('Rollen verwalten'),
                          $this->url_for('admin/role'))
                ->setActive($action === 'index');
        $views->addLink(_('Personenzuweisungen bearbeiten'),
                          $this->url_for('admin/role/assign_role'))
                ->setActive($action === 'assign_role');
        $views->addLink(_('Pluginzuweisungen bearbeiten'),
                          $this->url_for('admin/role/assign_plugin_role'))
                ->setActive($action === 'assign_plugin_role');
        $views->addLink(_('Rollenzuweisungen anzeigen'),
                          $this->url_for('admin/role/show_role'))
                ->setActive($action === 'show_role');
        $sidebar->addWidget($views);

        $actions = new ActionsWidget();
        $actions->addLink(_('Neue Rolle anlegen'),
                          $this->url_for('admin/role/add'),
                          Icon::create('add', 'clickable'))
                ->asDialog('size=auto');
        $sidebar->addWidget($actions);
    }
}
