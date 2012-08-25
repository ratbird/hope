<?php
# Lifter007: TODO
# Lifter010: TODO
/**
 * Factory Class for the plugin engine
 * @author Dennis Reil, <dennis.reil@offis.de>
 * @package pluginengine
 * @subpackage engine
 */

require_once 'PluginManager.class.php';
require_once 'PluginNotFoundException.php';

class PluginEngine {

    /*
     * cache for visit-dates for plugins
     */
    private static $visits_data = array();

    /**
     * @deprecated
     *
     * @return int  returns the current plugin's ID
     */
    public static function getCurrentPluginId() {
        $page = basename($_SERVER['PHP_SELF']);

        if ($page == 'plugins.php' && isset($_SERVER['PATH_INFO'])) {
            list($plugin_class) = self::routeRequest($_SERVER['PATH_INFO']);
            $info = PluginManager::getInstance()->getPluginInfo($plugin_class);
            return $info['id'];
        }
    }

    /**
     * This function maps an incoming request to a tuple
     * (pluginclassname, unconsumed rest).
     *
     * @return array the above mentioned tuple
     */
    public static function routeRequest($dispatch_to) {
        $dispatch_to = ltrim($dispatch_to, '/');
        if (strlen($dispatch_to) === 0) {
            throw new PluginNotFoundException(_('Es wurde kein Plugin gewählt.'));
        }
        $pos = strpos($dispatch_to, '/');
        return $pos === FALSE
            ? array($dispatch_to, '')
            : array(substr($dispatch_to, 0, $pos), substr($dispatch_to, $pos + 1));
    }

    /**
     * Load the default set of plugins. This currently loads plugins of
     * type Homepage, Standard (if a course is selected), Administration
     * (if user has admin status) and System. The exact type of plugins
     * loaded here may change in the future.
     */
    public static function loadPlugins() {
        global $user, $perm;

        // load system plugins and run background tasks
        foreach (self::getPlugins('SystemPlugin') as $plugin) {
            if ($plugin instanceof AbstractStudIPSystemPlugin) {
                if ($plugin->hasBackgroundTasks()) {
                    $plugin->doBackgroundTasks();
                }
            }
        }
        
        // load homepage plugins
        self::getPlugins('HomepagePlugin');

        // load course plugins
        if (isset($_SESSION['SessionSeminar'])) {
            self::getPlugins('StandardPlugin');
        }

        // load admin plugins
        if (is_object($user) && $perm->have_perm('admin')) {
            self::getPlugins('AdministrationPlugin');
        }
    }

    /**
     * Get instance of the plugin specified by plugin class name.
     *
     * @param $class   class name of plugin
     */
    public static function getPlugin ($class) {
        return PluginManager::getInstance()->getPlugin($class);
    }

    /**
     * Get instances of all plugins of the specified type. A type of NULL
     * returns all enabled plugins. The optional context parameter can be
     * used to get only plugins that are activated in the given context.
     *
     * @param $type      plugin type or NULL (all types)
     * @param $context   context range id (optional)
     */
    public static function getPlugins ($type, $context = NULL) {
        return PluginManager::getInstance()->getPlugins($type, $context);
    }

    /**
     * Sends a message to all activated plugins of a type and returns an array of
     * the return values.
     *
     * @param  type       plugin type or NULL (all types)
     * @param  string     the method name that should be send to all plugins
     * @param  mixed      a variable number of arguments
     *
     * @return array      an array containing the return values
     */
    public static function sendMessage($type, $method /* ... */) {
        $args = func_get_args();
        array_splice($args, 1, 0, array(NULL));
        return call_user_func_array(array(__CLASS__, 'sendMessageWithContext'), $args);
    }

    /**
     * Sends a message to all activated plugins of a type enabled in a context and
     * returns an array of the return values.
     *
     * @param  type       plugin type or NULL (all types)
     * @param  context    context range id (may be NULL)
     * @param  string     the method name that should be send to all plugins
     * @param  mixed      a variable number of arguments
     *
     * @return array      an array containing the return values
     */
    public static function sendMessageWithContext($type, $context, $method /* ... */) {
        $args = func_get_args();
        $args = array_slice($args, 3);
        $results = array();
        foreach (self::getPlugins($type, $context) as $plugin) {
            $results[] = call_user_func_array(array($plugin, $method), $args);
        }
        return $results;
    }

    /**
    * Generates a URL which can be shown in user interfaces
    * @param $plugin - the plugin to which should be linked
    * @param $params - an array with name value pairs
    * @param $cmd - command to execute by clicking the link
    * @return a link to the current plugin with the additional $params
    */
    public static function getURL($plugin, $params = array(), $cmd = 'show') {
        if (is_null($plugin)) {
            throw new InvalidArgumentException(_('Es wurde kein Plugin gewählt.'));
        } else if (is_object($plugin)) {
            $plugin = strtolower(get_class($plugin)) . '/' . $cmd;
        } else if (strpos($plugin, '/') === false) {
            $plugin = $plugin . '/' . $cmd;
        }

        return URLHelper::getURL('plugins.php/' . $plugin, $params);
    }

    /**
    * Generates a link (entity encoded URL) which can be shown in user interfaces
    * @param $plugin - the plugin to which should be linked
    * @param $params - an array with name value pairs
    * @param $cmd - command to execute by clicking the link
    * @return a link to the current plugin with the additional $params
    */
    public static function getLink($plugin, $params = array(), $cmd = 'show') {
        return htmlspecialchars(self::getURL($plugin, $params, $cmd));
    }

    /**
     * Generates a link to the plugin administration which can be shown in user interfaces
     *
     * @deprecated
     *
     * @param   array   an optional array with name value pairs
     * @param   string  an optional command defaulting to 'show'
     *
     * @return  string  a link to the administration plugin with the additional $params
     */
    public static function getLinkToAdministrationPlugin($params = array(), $cmd = 'show') {
        return self::getLink('pluginadministrationplugin', $params, $cmd);
    }

    /**
     * Saves a value to the global session
     *
     * @deprecated
     *
     * @param StudIPPlugin $plugin - the plugin for which the value should be saved
     * @param string $key - a key for the value. has to be unique for the calling plugin
     * @param string $value - the value, which should be saved into the session
     */
    public static function saveToSession($plugin,$key,$value) {
        $_SESSION["PLUGIN_SESSION_SPACE"][strtolower(get_class($plugin))][$key] =serialize($value);
    }

    /**
     * Retrieves the value to key from the global plugin session
     *
     * @deprecated
     */
    public static function getValueFromSession($plugin,$key) {
        return unserialize($_SESSION["PLUGIN_SESSION_SPACE"][strtolower(get_class($plugin))][$key]);
    }

    /**
     * Set the visitdate for the passed plugin. You can pass a type to track
     * multiple visitdates in the same plugin
     *
     * @param object $plugin the plugin-object to set the visit-date for
     * @param string $type an optional string denoting the type of the visitdate
     */
    public static function setVisit($plugin, $object_id = null, $type = null, $user_id = null)
    {
        // if no object-id is given, use the id of the passed plugin (special case)
        if(!$object_id) $object_id = $plugin->getId();
        
        if(!$user_id) $user_id = $GLOBALS['user']->id;
        $pluginname = $plugin->getPluginclassname();

        $last_visit = (int)self::getLastVisit($plugin, $object_id, $type, $user_id, false);
            if($last_visit < object_get_visit($plugin->getId(), 'sem', false, false)){
                $key = join('-', array($pluginname, $object_id, $user_id, $type));
                unset(self::$visits_data[$key]);
                
                $stmt = DBManager::get()->prepare("REPLACE INTO plugins_object_user_visits 
                    (pluginname,object_id,user_id,type,visitdate,last_visitdate)
                    VALUES (?,?,?,?,UNIX_TIMESTAMP(),?)");
                return $stmt->execute(array($pluginname, $object_id, $user_id, $type, $last_visit));
        }

        return false;
    }

    /**
     * get the visitdate for the passed plugin. You can pass a type to track
     * multiple visitdates in the same plugin
     *
     * @param object $plugin the plugin-object to set the visit-date for
     * @param string $object_id optional, the id of the object to set the visit-date for, defaults to the plugin-id
     * @param string $type an optional string denoting the type of the visitdate, defaults to "plugin"
     * @param string $user_id optional, defaults to $GLOBALS['user']->id     
     *
     * @return int the visitdate for the passed plugin and the passed type
     */
    public static function getVisit($plugin, $object_id = null, $type = null, $user_id = null)
    {
        $visitdate = self::getVisitDates();
        return (int)$visitdate['visitdate'];
    }

    /**
     * get the last_visitdate for the passed plugin. You can pass a type to track
     * multiple visitdates in the same plugin
     *
     * @param object $plugin the plugin-object to get the visit-date for
     * @param string $object_id optional, the id of the object to get the visit-date for, defaults to plugin-id
     * @param string $type optional, a string denoting the type of the visitdate, defaults to "plugin"
     * @param string $user_id optional, defaults to $GLOBALS['user']->id
     *
     * @return int the last_visitdate for the passed plugin and the passed type
     */
    public static function getLastVisit($plugin, $object_id = null, $type = null, $user_id = null)
    {
        $visitdate = self::getVisitDates();
        return (int)$visitdate['last_visitdate'];
    }

    /**
     * get the visitdate and the last_visitdate for the passed plugin. You can pass a type to track
     * multiple visitdates in the same plugin
     *
     * @param object $plugin the plugin-object to get the visit-date for
     * @param string $object_id optional, the id of the object to get the visit-date for, defaults to plugin-id
     * @param string $type optional, a string denoting the type of the visitdate, defaults to "plugin"
     * @param string $user_id optional, defaults to $GLOBALS['user']->id
     *
     * @return array contains the whole table row, including visit and last_visit
     */
    private static function getVisitDates($plugin, $object_id = null, $type = null, $user_id = null)
    {
        // if no object-id is given, use the id of the passed plugin (special case)
        if(!$object_id) $object_id = $plugin->getId();
        
        if(!$user_id) $user_id = $GLOBALS['user']->id;
        $pluginname = $plugin->getPluginclassname();

        $key = join('-', array($pluginname, $object_id, $user_id, $type));

        if(!isset(self::$visits_data[$key])){
            $stmt = DBManager::get()->prepare("SELECT * FROM plugins_object_user_visits 
                WHERE pluginname=? AND object_id=? AND user_id=? AND type=?");
            $stmt->execute(array($pluginname, $object_id, $user_id, $type));
            self::$visits_data[$key] = $stmt->fetch();
        }
        return self::$visits_data[$key];
    }

}