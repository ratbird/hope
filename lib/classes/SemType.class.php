<?php

/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 * 
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */

if (isset($GLOBALS['SEM_TYPE'])) {
    $GLOBALS['SEM_TYPE_OLD_VAR'] = $GLOBALS['SEM_TYPE'];
}

/**
 * Class to define and manage attributes of seminar types.
 * Usually all sem-types are stored in a global variable $SEM_TYPE which is 
 * an array of SemType objects. 
 * 
 * SemType::getTypes() gets you all seminar types in an array.
 * 
 * This class only represents the name of the type and gives a relation to a
 * sem_class.
 */
class SemType implements ArrayAccess 
{
    protected $data = array();
    static protected $sem_types = null;
    
    /**
     * Constructor can be set with integer of sem_class_id or an array of
     * the old $SEM_CLASS style.
     * @param integer | array $data 
     */
    public function __construct($data) {
        $db = DBManager::get();
        if (is_int($data)) {
            $statement = $db->prepare("SELECT * FROM sem_types WHERE id = :id ");
            $statement->execute(array('id' => $data));
            $this->data = $statement->fetch(PDO::FETCH_ASSOC);
        } else {
            $this->data = $data;
        }
    }
    
    /**
     * Returns the number of seminars of this sem_type in Stud.IP
     * @return integer 
     */
    public function countSeminars() {
        $db = DBManager::get();
        $statement = $db->prepare("SELECT COUNT(*) FROM seminare WHERE status = :sem_type ");
        $statement->execute(array('sem_type' => $this->data['id']));
        return $statement->fetch(PDO::FETCH_COLUMN, 0);
    }
    
    /**
     * stores all data in the database 
     * @return boolean success
     */
    public function store() {
        $db = DBManager::get();
        $statement = $db->prepare(
            "UPDATE sem_classes " .
                "SET name = :name, " .
                "class = :class, " .
                "chdate = UNIX_TIMESTAMP() " .
            "WHERE id = :id ".
        "");
        return $statement->execute(array(
            'id' => $this->data['id'],
            'name' => $this->data['name']
        ));
    }
    
    /**
     * Sets an attribute of sem_type->data
     * @param string $offset
     * @param mixed $value 
     */
    public function set($offset, $value) {
        $this->data[$offset] = $value;
    }
    
    public function getClass() {
        return $GLOBALS['SEM_CLASS'][$this->data['class']];
    }
    
    /***************************************************************************
     *                          ArrayAccess methods                            *
     ***************************************************************************/
    
    /**
     * deprecated, does nothing, should not be used
     * @param string $offset
     * @param mixed $value 
     */
    public function offsetSet($offset, $value)
    {
    }
    
    /**
     * Compatibility function with old $SEM_TYPE variable for plugins. Maps the 
     * new array-structure to the old boolean values.
     * @param integer $offset: name of attribute
     * @return boolean|(localized)string
     */
    public function offsetGet($offset)
    {
        switch ($offset) {
            case "name":
                return gettext($this->data['name']);
            case in_array($offset, array("title_dozent", "title_tutor", "title_autor")):
                $sem_class = $this->getClass();
                $title = array(gettext($sem_class[$offset]), gettext($sem_class[$offset.'_plural']));
                return $title[0] || $title[1] ? $title : $this->data[$offset];
        }
        //ansonsten
        return $this->data[$offset];
    }
    
    /**
     * ArrayAccess method to check if an attribute exists.
     * @param type $offset
     * @return type 
     */
    public function offsetExists($offset) 
    {
        return isset($this->data[$offset]);
    }
    
    /**
     * deprecated, does nothing, should not be used
     * @param string $offset
     */
    public function offsetUnset($offset) 
    {
    }
    
    /***************************************************************************
     *                            static methods                               *
     ***************************************************************************/
    
    /**
     * Returns an array of all SemTypes in Stud.IP. Equivalent to global
     * $SEM_TYPE variable. This variable is statically stored in this class.
     * @return array of SemType
     */
    static public function getTypes() {
        if (!is_array(self::$sem_types)) {
            $db = DBManager::get();
            self::$sem_types = array();
            try {
                $statement = $db->prepare(
                    "SELECT * FROM sem_types ORDER BY id ASC " .
                "");
                $statement->execute();
                $type_array = $statement->fetchAll(PDO::FETCH_ASSOC);
                foreach ($type_array as $sem_class) {
                    self::$sem_types[$sem_class['id']] = new SemType($sem_class);
                }
            } catch(Exception $e) {
                //for use without or before migration 92
                $type_array = $GLOBALS['SEM_TYPE_OLD_VAR'];
                ksort($type_array);
                foreach ($type_array as $id => $type) {
                    self::$sem_types[$id] = new SemType($type);
                }
            }
        }
        return self::$sem_types;
    }
        
    /**
     * Static method only to keep the translationstrings of the values. It is 
     * never used within the system.
     */
    static private function localization() {
        _("Vorlesung");
        _("Seminar");
        _("Übung");
        _("Praktikum");
        _("Colloquium");
        _("Kolloquium");
        _("Forschungsgruppe");
        _("sonstige");
        _("Gremium");
        _("Projektgruppe");
        _("Kulturforum");
        _("Veranstaltungsboard");
        _("Studiengruppe");
        
    }
    
}

$GLOBALS['SEM_TYPE'] = SemType::getTypes();
