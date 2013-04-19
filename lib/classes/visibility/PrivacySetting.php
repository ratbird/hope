<?php

/**
 * PrivacySetting.php - Represents ONE UserPrivacySetting
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

/**
 * The PrivacySetting class is one privacySettings in the UserPrivacyTree
 */
class PrivacySetting {

    public $privacyID;
    public $parentID;
    public $childIDs = array();
    public $state;
    public $name;
    public $isCategory;
    public $displayed = false;
    public $parent = 0;
    public $plugin;

    /**
     * On construction it loads all nessecary data from the database
     * @param type $id the settingskey 
     */
    function __construct($id, $parent = 0) {
        $this->privacyID = $id;
        $db = new DB_Seminar("SELECT parent_id, category, name, state, plugin FROM user_visibility_settings WHERE visibilityid = '$id'");
        if ($db->next_record()) {
            $this->parentID = $db->f("parent_id");
            $this->isCategory = $db->f("category");
            $this->state = $db->f("state");
            $this->name = $db->f("name");
            $this->plugin = $db->f("plugin");
            $this->parent = $parent;

            // check if it is a category 
            $catDisplay = $this->isCategory != 0;

            // check if it is a plugin and if it is activated
            $pluginManager = PluginManager::getInstance();
            $plugin = $pluginManager->getPluginInfoById($this->plugin);
            $pluginDisplay = ($this->plugin == 0
                    || ($pluginManager->isPluginActivatedForUser($this->plugin, $this->userid))
                    && $plugin['enabled']);

            if ($catDisplay && $pluginDisplay) {
                $this->setDisplayed();
            }
        }

        //load all children
        $db->query("SELECT visibilityid FROM user_visibility_settings WHERE parent_id = '$this->privacyID'");
        while ($db->next_record()) {
            array_push($this->childIDs, new PrivacySetting($db->f("visibilityid"), $this));
        }
    }

    /**
     * Categories without childs are not displayed. Whenever a child in a tree
     * needs to be displayed the whole tree has to be displayed.
     */
    function setDisplayed() {
        $this->displayed = true;
        if ($this->parent != 0) {
            $this->parent->setDisplayed();
        }
    }

    /**
     * Returns the needed Arguments to build up the Interface
     * @param type $result the given array where the setting stores its data
     * @param type $depth the depth of the setting in the settingstree
     */
    function getHTMLArgs(&$result, $depth = 0) {
        if ($this->displayed) {
            $entry['is_header'] = $this->parentID == 0;
            $entry['is_category'] = $this->isCategory == 0;
            $entry['id'] = $this->privacyID;
            $entry['state'] = $this->state;
            $entry['padding'] = ($depth * 20) . "px";
            $entry['name'] .= $this->name;
            array_push($result, $entry);

            // Now add the html args for the children
            foreach ($this->childIDs as $child) {
                $child->getHTMLArgs($result, $depth + 1);
            }
        }
    }

}

?>
