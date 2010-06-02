<?php
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
    
    protected $data = array();
    protected $metadata = array();
    
    public static function get()
    {
        if (self::$instance === null) {
            $config = new Config();
            self::$instance = $config;
        }
        return self::$instance;
    }
    
    public static function getInstance()
    {
        return self::get();
    }
    
    public static function set($my_instance)
    {
        self::$instance = $my_instance;
    }
    
    function __construct($data = null)
    {
        $this->fetchData($data);
    }
    
    function extractAllGlobal() {
        foreach ($this->getFields('global') as $key) {
            $GLOBALS[$key] = $this->getValue($key);
        }
    }

    function getFields($range = null, $section = null, $prefix = null)
    {
        $filter = array();
        if(in_array($range, words('global user'))){
            $filter[] = '$a["range"]=="'.$range.'"';
        }
        if($section){
            $filter[] = '$a["section"]=="'.$section.'"';
        }
        if($prefix){
            $filter[] = 'preg_match("/^'.preg_quote($prefix, '/').'/i", $a["field"])';
        }
        if(count($filter)){
            $filterfunc = create_function('$a', 'return ' . join(' && ', $filter) .  ';');
            $ret = array_keys(array_filter($this->metadata, $filterfunc));
        } else {
            $ret = array_keys($this->metadata);
        }
        return $ret;
    }
    
	function getMetadata($field)
	{
		return $this->metadata[$field];
	}
	
    function getValue ($field) {
        if (array_key_exists($field, $this->data)) {
            return $this->data[$field];
        }
        if (isset($GLOBALS[$field]) && !isset($_REQUEST[$field])) {
            return $GLOBALS[$key];
        }
    }
    
    function setValue ($field, $value) {
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
    
    function __get($field) {
        return $this->getValue($field);
    }
    
    function __set($field, $value) {
         return $this->setValue($field, $value);
    }
    
    function __isset($field) {
        return isset($this->data[$field]);
    }
    /**
     * ArrayAccess: Check whether the given offset exists.
     */
    public function offsetExists ($offset)
    {
        return isset($this->$offset);
    }

    /**
     * ArrayAccess: Get the value at the given offset.
     */
    public function offsetGet ($offset)
    {
        return $this->$offset;
    }

    /**
     * ArrayAccess: Set the value at the given offset.
     */
    public function offsetSet ($offset, $value)
    {
        $this->$offset = $value;
    }
	
    public function offsetUnset ($offset)
    {
    	
    }
    
    public function count ()
    {
    	return count($this->data);
    }
    
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
                    default:
                        $value = (string)$row['value'];
                        $row['type'] = 'string';
                }
                $this->data[$row['field']] = $row['value'];
                $this->metadata[$row['field']] = array_intersect_key($row, array_flip(words('type section range description is_default comment')));
				$this->metadata[$row['field']]['field'] = $row['field'];
            }
        }
    }
    
    function store($field, $values)
    {
        if (!is_array($values)) {
            $values['value'] = $values;
        }
        $entries = ConfigEntry::findByField($field);
        if (count($entries)) {
            if (isset($values['value'])) {
                if(count($entries) == 1) {
                    $entries[1] = $entries[0];
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
                   NotificationCenter::postNotification('ConfigValueChanged', $this, array('field' => $field, 'old_value' => $old_value, 'new_value' => $value_entry->value));
                }
           }
            return $ret > 0;
        } else {
            throw new InvalidArgumentException($field . " not found in config table");
        }
    }
    
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
        if(!$entry->type) {
            $entry->type = 'string';
        }
        return $entry->store() ? $entry : null;
    }
}