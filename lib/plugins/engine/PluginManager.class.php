<?php
# Lifter010: TODO
/*
 * PluginManager.class.php - plugin manager for Stud.IP
 *
 * Copyright (c) 2009  Elmar Ludwig
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

class PluginManager
{
    /**
     * meta data of installed plugins
     */
    private $plugins;

    /**
     * cache of created plugin instances
     */
    private $plugin_cache;

    /**
     * RolePersistence object
     */
    private $rolemgmt;

    /**
     * Returns the PluginManager singleton instance.
     */
    public static function getInstance ()
    {
        static $instance;

        if (isset($instance)) {
            return $instance;
        }

        return $instance = new PluginManager();
    }

    /**
     * Initialize a new PluginManager instance.
     */
    private function __construct ()
    {
        $this->readPluginInfos();
        $this->plugin_cache = array();
        $this->rolemgmt = new RolePersistence();
    }

    /**
     * Comparison function used to order plugins by position.
     */
    private static function positionCompare ($plugin1, $plugin2)
    {
        return $plugin1['position'] - $plugin2['position'];
    }

    /**
     * Read meta data for all plugins registered in the data base.
     */
    private function readPluginInfos ()
    {
        $db = DBManager::get();
        $this->plugins = array();

        $result = $db->query('SELECT * FROM plugins ORDER BY pluginname');

        foreach ($result as $plugin) {
            $id = (int) $plugin['pluginid'];

            $this->plugins[$id] = array(
                'id'          => $id,
                'name'        => $plugin['pluginname'],
                'class'       => $plugin['pluginclassname'],
                'path'        => $plugin['pluginpath'],
                'type'        => explode(',', $plugin['plugintype']),
                'enabled'     => $plugin['enabled'] === 'yes',
                'position'    => $plugin['navigationpos'],
                'depends'     => (int) $plugin['dependentonid']
            );
        }
    }

     /**
      * @addtogroup notifications
      *
      * Enabling or disabling a plugin triggers a PluginDidEnable or
      * respectively PluginDidDisable notification. The plugin's ID
      * is transmitted as subject of the notification.
      */

    /**
     * Set the enabled/disabled status of the given plugin.
     *
     * Triggers a PluginDidEnable or respectively PluginDidDisable
     * notification. The plugin's ID is transmitted as subject of the
     * notification.
     *
     * @param $id        id of the plugin
     * @param $enabled   plugin status (true or false)
     */
    public function setPluginEnabled ($id, $enabled)
    {
        $db = DBManager::get();
        $info = $this->getPluginInfoById($id);
        $state = $enabled ? 'yes' : 'no';

        if ($info && $info['enabled'] != $enabled) {
            $db->exec("UPDATE plugins SET enabled = '$state' WHERE pluginid = '$id'");
            $this->plugins[$id]['enabled'] = (boolean) $enabled;

            // call #onEnable or #onDisable
            $plugin_class = $this->loadPluginById($id);
            if ($plugin_class) {
                $plugin_class->getMethod("on" .  ($enabled ? "En" : "Dis") . "able")->invoke(NULL, $id);
            }

            NotificationCenter::postNotification(
                $enabled ? 'PluginDidEnable' : 'PluginDidDisable',
                $id);
        }

    }

    /**
     * Set the navigation position of the given plugin.
     *
     * @param $id        id of the plugin
     * @param $position  plugin navigation position
     */
    public function setPluginPosition ($id, $position)
    {
        $db = DBManager::get();
        $info = $this->getPluginInfoById($id);
        $position = (int) $position;

        if ($info && $info['position'] != $position) {
            $db->exec("UPDATE plugins SET navigationpos = $position WHERE pluginid = '$id'");
            $this->plugins[$id]['position'] = $position;
            $this->readPluginInfos();
        }
    }

    /**
     * Get the activation status of a plugin in the given context.
     * This also checks the plugin default activations.
     *
     * @param $id        id of the plugin
     * @param $context   context range id
     */
    public function isPluginActivated ($id, $context)
    {
        $query = "SELECT 1 "
               . "FROM plugins_default_activations "
               . "JOIN seminar_inst ON (institutid = institut_id) "
               . "WHERE pluginid = ? AND seminar_id = ?";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($id, $context));
        $default = $statement->fetchColumn();

        $query = "SELECT state "
               . "FROM plugins_activated "
               . "WHERE pluginid = ? AND (poiid = CONCAT('sem', ?) OR poiid = CONCAT('inst', ?))";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array($id, $context, $context));
        $state = $statement->fetchColumn();

        return $default && $state !== 'off' || $state === 'on';
    }

    /**
     * Sets the activation status of a plugin in the given context.
     *
     * @param $id        id of the plugin
     * @param $context   context range id
     * @param $active    plugin status (true or false)
     */
    public function setPluginActivated ($id, $context, $active)
    {
        $db = DBManager::get();
        $state = $active ? 'on' : 'off';

        $db->exec("REPLACE INTO plugins_activated (pluginid, poiid, state)
                   VALUES ('$id', 'sem$context', '$state')");
    }

    /**
     * Returns the list of institutes for which a specific plugin is
     * enabled by default.
     *
     * @param $id        id of the plugin
     */
    public function getDefaultActivations ($id)
    {
        $db = DBManager::get();

        $result = $db->query("SELECT institutid FROM plugins_default_activations
                              WHERE pluginid = '$id'");

        return $result->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Set the list of institutes for which a specific plugin should
     * be enabled by default.
     *
     * @param $id          id of the plugin
     * @param $institutes  array of institute ids
     */
    public function setDefaultActivations ($id, $institutes)
    {
        $db = DBManager::get();

        $result = $db->query("DELETE FROM plugins_default_activations
                              WHERE pluginid = '$id'");

        $stmt = $db->prepare("INSERT INTO plugins_default_activations
                              (pluginid, institutid) VALUES (?,?)");

        foreach ($institutes as $instid) {
            $stmt->execute(array($id, $instid));
        }
    }

    /**
     * Load a plugin class from the given file system path and
     * return the ReflectionClass instance for the plugin.
     *
     * @param $class     plugin class name
     * @param $path      plugin relative path
     */
    private function loadPlugin ($class, $path)
    {
        $basepath = get_config('PLUGINS_PATH');
        $pluginfile = $basepath.'/'.$path.'/'.$class.'.class.php';

        if (!file_exists($pluginfile)) {
            $pluginfile = $basepath.'/'.$path.'/'.$class.'.php';

            if (!file_exists($pluginfile)) {
                return NULL;
            }
        }

        require_once $pluginfile;

        return new ReflectionClass($class);
    }

    /**
     * Load a plugin class from the given plugin ID and
     * return the ReflectionClass instance for the plugin.
     *
     * @param $id string  the plugin's ID
     * @return a ReflectionClass instance of the plugin
     */
    private function loadPluginById ($id)
    {
        $plugin_info = $this->getPluginInfoById($id);
        if (isset($plugin_info)) {

            $class = $plugin_info['class'];
            $path  = $plugin_info['path'];

            return $this->loadPlugin($class, $path);
        }

        return NULL;
    }

    /**
     * Determine the type of a plugin to be installed.
     *
     * @param $class     plugin class name
     * @param $path      plugin relative path
     */
    private function getPluginType ($class, $path)
    {
        $plugin_class = $this->loadPlugin($class, $path);
        $types = array();

        if ($plugin_class) {
            $plugin_base = new ReflectionClass('StudIPPlugin');
            $interfaces = $plugin_class->getInterfaces();

            if ($plugin_class->isSubclassOf($plugin_base)) {
                foreach ($interfaces as $interface) {
                    $types[] = $interface->getName();
                }
            }
        }

        sort($types);

        return $types;
    }

    /**
     * Register a new plugin or update an existing plugin entry in the
     * data base. Returns the id of the new or updated plugin.
     *
     * @param $name      plugin name
     * @param $class     plugin class name
     * @param $path      plugin relative path
     * @param $depends   id of plugin this plugin depends on
     */
    public function registerPlugin ($name, $class, $path, $depends = NULL)
    {
        $db = DBManager::get();
        $info = $this->getPluginInfo($class);
        $type = $this->getPluginType($class, $path);
        $position = 1;

        // plugin must implement at least one interface
        if (count($type) == 0) {
            return NULL;
        }

        if ($info) {
            $id = $info['id'];
            $sql = 'UPDATE plugins SET pluginname = ?, pluginpath = ?,
                                       plugintype = ? WHERE pluginid = ?';
            $stmt = $db->prepare($sql);
            $stmt->execute(array($name, $path, join(',', $type), $id));

            $this->plugins[$id]['name'] = $name;
            $this->plugins[$id]['path'] = $path;
            $this->plugins[$id]['type'] = $type;

            return $id;
        }

        foreach ($this->plugins as $plugin) {
            $common_types = array_intersect($type, $plugin['type']);

            if (count($common_types) > 0 && $plugin['position'] >= $position) {
                $position = $plugin['position'] + 1;
            }
        }

        $sql = 'INSERT INTO plugins (
                    pluginname, pluginclassname, pluginpath,
                    plugintype, navigationpos, dependentonid
                ) VALUES (?,?,?,?,?,?)';
        $stmt = $db->prepare($sql);
        $stmt->execute(array($name, $class, $path, join(',', $type), $position, $depends));
        $id = $db->lastInsertId();

        $this->plugins[$id] = array(
            'id'          => $id,
            'name'        => $name,
            'class'       => $class,
            'path'        => $path,
            'type'        => $type,
            'enabled'     => false,
            'position'    => $position,
            'depends'     => $depends
        );

        $this->readPluginInfos();

        $db->exec("INSERT INTO roles_plugins (roleid, pluginid)
                   SELECT roleid, $id FROM roles WHERE system = 'y' AND rolename != 'Nobody'");

        return $id;
    }

    /**
     * Remove registration for the given plugin from the data base.
     *
     * @param $id        id of the plugin
     */
    public function unregisterPlugin ($id)
    {
        $db = DBManager::get();
        $info = $this->getPluginInfoById($id);

        if ($info) {
            $db->exec("DELETE FROM plugins WHERE pluginid = '$id'");
            $db->exec("DELETE FROM plugins_activated WHERE pluginid = '$id'");
            $db->exec("DELETE FROM plugins_default_activations WHERE pluginid = '$id'");
            $db->exec("DELETE FROM roles_plugins WHERE pluginid = '$id'");

            unset($this->plugins[$id]);
        }
    }

    /**
     * Get meta data for the plugin specified by plugin class name.
     *
     * @param $class   class name of plugin
     */
    public function getPluginInfo ($class)
    {
        foreach ($this->plugins as $plugin) {
            if (strcasecmp($plugin['class'], $class) == 0) {
                return $plugin;
            }
        }

        return NULL;
    }

    /**
     * Get meta data for the plugin specified by plugin id.
     *
     * @param $id   id of the plugin
     */
    public function getPluginInfoById ($id)
    {
        if (isset($this->plugins[$id])) {
            return $this->plugins[$id];
        }

        return NULL;
    }

    /**
     * Get meta data for all plugins of the specified type. A type of NULL
     * returns meta data for all installed plugins.
     *
     * @param $type      plugin type or NULL (all types)
     */
    public function getPluginInfos ($type = NULL)
    {
        $result = array();

        foreach ($this->plugins as $id => $plugin) {
            if ($type === NULL || in_array($type, $plugin['type'])) {
                $result[$id] = $plugin;
            }
        }

        return $result;
    }

    /**
     * Check user access permission for the given plugin.
     *
     * @param $plugin   plugin meta data
     * @param $user     user id of user
     */
    protected function checkUserAccess ($plugin, $user)
    {
        if (!$plugin['enabled']) {
            return false;
        }

        $plugin_roles = $this->rolemgmt->getAssignedPluginRoles($plugin['id']);
        $user_roles = $this->rolemgmt->getAssignedRoles($user, true);

        foreach ($plugin_roles as $plugin_role) {
            foreach ($user_roles as $user_role) {
                if ($plugin_role->getRoleid() === $user_role->getRoleid()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get instance of the plugin specified by plugin meta data.
     *
     * @param $plugin_info   plugin meta data
     * @param $context       context range id (optional)
     */
    protected function getCachedPlugin ($plugin_info, $context = NULL)
    {
        $class = $plugin_info['class'];
        $path  = $plugin_info['path'];
        $cache_key = isset($context) ? $class.'_'.$context : $class;

        if (isset($this->plugin_cache[$class])) {
            return $this->plugin_cache[$class];
        }

        if (isset($this->plugin_cache[$cache_key])) {
            return $this->plugin_cache[$cache_key];
        }

        $plugin_class = $this->loadPlugin($class, $path);

        if ($plugin_class) {
            $plugin = $plugin_class->newInstance();
        }

        return $this->plugin_cache[$cache_key] = $plugin;
    }

    /**
     * Get instance of the plugin specified by plugin class name.
     *
     * @param $class   class name of plugin
     */
    public function getPlugin ($class)
    {
        $user = $GLOBALS['user']->id;
        $plugin_info = $this->getPluginInfo($class);

        if (isset($plugin_info) && $this->checkUserAccess($plugin_info, $user)) {
            $plugin = $this->getCachedPlugin($plugin_info);
        }

        return $plugin;
    }

    /**
     * Get instance of the plugin specified by plugin id.
     *
     * @param $id   id of the plugin
     */
    public function getPluginById ($id)
    {
        $user = $GLOBALS['user']->id;
        $plugin_info = $this->getPluginInfoById($id);

        if (isset($plugin_info) && $this->checkUserAccess($plugin_info, $user)) {
            $plugin = $this->getCachedPlugin($plugin_info);
        }

        return $plugin;
    }

    /**
     * Get instances of all plugins of the specified type. A type of NULL
     * returns all enabled plugins. The optional context parameter can be
     * used to get only plugins that are activated in the given context.
     *
     * @param $type      plugin type or NULL (all types)
     * @param $context   context range id (optional)
     */
    public function getPlugins ($type, $context = NULL)
    {
        $user = $GLOBALS['user']->id;
        $plugin_info = $this->getPluginInfos($type);
        $plugins = array();

        usort($plugin_info, array('self', 'positionCompare'));

        foreach ($plugin_info as $info) {
            $activated = $context == NULL ||
                $this->isPluginActivated($info['id'], $context);

            if ($this->checkUserAccess($info, $user) && $activated) {
                $plugin = $this->getCachedPlugin($info, $context);

                if ($plugin !== NULL) {
                    $plugins[] = $plugin;
                }
            }
        }

        return $plugins;
    }
}
