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

class CSVArrayObject extends ArrayObject
{
    function __construct($input)
    {
        if (is_string($input)) {
            $input = strlen($input) ? array_map('trim', explode(',', $input)) : array();
        }
        parent::__construct((array)$input);
    }

    function __toString()
    {
        return implode(',', (array)$this);
    }
}

class WebserviceAccessRule extends SimpleORMap
{

    static function find($id)
    {
        return SimpleORMap::find(__CLASS__, $id);
    }

    static function findBySql($where)
    {
        return SimpleORMap::findBySql(__CLASS__, $where);
    }

    static function findByApiKey($api_key)
    {
        return self::findBySQL("api_key = " . DbManager::get()->quote($api_key) . " ORDER BY type");
    }

    static function findAll()
    {
        return self::findBySQL("1 ORDER BY api_key, type");
    }

    static function deleteBySql($where)
    {
        return SimpleORMap::deleteBySql(__CLASS__, $where);
    }

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
     *
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

    function setData($data, $reset)
    {
        $ret = parent::setData($data, $reset);
        $this->content['ip_range'] = new CSVArrayObject($this->content['ip_range']);
        return $ret;
    }

    function setValue($field, $value)
    {
        if ($field == 'ip_range' && !$value instanceof CSVArrayObject) {
            $value = new CSVArrayObject($value);
        }
        return parent::setValue($field, $value);
    }

    function getNewId()
    {
        return 0;
    }

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

    function checkMethodName($method)
    {
        return ($method && (!$this->method || strpos($method, $this->method) !== false));
    }
}
