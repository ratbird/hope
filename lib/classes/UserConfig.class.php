<?php
/**
 * UserConfig.class.php
 * provides access to user preferences 
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Noack <noack@data-quest.de>
 * @copyright   2010 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
*/

require_once 'lib/classes/Config.class.php';
require_once 'UserConfigEntry.class.php';

class UserConfig extends Config
{
    private static $instances;
    private $user_id;
    
    public static function get($user_id)
    {
        if (self::$instances[$user_id] === null) {
            $config = new UserConfig($user_id);
            self::$instances[$user_id] = $config;
        }
        return self::$instances[$user_id];
    }
    
    public static function set($user_id, $my_instance)
    {
        self::$instances[$user_id] = $my_instance;
    }
    
    function __construct($user_id = null, $data = null)
    {
        if($user_id !== null) {
            $this->setUserId($user_id ? $user_id : $GLOBALS['user']->id, $data);
        }
    }
    
    function fetchData($data = null)
    {
        if ($data !== null) {
            $this->data = $data;
        } else {
            $this->data = array();
            foreach(Config::get()->getFields('user') as $field){
                $this->data[$field] = Config::get()->$field;
            }
            $db = DbManager::get();
            $rs = $db->query("SELECT DISTINCT uc.field,uc.value,c.type FROM user_config uc LEFT JOIN config c USING(field) WHERE user_id = " . $db->quote($this->user_id));
            while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
                switch ($row['type']) {
                    case 'integer':
                        $value = (int)$row['value'];
                        break;
                    case 'boolean':
                        $value = (bool)$row['value'];
                        break;
                    default:
                        $value = trim($row['value']);
                }
                $this->data[$row['field']] = $row['value'];
            }
        }
    }
    
    function setUserId($user_id)
    {
        $this->user_id = $user_id;
        $this->fetchData($data);
    }
    
    function getUserId()
    {
        return $this->user_id;
    }
    
    function store($field, $value)
    {
        if (array_key_exists($field, $this->data)) {
            $entry = UserConfigEntry::findByFieldAndUser($field, $this->user_id);
            if($entry === null) {
                $entry = new UserConfigEntry();
                $entry->user_id = $this->user_id;
                $entry->field = $field;
            }
            $entry->value = $value;
            $ret = $entry->store();
            if ($ret) {
                $this->fetchData();
            }
            return $ret;
        }
    }
}
