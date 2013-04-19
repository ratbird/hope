<?php

/**
 * Description of UserVisibility
 *
 * @author flo
 */
require_once 'PrivacySetting.php';

class UserPrivacy {

    /**
     * Userobject that owns the privacy settings
     * @var type int
     */
    private $userid;
    private $profileSettings;

    /**
     * Loads the privacySettings from the database
     */
    private function initProfileSettings() {
        $this->profileSettings = array();
        /* $db = new DB_Seminar("SELECT visibilityid 
          FROM user_visibility_settings
          JOIN plugins ON user_visibility_settings.plugin = plugins.pluginid
          JOIN plugins_activated ON user_visibility_settings.plugin = plugins_activated.pluginid
          WHERE userid = '$this->userid' AND parent_id = 0 AND enabled ='yes'
          AND plugins_activated.poiid = 'user$this->userid' AND plugins_activated.state = 'on'");
         */
        $db = new DB_Seminar("SELECT visibilityid, plugin 
                FROM user_visibility_settings
                WHERE user_id = '$this->userid' AND parent_id = 0 
");

        while ($db->next_record()) {
            $pluginManager = PluginManager::getInstance();
            $plugin = $pluginManager->getPluginInfoById($db->f("plugin"));
            if ($db->f("plugin") == 0
                    || ($pluginManager->isPluginActivatedForUser($db->f("plugin"), $this->userid)) 
                            && $plugin['enabled']) {
        
                array_push($this->profileSettings, new PrivacySetting($db->f(visibilityid)));
            }
        }
    }

    /**
     * Builds a visibility setting for a specific userid
     */
    function __construct($userid = null, $loadAll = TRUE) {
        if ($userid == null) {
            $this->userid = $GLOBALS['user']->user_id;
        } else {
            $this->userid = $userid;
        }
        if ($loadAll) {
            $this->initProfileSettings();
        }
    }

    /**
     * Returns all the categorys and it's items
     * @return type categorys and it's items
     */
    function getProfileSettings() {
        $this->loadSettings();
        return $this->profileSettings;
    }

    /**
     * Adds a new privacysetting to the database and returns the created id
     * @param type $category category where it should be shown in settings
     * @param type $name name of the entry (shown in settings)
     * @param type $default default visibility state
     * @return type new visibilityID
     */
    function addPrivacySetting($category, $name, $parent_id = "0", $default = 1, $pluginid = 0) {
        $visibilityid = uniqid();
        $db = DBManager::get();
        $sql = "INSERT INTO user_visibility_settings (`userid`, `visibilityid`, `parent_id`, `category`, `name`, `state`, `plugin`)
                   VALUES ('$this->userid', '" . $visibilityid . "', '" . $parent_id . "', '$category', '$name', '$default', '$pluginid')";
        $db->exec($sql);
        return $visibilityid;
    }

    /**
     * Returns a specific privacysetting by a given visibilityid
     * @param type $id the visibility id of the privacysetting 
     * @return type the state of the setting
     */
    function getPrivacySettingByID($id) {
        if ($this->isSettingsLoaded()) {
            foreach ($this->profileSettings as $category) {
                foreach ($category as $key => $item) {
                    if ($item["id"] == $id) {
                        return $item["visibility"];
                    }
                }
            }
        } else {
            $db = new DB_Seminar("SELECT state FROM user_visibility_settings WHERE visibilityid = '$id'");
            if ($db->next_record()) {
                return $db->f("state");
            }
        }
    }

    /**
     * Takes the new_priv_settings request and stores it into the database
     */
    function updateAllFromRequest($data) {
        $db = DBManager::get();
        
        /* 
         * This is really interesting! A single update query with CASE construct
         * is performed in about half the time of multiple queries with WHERE
         */
        
        $sql = "UPDATE `user_visibility_settings` SET `state` = CASE `visibilityid` ";
        foreach ($data as $key => $ps) {
            $sql .= "WHEN '$key' THEN '$ps' ";
        }
        $sql .= "ELSE `state` END;";
        $db->exec($sql);
    }

    /**
     * Updates a PrivacySetting in the DB
     * 
     * @param type $key The Settings Identifier
     * @param type $state The wanted state
     * @param type $db Optional an open database connection
     * @param type $userid Optional the users id
     */
    function update($key, $state, $db = null, $userid = null) {
        if ($db == null) {
            $db = DBManager::get();
        }
        $sql = "UPDATE user_visibility_settings SET state = '$state' WHERE visibilityid='$key'";
        if ($userid != null) {
            $sql .= " AND userid = '$userid'";
        }
        $db->exec($sql);
    }

    /**
     * Loads all privacysettings for a user
     */
    function loadSettings() {
        if (!isset($this->profileSettings)) {
            $this->initProfileSettings();
        }
    }

    /**
     * Reloads all privacysettings for a user
     */
    function reloadSettings() {
        $this->initProfileSettings();
    }

    private function isSettingsLoaded() {
        is_array($this->profileSettings);
    }

    /**
     * Returns all Arguments for the SettingsPage
     * @return array Arguments for the SettingsPage
     */
    function getHTMLArgs() {
        $privacy_states = VisibilitySettings::getInstance();
        $result['header_colspan'] = $privacy_states->count() + 1;
        $result['row_colspan'] = $privacy_states->count();
        $result['header_names'] = $privacy_states->getAllNames();
        $result['states'] = $privacy_states->getAllKeys();
        $result['entry'] = array();
        foreach ($this->profileSettings as $child) {
            $child->getHTMLArgs($result['entry']);
        }
        return $result;
    }

}

?>
