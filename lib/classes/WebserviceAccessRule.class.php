<?php
/**
 * WebserviceAccessRule.class.php
 * model class for table webservice_access_rules
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
 * This class works like an array.
 * The internal array is constructed from a comma separated string
 * When used in an string context, it is automatically converted to a comma
 * separated string
 *
 * Usage:
 * @code
 * $csvarray = new CSVArrayObject('eins,zwei,drei');
 * $csvarray[] = 'vier';
 * echo $csvarray; // prints out "eins,zwei,drei,vier"
 * @endcode
 *
 * @link http://www.php.net/manual/en/class.arrayobject.php
*/
class CSVArrayObject extends ArrayObject
{
    /**
     * Construct an array object from a string of comma separated items
     *
     * @param string $input a string of comma separated items
     */
    function __construct($input)
    {
        if (is_string($input)) {
            $input = strlen($input) ? array_map('trim', explode(',', $input)) : array();
        }
        parent::__construct((array)$input);
    }

    /**
     * magic method for use of object in string context
     *
     * @return string internal array itmes converted to a comma separated list
     */
    function __toString()
    {
        return implode(',', (array)$this);
    }
}

/**
 * this class represents one record of the table webservice_access_rules
 * the column ip_range is converted from a comma separated list to an ArrayObject and vice-versa,
 * to allow array-like access
 *
 * Example:
 * @code
 * $rule = WebserviceAccessRule::find($id);
 * echo $rule['ip_range']; //prints out e.g. 127.0.0.1
 * $rule['ip_range'][] = '192.168.19.0/8';
 * echo $rule['ip_range']; //prints out 127.0.0.1,192.168.19.0/8
 * @endcode
 *
 */
class WebserviceAccessRule extends SimpleORMap
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
     * returns all rules for an given api key
     *
     * @param string $api_key
     * @return array of WebserviceAccessRule objects
     */
    static function findByApiKey($api_key)
    {
        return self::findBySQL("api_key = " . DbManager::get()->quote($api_key) . " ORDER BY type");
    }

    /**
     * returns all rules in db sorted by api key
     *
     * @return array of WebserviceAccessRule objects
     */
    static function findAll()
    {
        return self::findBySQL("1 ORDER BY api_key, type");
    }

    /**
     * @see SimpleORMap::deleteBySql()
     */
    static function deleteBySql($where)
    {
        return SimpleORMap::deleteBySql(__CLASS__, $where);
    }

    /**
     * Checks for given api key, methodname and IP Address if access
     * is granted or not
     *
     * @param string $api_key an api key
     * @param string $method a name of an webservice method
     * @param string $ip an IP Address
     * @return boolean returns true if access fpr given params is allowed
     */
    static function checkAccess($api_key, $method, $ip)
    {
        $rules = self::findByApiKey($api_key);
        $access = false;
        foreach ($rules as $rule) {
            if ($rule->type == 'allow'
                && $rule->checkIpInRange($ip)
                && $rule->checkMethodName($method)) {
                $access = true;
            }
            if ($rule->type == 'deny'
                && $rule->checkIpInRange($ip)
                && $rule->checkMethodName($method)) {
                $access = false;
            }
        }
        return $access;
    }

    /**
     * Constructor
     * @param string $id primary key of table
     */
    function __construct($id = null)
    {
        $this->db_table = 'webservice_access_rules';
        parent::__construct($id);
        if ($this->isNew() && !$this->content['ip_range'] instanceof CSVArrayObject) {
            $this->content['ip_range'] = new CSVArrayObject($this->content['ip_range']);
        }
    }

    /**
     * @see SimpleORMap::setData()
     */
    function setData($data, $reset)
    {
        $ret = parent::setData($data, $reset);
        $this->content['ip_range'] = new CSVArrayObject($this->content['ip_range']);
        return $ret;
    }

    /**
     * @see SimpleORMap::setValue()
     */
    function setValue($field, $value)
    {
        if ($field == 'ip_range' && !$value instanceof CSVArrayObject) {
            $value = new CSVArrayObject($value);
        }
        return parent::setValue($field, $value);
    }

    /**
     * the table uses an auto_increment as primary key, so this always
     * return 0 to get new key on first insert
     *
     * @return integer always 0
     */
    function getNewId()
    {
        return 0;
    }

    /**
     * checks, if a given IP Address is in the range specified
     * for this rule. If there is no specified range, it returns true
     *
     * @param string $check_ip an IP Address
     * @return boolean true if given Address is in specified range
     */
    function checkIpInRange($check_ip)
    {
        if (!ip2long($check_ip)) {
            return false;
        }
        if (!count($this->ip_range)) {
            return true;
        }
        foreach($this->ip_range as $range) {
            list($ip, $mask) = explode('/', $range);
            if (!$mask) {
                $mask = 32;
            }
            if ( (ip2long($check_ip) & ~((1 << (32 - $mask)) - 1)) == ip2long($ip)) {
                return true;
            }
        }
        return false;
    }

    /**
     * checks, if the specified method name for this rule
     * is part of the given one.
     * If there is no specified method name, it returns true
     *
     *
     * @param string $method a webservice method name
     * @return boolean true if given name matches the specified
     */
    function checkMethodName($method)
    {
        return ($method && (!$this->method || strpos($method, $this->method) !== false));
    }
}
