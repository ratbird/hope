<?
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/**
 * RolePersistence.class.php
 *
 * PHP version 5
 *
 * @author      Dennis Reil <dennis.reil@offis.de>
 * @author      Michael Riehemann <michael.riehemann@uni-oldenburg.de>
 * @package     pluginengine
 * @subpackage  db
 * @copyright   2009 Stud.IP
 * @license     http://www.gnu.org/licenses/gpl.html GPL Licence 3
 */


class CachingRegistry extends ArrayObject
{
    private $expire_times = array();

    public function __construct($objects = array())
    {
        parent::__construct();
        foreach($objects as $key => $value) {
            $this->register($key, $value['value'], $value['expire']);
        }
    }

    public function register($key, $value = null, $expire = 0)
    {
        $this->expire_times[$key] = (int)$expire;
        if ($value === null && $expire > 0) {
            $value = $this->getFromCache($key);
            return parent::offsetSet($key, $value);
        }
       return $this->offsetSet($key, $value);
    }

    public function unregister($key)
    {
        if ($this->expire_times[$key] > 0) {
            $this->expireFromCache($key);
        }
        unset($this->expire_times[$key]);
        return $this->offsetUnset($key);
    }

    public function offsetUnset($key)
    {
        if ($this->expire_times[$key] > 0 ) {
            $this->expireFromCache($key);
        }
        return parent::offsetUnset($key);
    }

    public function offsetGet($key)
    {
        if ($this->expire_times[$key] > 0 && !$this->offsetExists($key)) {
            $value = $this->getFromCache($key);
            parent::offsetSet($key, $value);
        }
        return parent::offsetGet($key);
    }

    public function offsetSet($key, $value)
    {
        if ($this->expire_times[$key] > 0 && $this->offsetGet($key) !== $value) {
            $this->putInCache($key, $value, $this->expire_times[$key]);
        }
        return parent::offsetSet($key, $value);
    }

    private function getFromCache($key)
    {
        $cache = StudipCacheFactory::getCache();
        return unserialize($cache->read($key));
    }

     private function putInCache($key, $value, $expire)
    {
        $cache = StudipCacheFactory::getCache();
        return $cache->write($key, serialize($value), $expire);
    }

    private function expireFromCache($key)
    {
        $cache = StudipCacheFactory::getCache();
        return $cache->expire($key);
    }
}
/**
 * role id unknown
 */
define("UNKNOWN_ROLE_ID",-1);

/**
 * Funktionen für das Rollenmanagement
 * TODO: (mriehe) this is a static class, change the public function in static public functions
 *
 */
class RolePersistence
{

    const ROLES_CACHE_KEY = 'plugins/rolepersistence/roles';
    const ROLES_PLUGINS_CACHE_KEY = 'plugins/rolepersistence/roles_plugins/';

    private static $registry;

    public function __construct()
    {
        if (self::$registry === null) {
            $items[self::ROLES_CACHE_KEY]['expire'] = 43200;
            $items[self::ROLES_PLUGINS_CACHE_KEY]['expire'] = 43200;
            $items['user_roles']['expire'] = 0;
            $items['user_roles']['value'] = array();
            self::$registry = new CachingRegistry($items);
        }
    }
    /**
     * Enter description here...
     *
     * @return array Roles
     */
    public function getAllRoles()
    {
        $roles = self::$registry[self::ROLES_CACHE_KEY];
        // cache miss, retrieve from database
        if (!$roles) {
            $roles = array();
            $stmt = DBManager::get()->query("SELECT * FROM roles ORDER BY rolename");
            foreach ($stmt as $row) {
                $role = new Role();
                $role->setRoleid($row["roleid"]);
                $role->setRolename($row["rolename"]);
                $role->setSystemtype($row["system"] == 'y');
                $roles[$row["roleid"]] = $role;
            }

            // write to cache
            self::$registry[self::ROLES_CACHE_KEY] = $roles;
        }
        return $roles;
    }

    /**
     * Inserts the role into the database or does an update, if it's already there
     *
     * @param Role $role
     * @return the role id
     */
    public function saveRole($role)
    {
        // sweep roles cache, see #getAllRoles
        unset(self::$registry[self::ROLES_CACHE_KEY]);
        unset(self::$registry['user_roles']);

        $db = DBManager::get();

        // role is not in database
        if ($role->getRoleid() == UNKNOWN_ROLE_ID) {
            $stmt = $db->prepare("INSERT INTO roles (roleid, rolename) ".
                                 "values (0, ?)");
            $stmt->execute(array($role->getRolename()));
            $roleid = $db->lastInsertId();
        }
        // role is already in database
        else {
            $roleid = $role->getRoleid();
            $stmt = $db->prepare("UPDATE roles SET rolename=? WHERE roleid=?");
            $stmt->execute(array($role->getRolename(), $roleid));
        }
        return $roleid;
    }

    /**
     * Delete role if not a permanent role. System roles cannot be deleted.
     *
     * @param unknown_type $role
     */
    public function deleteRole($role)
    {

        $id = $role->getRoleid();

        // sweep roles cache
        unset(self::$registry[self::ROLES_CACHE_KEY]);
        unset(self::$registry[self::ROLES_PLUGINS_CACHE_KEY]);
        unset(self::$registry['user_roles']);

        $db = DBManager::get();
        $stmt = $db->prepare("DELETE FROM roles WHERE roleid=? AND system='n'");
        $stmt->execute(array($id));
        if ($stmt->rowCount())
        {
            $stmt = $db->prepare("DELETE FROM roles_user WHERE roleid=?");
            $stmt->execute(array($id));
            $stmt = $db->prepare("DELETE FROM roles_plugins WHERE roleid=?");
            $stmt->execute(array($id));
            $stmt = $db->prepare("DELETE FROM roles_studipperms WHERE roleid=?");
            $stmt->execute(array($id));
        }
    }

    /**
     * Saves a role assignment to the database
     *
     * @param StudIPUser $user
     * @param Role $role
     */
    public function assignRole($user,$role)
    {
        // role is not in database
        // save it to the database first
        if ($role->getRoleid() <> UNKNOWN_ROLE_ID) {
            $roleid = self::saveRole($role);
        }
        else {
            $roleid = $role->getRoleid();
        }
        $stmt = DBManager::get()->prepare("REPLACE INTO roles_user ".
          "(roleid, userid) VALUES (?, ?)");
        $stmt->execute(array($roleid, $user->getUserid()));
        self::$registry['user_roles'] = array();
    }

    /**
     * Gets all assigned roles from the database for a user
     *
     * @param int $userid
     * @param boolean $implicit
     * @return array
     */
    public function getAssignedRoles($userid, $implicit = false)
    {
        $key = $userid . (int)$implicit;
        $user_roles = (array)self::$registry['user_roles'];
        if (!array_key_exists($key, $user_roles)) {
            if ($implicit && is_object($GLOBALS['perm']))
            {
                $global_perm = $GLOBALS['perm']->get_perm($userid);

                $stmt = DBManager::get()->prepare(
              "SELECT r.roleid FROM roles_user r ".
              "WHERE r.userid=? ".
              "UNION ".
              "SELECT rp.roleid FROM roles_studipperms rp WHERE rp.permname = ?");
                $stmt->execute(array($userid, $global_perm));
            }
            else
            {
                $stmt = DBManager::get()->prepare("SELECT r.roleid FROM roles_user r WHERE r.userid=?");
                $stmt->execute(array($userid));
            }
            $user_roles[$key] = $stmt->fetchAll(PDO::FETCH_COLUMN);
            self::$registry['user_roles'] = $user_roles;
        }
        return array_intersect_key(self::getAllRoles(), array_flip($user_roles[$key]));
    }

    /**
     * Checks a role assignment for an user
     *
     * @param string $userid
     * @param string $assignedrole
     * @return boolean
     */
    public static function isAssignedRole($userid, $assignedrole)
    {
        $stmt = DBManager::get()->query("SELECT r.roleid FROM roles_user AS u LEFT JOIN roles AS r ON r.roleid=u.roleid WHERE u.userid='{$userid}' AND r.rolename='{$assignedrole}'")->fetchColumn();
        if(!empty($stmt))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * Deletes a role assignment from the database
     *
     * @param StudIPUser[] $users
     * @param Role $role
     */
    public function deleteRoleAssignment($user,$role)
    {
        $stmt = DBManager::get()->prepare("DELETE FROM roles_user WHERE roleid=? AND userid=?");
        $stmt->execute(array($role->getRoleid(),$user->getUserid()));
        unset(self::$registry['user_roles']);
    }

    /**
     * Get's all Role-Assignments for a certain user.
     * If no user is set, all role assignments are returned.
     *
     * @param StudIPUser $user
     * @return array with roleids and the assigned userids
     */
    public function getAllRoleAssignments($user=null)
    {
        if ($user == null)
        {
            $result = DBManager::get()->query("SELECT * FROM roles_user");
        }
        else
        {
            $result = DBManager::get()->prepare("SELECT * FROM roles_user WHERE userid=?");
            $result->execute(array($user->getUserid()));
        }

        $roles_user = array();
        while (!$result->EOF)
        {
            $roles_user[$row["roleid"]] = $row["userid"];
        }
        return $roles_user;
    }

    /**
     * Enter description here...
     *
     * @param int $pluginid
     * @param array $roleids
     */
    public function assignPluginRoles($pluginid,$roleids)
    {
        unset(self::$registry[self::ROLES_PLUGINS_CACHE_KEY]);
        $stmt = DBManager::get()->prepare("REPLACE INTO roles_plugins (roleid, pluginid) VALUES (?, ?)");
        foreach ($roleids as $roleid) {
            $stmt->execute(array($roleid, $pluginid));
        }
    }

    /**
     * Enter description here...
     *
     * @param int $pluginid
     * @param array $roleids
     */
    public function deleteAssignedPluginRoles($pluginid,$roleids)
    {
        unset(self::$registry[self::ROLES_PLUGINS_CACHE_KEY]);

        $stmt = DBManager::get()->prepare("DELETE FROM roles_plugins WHERE roleid=? AND pluginid=?");
        foreach ($roleids as $roleid) {
            $stmt->execute(array($roleid, $pluginid));
        }
    }

    /**
     * Enter description here...
     *
     * @param int $pluginid
     * @return array
     */
    public function getAssignedPluginRoles($pluginid=-1)
    {
        $plugin_roles = self::$registry[self::ROLES_PLUGINS_CACHE_KEY];

        // cache miss, retrieve roles from database
        if (!$plugin_roles) {

            $plugin_roles = array();
            $roles = self::getAllRoles();

            $stmt = DBManager::get()->prepare("SELECT * FROM roles_plugins");
            $stmt->execute(array());

            while ($row = $stmt->fetch()) {
                if (isset($roles[$row["roleid"]])) {
                    $plugin_roles[$row['pluginid']][] = $roles[$row["roleid"]];
                }
            }

            // write to cache
            self::$registry[self::ROLES_PLUGINS_CACHE_KEY] = $plugin_roles;
        }
        return is_array($plugin_roles[$pluginid]) ? $plugin_roles[$pluginid] : array();
    }
}
