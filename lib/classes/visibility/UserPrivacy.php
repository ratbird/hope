<?php
/**
 * UserPrivacy.php - Represents the privacy settings for a user
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Florian Bieringer <florian.bieringer@uni-passau.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */
require_once 'User_Visibility_Settings.php';

class UserPrivacy {

    /**
     * Userobject that owns the privacy settings
     * @var type int
     */
    private $userid;
    private $profileSettings;

    /**
     * Builds a visibility setting for a specific userid
     */
    public function __construct($userid = null) {
        if ($userid == null) {
            $this->userid = $GLOBALS['user']->user_id;
        } else {
            $this->userid = $userid;
        }
    }

    /**
     * Returns all the categorys and it's items
     * @return type categorys and it's items
     */
    function getProfileSettings() {
        if (!isset($this->profileSettings)) {
            $this->profileSettings = User_Visibility_Settings::findBySQL("user_id = ? AND parent_id = 0 ", array($this->userid));
            foreach ($this->profileSettings as $vis) {
                $vis->loadChildren();
            }
        }
        return $this->profileSettings;
    }

    /**
     * Adds a new privacysetting to the database and returns the created id
     * @param type $category category where it should be shown in settings
     * @param type $name name of the entry (shown in settings)
     * @param type $default default visibility state
     * @return type new visibilityID
     */
    public function addPrivacySetting($category, $name, $parent_id = "0", $default = 1, $pluginid = 0) {
        $visibilityid = uniqid();
        $db = DBManager::get();
        $sql = "INSERT INTO user_visibility_settings (`userid`, `visibilityid`, `parent_id`, `category`, `name`, `state`, `plugin`)
                   VALUES ('$this->userid', '" . $visibilityid . "', '" . $parent_id . "', '$category', '$name', '$default', '$pluginid')";
        $db->exec($sql);
        return $visibilityid;
    }

    /**
     * Takes the new_priv_settings request and stores it into the database
     */
    public function updateAllFromRequest($data) {
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
    public function update($key, $state, $db = null, $userid = null) {
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
        foreach ($this->getProfileSettings() as $child) {
            $child->getHTMLArgs($result['entry']);
        }
        return $result;
    }
}
?>
