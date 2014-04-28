<?php
require_once 'User_Visibility_Settings.php';

/**
 * UserPrivacy.php - Represents the privacy settings for a user
 *
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
class UserPrivacy
{

    /**
     * @var int Userid that owns the privacy settings
     */
    private $userid;

    /**
     * @var array Privacysettingstree
     */
    private $profileSettings;

    /**
     * Builds a visibility setting for a specific userid
     * @param type $userid the specific userid
     */
    public function __construct($userid = null)
    {
        if ($userid == null) {
            $this->userid = $GLOBALS['user']->user_id;
        } else {
            $this->userid = $userid;
        }
    }

    /**
     * Returns all the categorys and it's items
     * @return array categorys and it's items
     */
    function getProfileSettings()
    {
        if (!isset($this->profileSettings)) {
            $this->profileSettings = User_Visibility_Settings::findBySQL("user_id = ? AND parent_id = 0 AND identifier <> 'plugins'", array($this->userid));
            foreach ($this->profileSettings as $vis) {
                $vis->loadChildren();
            }
            $about = new about($GLOBALS['user']->username, '');
            $elements = $about->get_homepage_elements();

            foreach ($elements as $key => $element) {
                foreach ($this->profileSettings as $vis) {
                    if ($vis->name === $element['category']) {
                        foreach ($vis->children as $child) {
                            if ($child->identifier === $key) {
                                break 2;
                            }
                        }

                        $child = new User_Visibility_Settings();
                        $child->setData(array(
                            'user_id'    => $this->userid,
                            'parent_id'  => $vis->id,
                            'category'   => 1,
                            'name'       => $element['name'],
                            'state'      => $element['visibility'],
                            'identifier' => $key
                        ));
                        $child->store();
                        $child->parent = $vis;
                        $child->setDisplayed();
                        $vis->children[] = $child;
                        break;
                    }
                }
            }
        }
        return $this->profileSettings;
    }

    /**
     * Takes the new_priv_settings request and stores it into the database
     * @param array $data The given requestdata produced by the privacySettings
     * view
     */
    public function updateAllFromRequest($data)
    {
        $db = DBManager::get();

        /*
         * This is really interesting! A single update query with CASE construct
         * is performed in about half the time of multiple queries with WHERE
         */
        $sql = "UPDATE `user_visibility_settings` SET `state` = CASE `visibilityid` ";
        foreach (array_map('addslashes', $data) as $key => $ps) {
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
    public function update($key, $state, $db = null, $userid = null)
    {
        if ($db == null) {
            $db = DBManager::get();
        }
        $params = array();
        $sql = "UPDATE user_visibility_settings SET state = ? WHERE visibilityid = ? ";
        $params[] = $state;
        $params[] = $key;
        if ($userid != null) {
            $sql .= " AND userid = ?";
            $params[] = $userid;
        }
        $st = $db->prepare($sql);
        $st->execute($params);
    }

    /**
     * Returns all Arguments for the SettingsPage
     * @return array Arguments for the SettingsPage
     */
    function getHTMLArgs()
    {
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
