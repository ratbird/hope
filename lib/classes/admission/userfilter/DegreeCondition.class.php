<?php

/**
 * DegreeCondition.class.php
 * 
 * All conditions concerning the study degree in Stud.IP can be specified here.
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Thomas Hackl <thomas.hackl@uni-passau.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

require_once(realpath(dirname(__FILE__).'/..').'/UserFilterField.class.php');

class DegreeCondition extends UserFilterField
{
    // --- ATTRIBUTES ---


    /**
     * Standard constructor.
     */
    public function __construct($fieldId='') {
        $this->validCompareOperators = array(
            '=' => _('gleich'),
            '!=' => _('ungleich')
        );
        // Get all available degrees from database.
        $stmt = DBManager::get()->query(
            "SELECT DISTINCT `abschluss_id`, `name` ".
            "FROM `abschluss` ORDER BY `name` ASC");
        while ($current = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->validValues[$current['abschluss_id']] = $current['name'];
        }
        if ($fieldId) {
            $this->id = $fieldId;
            $this->load();
        } else {
            $this->id = $this->generateId();
        }
    }

    /**
     * Get this field's display name.
     *
     * @return String
     */
    public function getName()
    {
        return _("Abschluss");
    }

    /**
     * Compares all the users' degrees by using the specified compare operator
     * and returns all users that fulfill the condition. This can be
     * an important informatione when checking on validity of a combination
     * of conditions.
     * 
     * @return Array All users that are affected by the current condition 
     * field.
     */
    public function getUsers() {
        $users = array();
        // Get all the users that fulfill the degree condition.
        $stmt = DBManager::get()->prepare(
            "SELECT DISTINCT `user_id` ".
            "FROM `user_studiengang` ".
            "WHERE `abschluss_id`".$this->compareOperator."?");
        $stmt->execute(array($this->value));
        while ($current = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $users[] = $current['user_id'];
        }
        return $users;
    }

    /**
     * Gets the value for the given user that is relevant for this
     * condition field. Here, this method looks up the study degree(s) 
     * for the user. These can then be compared with the required degrees
     * whether they fit.
     * 
     * @param  String $userId User to check.
     * @param  Array additional conditions that are required for check.
     * @return The value(s) for this user.
     */
    public function getUserValues($userId, $additional=null) {
        $result = array();
        // Get degrees for user.
        $stmt = DBManager::get()->prepare(
            "SELECT DISTINCT `abschluss_id` ".
            "FROM `user_studiengang` ".
            "WHERE `user_id`=?");
        $stmt->execute(array($userId));
        while ($current = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $current['abschluss_id'];
        }
        return $result;
    }

} /* end of class DegreeCondition */

?>