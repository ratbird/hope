<?php
# Lifter010: TODO
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

class LockRule extends SimpleORMap
{

    static function find($id)
    {
        return SimpleORMap::find(__CLASS__, $id);
    }

    static function findBySql($where)
    {
        return SimpleORMap::findBySql(__CLASS__, $where);
    }

    static function findBySeminar($seminar_id)
    {
        $db = DBManager::get();
        $lock_rule_id = $db->query("SELECT lock_rule FROM seminare WHERE seminar_id = " . $db->quote($seminar_id))
                           ->fetchColumn();
        return self::find($lock_rule_id);
    }

    static function findByInstitute($institute_id)
    {
        $db = DBManager::get();
        $lock_rule_id = $db->query("SELECT lock_rule FROM Institute WHERE Institut_id = " . $db->quote($institute_id))
                           ->fetchColumn();
        return self::find($lock_rule_id);
    }

    static function findByUser($user_id)
    {
        $db = DBManager::get();
        $lock_rule_id = $db->query("SELECT lock_rule FROM user_info WHERE user_id = " . $db->quote($user_id))
                           ->fetchColumn();
        return self::find($lock_rule_id);
    }

    static function findAllByType($type)
    {
        return self::findBySQL("object_type = " . DbManager::get()->quote($type) . " ORDER BY name");
    }

    static function deleteBySql($where)
    {
        return SimpleORMap::deleteBySql(__CLASS__, $where);
    }

    /**
     *
     * @param string $id primary key of table
     */
    function __construct($id = null)
    {
        $this->db_table = 'lock_rules';
        parent::__construct($id);
        if ($this->isNew() && !$this->content['attributes'] instanceof ArrayObject) {
            $this->content['attributes'] = $this->convertJsonToArray($this->content['attributes']);
        }
    }

    function setData($data, $reset)
    {
        $ret = parent::setData($data, $reset);
        $this->content['attributes'] = $this->convertJsonToArray($this->content['attributes']);
        return $ret;
    }

    function store()
    {
        $this->content['attributes'] = $this->convertArrayToJson($this->content['attributes']);
        return parent::store();
    }

    function convertJsonToArray($attributes_json)
    {
        return new ArrayObject((array)json_decode($attributes_json, true));
    }

    function convertArrayToJson($attributes_array)
    {
        return json_encode((array)$attributes_array);
    }

    function delete()
    {
        $id = $this->getId();
        $object_type = $this->object_type;
        $ret = parent::delete();

        $db = DBManager::get();
        $tables['sem'] = 'seminare';
        $tables['inst'] = 'Institute';
        $tables['user'] = 'user_data';
        $db->exec("UPDATE " . $tables[$object_type] . " SET lock_rule='' WHERE lock_rule = " . $db->quote($id));
        return $ret;
    }

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