<?php

/**
 * Statusgruppen.php
 * model class for statusgroups.
 * The statusgrouphierarchy is represented by the attributes
 * children and parent
 * 
 * Statusgroupmembers are saved as in <code>$this->members</code>
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Florian Bieringer <florian.bieringer@uni-passau.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 *
 */
class Statusgruppen extends SimpleORMap {

    protected $db_table = "statusgruppen";
    protected $has_many = array(
        'children' => array(
            'class_name' => 'Statusgruppen',
            'on_delete' => 'delete',
            'assoc_foreign_key' => 'range_id'),
        'members' => array(
            'class_name' => 'StatusgruppeUser',
            'on_delete' => 'delete',
            'assoc_foreign_key' => 'statusgruppe_id')
    );
    protected $belongs_to = array('parent' => array('class_name' => 'Statusgruppen',
            'foreign_key' => 'range_id'
    ));

    /**
     * Produces an array of all statusgroups a user is in
     * 
     * @param string $user_id The user_id
     * @param string $seperator The sign between the full paths
     * @param string $pre Preface of the outputted string (used for recursion)
     * @return array Stringarray of full gendered paths
     */
    public function getFullGenderedPaths($user_id, $seperator = " > ", $pre = "") {
        $result = array();
        $name = $pre ? $pre . $seperator . $this->getGenderedName($user_id) : $this->getGenderedName($user_id);
        if ($this->isMember($user_id)) {
            $result[] = $name;
        }
        if ($this->children) {
            foreach ($this->children as $child) {
                $result = array_merge($result, $child->getFullGenderedPaths($user_id, $seperator, $name));
            }
        }
        return $result;
    }

    /**
     * Returns the gendered name of a statusgroup
     * 
     * @param string $user_id The user_id
     * @return string The gendered name
     */
    public function getGenderedName($user_id) {

// We have to have at least 1 name gendered
        if ($this->name_m || $this->name_w) {
            $userinfo = new UserInfo($user_id);
            switch ($userinfo->geschlecht) {
                case UserInfo::GENDER_FEMALE:
                    return $this->name_w ? : $this->name;
                case UserInfo::GENDER_MALE:
                    return $this->name_m ? : $this->name;
            }
        }
        return $this->name;
    }

    /**
     * Puts out an array of all gendered userroles for a user in a certain
     * context
     * 
     * @param string $context The context
     * @param string $user The user id
     * @return array All roles
     */
    public static function getUserRoles($context, $user) {
        $roles = array();
        $groups = self::findByRange_id($context);
        foreach ($groups as $group) {
            $roles = array_merge($roles, $group->getFullGenderedPaths($user));
        }
        return $roles;
    }

    /**
     * Checks if a statusgroup has a folder.
     * 
     * @return boolean <b>true</> if the statusgroup has a folder, else 
     * <b>false</b>
     */
    public function hasFolder() {
        if (!$this->folderid) {
            $query = "SELECT folder_id FROM folder WHERE range_id = ?";
            $statement = DBManager::get()->prepare($query);
            $statement->execute(array($this->id));
            $this->folderid = $statement->fetchColumn();
        }
        return $this->folderid;
    }

    /**
     * Delete or create a filder
     * 
     * @param boolean $set <b>true</b> Create a folder
     * <b>false</b> Delete the folder
     */
    public function updateFolder($set) {
        if ($this->hasFolder() && !$set) {
            delete_folder($this->hasFolder(), true);
        }
        if (!$this->hasFolder() && $set) {
            create_folder((_("Dateiordner der Gruppe:") . ' ' . $this->name), (_("Ablage f�r Ordner und Dokumente dieser Gruppe")), $this->id, 15);
        }
    }

    /**
     * Checks if a user is a member of this group
     * 
     * @param string $user_id The user id
     * @return boolean <b>true</b> if user is a member of this group
     */
    public function isMember($user_id = null) {
        if ($user_id == null) {
            $user_id = $GLOBALS['user']->id;
        }
        foreach ($this->members as $member) {
            if ($member->user_id == $user_id) {
                return true;
            }
        }
        return false;
    }

    /**
     * Displayfunction to show the places left in this group
     * 
     * @return string displaystring
     */
    public function getPlaces() {
        return $this->size ? "( " . min(count($this->getUsers()), $this->size) . " / {$this->size} )" : "";
    }

    /**
     * Remove all users of this group
     */
    public function removeAllUsers() {
        StatusgruppeUser::deleteBySQL('statusgruppe_id = ?', array($this->id));
    }

    /**
     * Remove one user of this group
     * 
     * @param string $user_id The user id
     */
    public function removeUser($user_id) {
        // For performance issues we do this manually
        $db = DBManager::get();
        // Get user's position for later resorting
        $query = "SELECT position FROM statusgruppe_user WHERE statusgruppe_id = ? AND user_id = ?";
        $statement = $db->prepare($query);
        $statement->execute(array($this->id, $user_id));
        $position = $statement->fetchColumn() ? : 0;

        // Delete user from statusgruppe
        $query = "DELETE FROM statusgruppe_user WHERE statusgruppe_id = ? AND user_id = ?";
        $statement = $db->prepare($query);
        $statement->execute(array($this->id, $user_id));

        // Resort members
        $query = "UPDATE statusgruppe_user SET position = position - 1 WHERE statusgruppe_id = ? AND position > ?";
        $statement = $db->prepare($query);
        $statement->execute(array($this->id, $position));
    }

    /**
     * Adds a user to a group
     * 
     * @param string $user_id The user id
     * @param boolean $check if <b>true</b> checks if there is space left in
     * this group
     * @return boolean <b>true</b> if user was added
     */
    public function addUser($user_id, $check = false) {
        if ($check && !$this->userMayJoin($user_id)) {
            return false;
        }
        $user = StatusgruppeUser(array($this->id, $user_id));
        $user->store();
        return true;
    }

    /**
     * Checks if a user could join this group
     * 
     * @param string $user_id The user id
     * @return boolean <b>true</b> if user is allowed to join
     */
    public function userMayJoin($user_id) {
        return !$this->isMember($user_id) && $this->hasSpace() && ($this->selfAssign != 2 || !$this->userHasExclusiveGroup($user_id));
    }

    /**
     * Checks if the user is already in an exclusive group of this range
     * 
     * @param string $user_id The user id
     * @return boolean <b>true</b> if user has already an exclusive group
     */
    public function userHasExclusiveGroup($user_id) {
        $sql = "SELECT 1 FROM statusgruppe_user JOIN statusgruppen USING (statusgruppe_id) WHERE range_id = ? AND user_id = ?";
        $stmt = DBManager::get()->prepare($sql);
        $stmt->execute(array($this->range_id, $user_id));
        return $stmt->fetchColumn();
    }

    /**
     * Checks if there is free space in this group
     * 
     * @return <b>true</b> if there is free space
     */
    public function hasSpace() {
        return $this->selfassign && ($this->size || count($this->members) < $this->size);
    }

    /**
     * Move a user to a position of a group
     * 
     * @param string $user 
     * @param type $pos
     */
    public function moveUser($user_id, $pos) {
        $statususer = new StatusgruppeUser(array($this->id, $user_id));
        if ($pos > $statususer->position) {
            $sql = "UPDATE statusgruppe_user SET position = position - 1 WHERE statusgruppe_id = ? AND position > ? AND position <= ?";
        } else {
            $sql = "UPDATE statusgruppe_user SET position = position + 1 WHERE statusgruppe_id = ? AND position < ? AND position >= ?";
        }
        $db = DBManager::get();
        $stmt = $db->prepare($sql);
        $stmt->execute(array($this->id, $statususer->position, $pos));

        $sql2 = "UPDATE statusgruppe_user SET position = ? WHERE statusgruppe_id = ? AND user_id = ?";
        $stmt2 = $db->prepare($sql2);
        $stmt2->execute(array($pos, $this->id, $statususer->user_id));
    }

}

?>
