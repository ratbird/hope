<?php
# Lifter010: TODO
/**
 * Config.class.php
 * provides access to global configuration
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

require_once 'SimpleORMap.class.php';
require_once 'ConfigEntry.class.php';

class Config implements ArrayAccess, Countable, IteratorAggregate
{

    private static $instance = null;

    /**
     * contains all config entries as field => value pairs
     * @var array
     */
    protected $data = array();
    /**
     * contains additional metadata for config fields
     * @var array
     */
    protected $metadata = array();

    /**
     * returns singleton instance
     * @return Config
     */
    public static function get()
    {
        if (self::$instance === null) {
            $config = new Config();
            self::$instance = $config;
        }
        return self::$instance;
    }

    /**
     * alias of Config::get() for compatibility
     * @return Config
     */
    public static function getInstance()
    {
        return self::get();
    }

    /**
     * use to set singleton instance for testing
     * or to unset by passing null
     * @param Config $my_instance
     */
    public static function set($my_instance)
    {
        self::$instance = $my_instance;
    }

    /**
     * pass array of config entries in field => value pairs
     * to circumvent fetching from database
     * @param array $data
     */
    function __construct($data = null)
    {
        $this->fetchData($data);
    }

    /**
     * export all global config entries into global namespace
     */
    function extractAllGlobal() {
        foreach ($this->getFields('global') as $key) {
            $GLOBALS[$key] = $this->getValue($key);
        }
    }

    /**
     * returns a list of config entry names, filtered by
     * given params
     * @param string filter by range: global or user
     * @param string filter by section
     * @param string filter by part of name
     * @return array
     */
    function getFields($range = null, $section = null, $name = null)
    {
        $filter = array();
        if (in_array($range, words('global user'))) {
            $filter[] = '$a["range"]=="'.$range.'"';
        }
        if ($section) {
            $filter[] = '$a["section"]=="'.$section.'"';
        }
        if ($name) {
            $filter[] = 'preg_match("/'.preg_quote($name, '/').'/i", $a["field"])';
        }
        if (count($filter)) {
            $filterfunc = create_function('$a', 'return ' . join(' && ', $filter) .  ';');
            $ret = array_keys(array_filter($this->metadata, $filterfunc));
        } else {
            $ret = array_keys($this->metadata);
        }
        return $ret;
    }

    /**
     * returns metadata for config entry
     * @param srting $field
     * @return array
     */
    function getMetadata($field)
    {
        return $this->metadata[$field];
    }

    /**
     * returns value of config entry
     * for compatibility reasons an existing variable in global
     * namespace with the same name is also returned
     * @param string $field
     * @return Ambigous
     */
    function getValue($field) {
        if (array_key_exists($field, $this->data)) {
            return $this->data[$field];
        }
        if (isset($GLOBALS[$field]) && !isset($_REQUEST[$field])) {
            return $GLOBALS[$field];
        }
    }

    /**
     * set config entry to given value, but don't store it
     * in database
     * @param string $field
     * @param unknown_type $value
     * @return
     */
    function setValue($field, $value) {
        if (array_key_exists($field, $this->data)) {
            return $this->data[$field] = $value;
        }
    }

    /**
     * IteratorAggregate
     */
    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }
    /**
     * magic method for dynamic properties
     */
    function __get($field) {
        return $this->getValue($field);
    }
    /**
     * magic method for dynamic properties
     */
    function __set($field, $value) {
         return $this->setValue($field, $value);
    }
    /**
     * magic method for dynamic properties
     */
    function __isset($field) {
        return isset($this->data[$field]);
    }
    /**
     * ArrayAccess: Check whether the given offset exists.
     */
    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }
    /**
     * ArrayAccess: Get the value at the given offset.
     */
    public function offsetGet($offset)
    {
        return $this->$offset;
    }
    /**
     * ArrayAccess: Set the value at the given offset.
     */
    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }
    /**
     * ArrayAccess: unset the value at the given offset (not applicable)
     */
    public function offsetUnset($offset)
    {

    }
    /**
     * Countable
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * fetch config data from table config
     * pass array to override database access
     * @param array $data
     */
    protected function fetchData($data = null)
    {
        if ($data !== null) {
            $this->data = $data;
        } else {
            $this->data = array();
            $db = DbManager::get();
            $rs = $db->query("SELECT field, value, type, section, `range`, description, comment, is_default FROM `config` ORDER BY is_default DESC, section, field");
            while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
                switch ($row['type']) {
                    case 'integer':
                        $value = (int)$row['value'];
                        break;
                    case 'boolean':
                        $value = (bool)$row['value'];
                        break;
                    case 'array':
                        $value = studip_utf8decode((array)json_decode($row['value'], true));
                        break;
                    default:
                        $value = (string)$row['value'];
                        $row['type'] = 'string';
                }
                $this->data[$row['field']] = $value;
                $this->metadata[$row['field']] = array_intersect_key($row, array_flip(words('type section range description is_default comment')));
                $this->metadata[$row['field']]['field'] = $row['field'];
            }
        }
    }

    /**
     * store new value for existing config entry in database
     * posts notification ConfigValueChanged if entry is changed
     * @param string $field
     * @param string $data
     * @throws InvalidArgumentException
     * @return boolean
     */
    function store($field, $data)
    {
        if (!is_array($data) || !isset($data['value'])) {
            $values['value'] = $data;
        } else {
            $values = $data;
        }
        switch ($this->metadata[$field]['type']) {
            case 'integer':
            case 'boolean':
                $values['value'] = (int)$values['value'];
            break;
            case 'array' :
                 $values['value'] = json_encode(studip_utf8encode($values['value']));
            break;
            default:
                $values['value'] = (string)$values['value'];
        }
        $entries = ConfigEntry::findByField($field);
        if (count($entries)) {
            if (isset($values['value'])) {
                if(count($entries) == 1 &&  $entries[0]->is_default == 1) {
                    $entries[1] = clone $entries[0];
                    $entries[1]->setId($entries[1]->getNewId());
                    $entries[1]->setNew(true);
                    $entries[1]->is_default = 0;
                }
                $value_entry = $entries[0]->is_default == 1 ? $entries[1] : $entries[0];
                $old_value = $value_entry->value;
                $value_entry->value = $values['value'];
            }
            foreach ($entries as $entry) {
                if (isset($values['section'])) {
                    $entry->section = $values['section'];
                }
                if (isset($values['comment'])) {
                    $entry->comment = $values['comment'];
                }
                $ret += $entry->store();
            }
            if ($ret) {
                $this->fetchData();
                if (isset($value_entry)) {
                   NotificationCenter::postNotification('ConfigValueDidChange', $this, array('field' => $field, 'old_value' => $old_value, 'new_value' => $value_entry->value));
                }
           }
            return $ret > 0;
        } else {
            throw new InvalidArgumentException($field . " not found in config table");
        }
    }

    /**
     * creates a new config entry in database
     * @param string name of entry
     * @param array data to insert as assoc array
     * @throws InvalidArgumentException
     * @return Ambigous <NULL, ConfigEntry>
     */
    function create($field, $data = array())
    {
        if (!$field) {
            throw new InvalidArgumentException("config fieldname is mandatory");
        }
        $exists = ConfigEntry::findByField($field);
        if (count($exists)) {
            throw new InvalidArgumentException("config $field already exists");
        }
        $entry = new ConfigEntry();
        $entry->setData($data);
        $entry->setId(md5($field));
        $entry->field = $field;
        $entry->is_default = 1;
        $entry->comment = '';
        if (!isset($data['value'])) {
            $entry->value = '';
        }
        if(!$entry->type) {
            $entry->type = 'string';
        }
        $ret = $entry->store() ? $entry : null;
        if ($ret) {
            $this->fetchData();
        }
        return $ret;
    }

    /**
     * delete config entry from database
     * @param string name of entry
     * @throws InvalidArgumentException
     * @return integer number of deleted rows
     */
    function delete($field)
    {
        if (!$field) {
            throw new InvalidArgumentException("config fieldname is mandatory");
        }
        $deleted = ConfigEntry::deleteBySql("field=" . DbManager::get()->quote($field));
        if ($deleted) {
            $this->fetchData();
        }
        return $deleted;
    }
}
