<?php
/**
 * LockRule.class.php
 * model class for table lock_rule
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Noack <noack@data-quest.de>
 * @copyright   2011 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
*/

require_once 'SimpleORMap.class.php';

/**
 * this class represents one record of the lock_rules table
 * the column attributes is converted to an ArrayObject and vice-versa,
 * to allow array-like access
 *
 * e.g.
 * @code
 * $lockrule = LockRule::find($id);
 * $lockrule['attributes']['name'] = 1;
 * $lockrule->store();
 * @endcode
 *
 */
class LockRule extends SimpleORMap
{

    /**
     * @see SimpleORMap::find()
     */
    static function find($id)
    {
        return SimpleORMap::find(__CLASS__, $id);
    }

    /**
     * @see SimpleORMap::findBySql()
     */
    static function findBySql($where)
    {
        return SimpleORMap::findBySql(__CLASS__, $where);
    }

    /**
     * returns the lockrule for a course
     *
     * @param string $seminar_id id of a course
     * @return LockRule
     */
    static function findBySeminar($seminar_id)
    {
        $db = DBManager::get();
        $lock_rule_id = $db->query("SELECT lock_rule FROM seminare WHERE seminar_id = " . $db->quote($seminar_id))
                           ->fetchColumn();
        return self::find($lock_rule_id);
    }

    /**
     * returns the lockrule for an institute
     *
     * @param string $institute_id id of an institute
     * @return LockRule
     */
    static function findByInstitute($institute_id)
    {
        $db = DBManager::get();
        $lock_rule_id = $db->query("SELECT lock_rule FROM Institute WHERE Institut_id = " . $db->quote($institute_id))
                           ->fetchColumn();
        return self::find($lock_rule_id);
    }

    /**
     * returns the lockrule for a user
     *
     * @param string $user_id id of a user
     * @return LockRule
     */
    static function findByUser($user_id)
    {
        $db = DBManager::get();
        $lock_rule_id = $db->query("SELECT lock_rule FROM user_info WHERE user_id = " . $db->quote($user_id))
                           ->fetchColumn();
        return self::find($lock_rule_id);
    }

    /**
     * returns all exisiting lockrules for a given entity type
     *
     * @param string $type entity type, one of [sem,inst,user]
     * @return array of LockRule objects
     */
    static function findAllByType($type)
    {
        return self::findBySQL("object_type = " . DbManager::get()->quote($type) . " ORDER BY name");
    }

    /**
     * @see SimpleORMap::deleteBySql()
     */
    static function deleteBySql($where)
    {
        return SimpleORMap::deleteBySql(__CLASS__, $where);
    }

    /**
     * Constructor
     *
     * @param string $id primary key of table lock_rules
     */
    function __construct($id = null)
    {
        $this->db_table = 'lock_rules';
        parent::__construct($id);
        if ($this->isNew() && !$this->content['attributes'] instanceof ArrayObject) {
            $this->content['attributes'] = $this->convertJsonToArray($this->content['attributes']);
        }
    }

    /**
     * @see SimpleORMap::setData()
     */
    function setData($data, $reset)
    {
        $ret = parent::setData($data, $reset);
        $this->content['attributes'] = $this->convertJsonToArray($this->content['attributes']);
        return $ret;
    }

    /**
     * @see SimpleORMap::store()
     */
    function store()
    {
        $this->content['attributes'] = $this->convertArrayToJson($this->content['attributes']);
        return parent::store();
    }

    /**
     * converts a json encoded array to an ArrayObject
     *
     * @param string $attributes_json a json encoded array
     * @return ArrayObject
     */
    private function convertJsonToArray($attributes_json)
    {
        return new ArrayObject((array)json_decode($attributes_json, true));
    }

    /**
     * converts an array (or an ArrayObject) to
     * a json encoded string
     *
     * @param array $attributes_array
     * @return string
     */
    private function convertArrayToJson($attributes_array)
    {
        return json_encode((array)$attributes_array);
    }

    /**
     * @see SimpleORMap::delete()
     */
    function delete()
    {
        $id = $this->getId();
        $object_type = $this->object_type;
        $ret = parent::delete();

        $db = DBManager::get();
        $tables['sem'] = 'seminare';
        $tables['inst'] = 'Institute';
        $tables['user'] = 'user_info';
        $db->exec("UPDATE " . $tables[$object_type] . " SET lock_rule='' WHERE lock_rule = " . $db->quote($id));
        return $ret;
    }

    /**
     * returns the number of uses for this lockrule
     *
     * @return integer
     */
    function getUsage()
    {
        if (!$this->isNew()) {
            $db = DBManager::get();
            $tables['sem'] = 'seminare';
            $tables['inst'] = 'Institute';
            $tables['user'] = 'user_info';
            return $db->query("SELECT COUNT(*) FROM " . $tables[$this->object_type] . " WHERE lock_rule = " . $db->quote($this->getId()))->fetchColumn();
        } else {
            return 0;
        }
    }



}