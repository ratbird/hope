<?php

/**
 * UserFilter.class.php
 * 
 * Conditions for user selection in Stud.IP. A condition is a collection of
 * condition fields, e.g. degree, course of study or semester. Each 
 * condition can have a validity period. 
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

require_once('lib/classes/admission/UserFilterField.class.php');

class UserFilter
{
    // --- ATTRIBUTES ---

    /**
     * All condition fields that form this condition.
     */
    public $fields = array();

    /**
     * Unique identifier for this condition.
     */
    public $id = '';

    // --- OPERATIONS ---

    /**
     * Standard constructor.
     *
     * @param  String conditionId
     * @return UserFilter
     */
    public function __construct($conditionId='')
    {
        UserFilterField::getAvailableFilterFields();
        $this->id = $conditionId;
        if ($conditionId) {
            $this->load();
        } else {
            $this->id = $this->generateId();
        }
        return $this;
    }

    /**
     * Add a new condition field.
     *
     * @param  ConditionField fieldId
     * @return UserFilter
     */
    public function addField($field)
    {
        $this->fields[$field->getId()] = $field;
        $field->setConditionId($this->id);
        return $this;
    }

    /**
     * Deletes the condition and all associated fields.
     */
    public function delete() {
        // Delete condition data.
        $stmt = DBManager::get()->prepare("DELETE FROM `userfilter` 
            WHERE `filter_id`=?");
        $stmt->execute(array($this->id));
        // Delete all defined condition fields.
        foreach ($this->fields as $field) {
            $field->delete();
        }
    }

    /**
     * Generate a new unique ID.
     * 
     * @param  String tableName
     */
    public function generateId() {
        do {
            $newid = md5(uniqid(get_class($this).microtime(), true));
            $id = DBManager::get()->fetchColumn("SELECT `filter_id` 
                FROM `userfilter` WHERE `filter_id`=?", array($newid));
        } while ($id);
        return $newid;
    }

    /**
     * Get all fields (without checking for validity according 
     * to the current time).
     *
     * @return Array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Get ID.
     *
     * @return String
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets all users that fulfill the current condition.
     * 
     * @return Array
     */
    public function getUsers() {
        $users = array();
        foreach ($this->fields as $field) {
            // Check if restrictions for the field value must be taken into consideration.
            $restrictions = array();
            foreach ($field->relations as $className => $related) {
                if ($other = &$this->hasField($className)) {
                    $restrictions[$className] = array(
                        'table' => $other->userDataDbTable,
                        'field' => $other->userDataDbField,
                        'compare' => $other->getCompareOperator(),
                        'value' => $other->getValue()
                    );
                }
            }
            $users = $users ? array_intersect($users, $field->getUsers($restrictions)) : $field->getUsers($restrictions);
        }
        return $users;
    }

    /**
     * Checks whether the current filter object contains a field
     * of the given type.
     *
     * @param String $className the type to check for
     * @return UserFilterField Return the found field or null if not applicable.
     */
    public function hasField($className) {
        foreach ($this->fields as $field) {
            if ($field instanceof $className) {
                return $field;
                break;
            }
        }
        return null;
    }

    /**
     * Is the current condition fulfilled (that means, are all 
     * required field values matched)?
     * 
     * @return boolean
     */
    public function isFulfilled($userId) {
        $fulfilled = true;
        // Check all fields.
        foreach ($this->fields as $field) {
            $fulfilled = $fulfilled && 
                $field->checkValue($field->getUserValues($userId));
        }
        return $fulfilled;
    }

    /**
     * Helper function for loading data from DB.
     */
    public function load() {
        // Load basic condition data.
        $stmt = DBManager::get()->prepare(
            "SELECT * FROM `userfilter` WHERE `filter_id`=? LIMIT 1");
        $stmt->execute(array($this->id));
        if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->id = $data['filter_id'];
            // Load the associated condition fields.
            $stmt = DBManager::get()->prepare(
                "SELECT `field_id`, `type` FROM `userfilter_fields`
                WHERE `filter_id`=?");
            $stmt->execute(array($this->id));
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                /*
                 * Create instance of appropriate UserFilterField subclass.
                 * We just "try" here because the class definition could have 
                 * been removed since saving data to DB.
                 */
                //try {
                    $field = new $data['type']($data['field_id']);
                    $this->fields[$field->getId()] = $field;
                //} catch (Exception $e) {}
            }
        }
    }

    /**
     * Removes the field with the given ID from the condition fields.
     *
     * @param  String fieldId
     * @return UserFilter
     */
    public function removeField($fieldId)
    {
        unset($this->fields[$fieldId]);
        return $this;
    }

    /**
     * Stores data to DB.
     */
    public function store() {
        // Generate new ID if condition entry doesn't exist in DB yet.
        if (!$this->id) {
            $this->id = $this->generateId();
        }
        
        // Store condition data.
        $stmt = DBManager::get()->prepare("INSERT INTO `userfilter` 
            (`filter_id`, `mkdate`, `chdate`)  
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE `chdate`=VALUES(`chdate`)");
        $stmt->execute(array($this->id, time(), time()));
        // Delete removed condition fields from DB.
        DBManager::get()->exec("DELETE FROM `userfilter_fields` 
            WHERE `filter_id`='".$this->id."' AND `field_id` NOT IN ('".
            implode("', '", array_keys($this->fields))."')");
        // Store all fields.
        foreach ($this->fields as $field) {
            $field->store($this->id);
        }
    }

    public function toString() {
        $tpl = $GLOBALS['template_factory']->open('userfilter/display');
        $tpl->set_attribute('filter', $this);
        return $tpl->render();
    }

    public function __toString() {
        return $this->toString();
    }

} /* end of class UserFilter */

?>