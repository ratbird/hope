<?php
# Lifter010: TODO
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
    /**
     * cache of created UserConfig instances
     * @var array
     */
    private static $instances;
    /**
     * user_id
     * @var string
     */
    private $user_id;

    /**
     * returns cached instance for given user_id
     * creates new objects if needed
     * @param string $user_id
     * @return UserConfig
     */
    public static function get($user_id)
    {
        if (self::$instances[$user_id] === null) {
            $config = new UserConfig($user_id);
            self::$instances[$user_id] = $config;
        }
        return self::$instances[$user_id];
    }

    /**
     * set cached instance for given user_id
     * use for testing or to unset cached instance by passing
     * null as second param
     * @param string $user_id
     * @param UserConfig $my_instance
     */
    public static function set($user_id, $my_instance)
    {
        self::$instances[$user_id] = $my_instance;
    }

    /**
     * passing null as first param is for compatibility and
     * should be considered deprecated.
     * passing data array as second param only for testing
     * @param string $user_id
     * @param array $data
     */
    function __construct($user_id = null, $data = null)
    {
        if($user_id !== null) {
            $this->setUserId($user_id ? $user_id : $GLOBALS['user']->id, $data);
        }
    }

    /* (non-PHPdoc)
     * @see lib/classes/Config::fetchData()
     */
    protected function fetchData($data = null)
    {
        if ($data !== null) {
            $this->data = $data;
        } else {
            $this->data = array();
            foreach(Config::get()->getFields('user') as $field){
                $this->data[$field] = Config::get()->$field;
                $metadata[$field] = Config::get()->getMetadata($field);
            }
            $db = DbManager::get();
            $rs = $db->query("SELECT field, value FROM user_config WHERE user_id = " . $db->quote($this->user_id));
            while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
                switch ($metadata[$row['field']]['type']) {
                    case 'integer':
                        $value = (int)$row['value'];
                        break;
                    case 'boolean':
                        $value = (bool)$row['value'];
                        break;
                    default:
                        $value = $row['value'];
                }
                $this->data[$row['field']] = $row['value'];
            }
        }
    }

    /**
     * kept for compatibility, should be private
     *
     * @deprecated
     * @param string $user_id
     */
    function setUserId($user_id)
    {
        $this->user_id = $user_id;
        $this->fetchData();
    }

    /**
     * returns the user id
     *
     * @return string
     */
    function getUserId()
    {
        return $this->user_id;
    }

    /* old style usage with $user_id, $key as params
     * still works but is deprecated
     * (non-PHPdoc)
     * @see lib/classes/Config::getValue()
     */
    function getValue($field)
    {
        $args = func_get_args();
        if(count($args) > 1) {
            list($user_id, $key) = $args;
            if($user_id !== null && $key !== null) {
                $ret = UserConfig::get($user_id)->$key;
            }
            if($user_id === null) {
                $ret = parent::getValue($key);
            }
            trigger_error('deprecated use of ' . __METHOD__, E_USER_NOTICE);
            return $ret;
        }
        return parent::getValue($field);
    }

    /* old style usage with $value, $user_id, $key as params
     * still works but is deprecated
     * (non-PHPdoc)
     * @see lib/classes/Config::setValue()
     */
    function setValue($field, $value)
    {
        $args = func_get_args();
        if(count($args) > 2) {
            list($value, $user_id, $key) = $args;
            if($user_id !== null && $key !== null) {
                $ret = UserConfig::get($user_id)->store($key, $value);
            }
            if($user_id === null && $key !== null) {
                $ret = $this->store($key, $value);
            }
            trigger_error('deprecated use of ' . __METHOD__, E_USER_NOTICE);
            return $ret;
        }
        return parent::setValue($field, $value);
    }

    /**
     * old style usage with $user_id, $key as params
     * still works but is deprecated
     * @param string $field
     * @return bool
     */
    function unsetValue($field)
    {
        $args = func_get_args();
        if(count($args) > 1) {
            list($user_id, $key) = $args;
            if($user_id !== null && $key !== null) {
                $ret = UserConfig::get($user_id)->delete($key);
            }
            if($user_id === null) {
                $ret = $this->delete($key);
            }
            trigger_error('deprecated use of ' . __METHOD__, E_USER_NOTICE);
            return $ret;
        }
        return $this->delete($field);
    }

    /* (non-PHPdoc)
     * @see lib/classes/Config::store()
     */
    function store($field, $value)
    {

        $entry = UserConfigEntry::findByFieldAndUser($field, $this->user_id);
        if($entry === null) {
            $entry = new UserConfigEntry();
            $entry->user_id = $this->user_id;
            $entry->field = $field;
            $entry->comment = '';
        }
        $metadata = Config::get()->getMetadata($field);
        switch ($metadata['type']) {
            case 'integer':
            case 'boolean':
                $value = (int)$value;
            break;
            default:
                $value = (string)$value;
        }
        $entry->value = $value;
        $ret = $entry->store();
        if ($ret) {
            $this->fetchData();
        }
        return $ret;

    }

    /* (non-PHPdoc)
     * @see lib/classes/Config::delete()
     */
    function delete($field)
    {
        $entry = UserConfigEntry::findByFieldAndUser($field, $this->user_id);
        if($entry !== null) {
            if($ret = $entry->delete()) {
                unset($this->data[$field]);
            }
            return $ret;
        } else {
            return null;
        }
    }

}
