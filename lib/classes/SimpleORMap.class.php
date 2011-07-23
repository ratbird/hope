<?php
# Lifter010: TODO
/**
 * SimpleORMap.class.php
 * simple object-relational mapping
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

class SimpleORMap implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * table row data
     * @var array
     */
    protected $content = array();
    /**
     * table row data
     * @var array
     */
    protected $content_db = array();
    /**
     * new state of entry
     * @var boolean
     */
    protected $is_new = true;

    /**
     * name of db table
     * @var string
     */
    protected $db_table = '';
    /**
     * table columns
     * @var array
     */
    protected $db_fields = null;
    /**
     * primary key columns
     * @var array
     */
    protected $pk = null;

    /**
     * db table metadata
     * @var array
     */
    protected static $schemes;

    /**
     * fetch table metadata from db ro from local cache
     * @param string $db_table
     */
    protected static function TableScheme($db_table)
    {
        if (self::$schemes === null) {
            $cache = StudipCacheFactory::getCache();
            self::$schemes = unserialize($cache->read('DB_TABLE_SCHEMES'));
        }
        if (!isset(self::$schemes[$db_table])) {
            $db = DBManager::get()->query("SHOW COLUMNS FROM $db_table");
            while($rs = $db->fetch(PDO::FETCH_ASSOC)){
                $db_fields[strtolower($rs['Field'])] = array(
                                                            'name' => $rs['Field'],
                                                            'type' => $rs['Type'],
                                                            'key'  => $rs['Key'],
                                                            'null' => $rs['Null'],
                                                            'default' => $rs['Default']
                                                            );
                if ($rs['Key'] == 'PRI'){
                    $pk[] = strtolower($rs['Field']);
                }
            }
            self::$schemes[$db_table]['db_fields'] = $db_fields;
            self::$schemes[$db_table]['pk'] = $pk;
            $cache = StudipCacheFactory::getCache();
            $cache->write('DB_TABLE_SCHEMES', serialize(self::$schemes));
        }
        return isset(self::$schemes[$db_table]);
    }

    /**
     * force reload of cached table metadata
     */
    public static function expireTableScheme()
    {
        StudipCacheFactory::getCache()->expire('DB_TABLE_SCHEMES');
        self::$schemes = null;
    }

    /**
     * returns new instance for given class and key
     * when found in db, else null
     * should be overridden in subclass to omit $class param
     * @param string $class
     * @param string primary key
     * @return null|NULL
     */
    public static function find($class, $id)
    {
        $record = new $class($id);
        if(!$record->isNew()){
            return $record;
        } else {
            return null;
        }
    }

    /**
     * returns array of instances of given class filtered by given sql
     * should be overridden in subclass to omit $class param
     * @param string $class
     * @param string sql clause to use on the right side of WHERE
     * @return array
     */
    public static function findBySQL($class, $where)
    {
        $record = new $class();
        $db = DBManager::get();
        $sql = "SELECT * FROM `" .  $record->db_table . "` WHERE " . $where;
        $rs = $db->query($sql);
        $ret = array();
        $c = 0;
        while($row = $rs->fetch(PDO::FETCH_ASSOC)) {
            $ret[$c] = clone $record;
            $ret[$c]->setData($row, true);
            $ret[$c]->setNew(false);
            ++$c;
        }
        return $ret;
    }

    /**
     * deletes table rows specified by given class and sql clause
     * @param string $class
     * @param string sql clause to use on the right side of WHERE
     * @return number
     */
    public static function deleteBySQL($class, $where)
    {
        $record = new $class();
        $db = DBManager::get();
        $sql = "DELETE FROM `" .  $record->db_table . "` WHERE " . $where;
        return $db->exec($sql);
    }

    /**
     * returns object of given class for given id or null
     * the param could be a string, an assoc array containing primary key field
     * or an already matching object. In all these cases an object is returned
     * 
     * @param mixed $id
     * @return NULL|object
     */
    public static function toObject($class, $id_or_object)
    {
        if ($id_or_object instanceof $class) {
            return $id_or_object;
        }
        if (is_array($id_or_object)) {
            $object = new $class();
            list( ,$pk) = array_values($object->getTableMetadata());
            $key_values = array();
            foreach($pk as $key) {
                if (array_key_exists($key, $id_or_object)) {
                    $key_values[] = $id_or_object[$key];
                }
            }
            if (count($pk) === count($key_values)) {
                if (count($pk) === 1) {
                    $id = $key_values[0];
                } else {
                    $id = $key_values;
                }
            }
        } else {
            $id = $id_or_object;
        }
        return call_user_func(array($class, 'find'), $id);
    }

    /**
     *
     * @param string $id primary key of table
     */
    function __construct($id = null)
    {
        if (!$this->db_table){
            if (defined(strtoupper(get_class($this)) . '_DB_TABLE')){
                $this->db_table = constant(strtoupper(get_class($this)) . '_DB_TABLE');
            } else {
                $this->db_table = strtolower(get_class($this));
            }
        }
        if (!$this->db_fields){
            $this->getTableScheme();
        }
        if ($id){
            $this->setId($id);
            $this->restore();
        }
    }

    protected function getTableScheme()
    {
        if(self::TableScheme($this->db_table)){
            $this->db_fields =& self::$schemes[$this->db_table]['db_fields'];
            $this->pk =& self::$schemes[$this->db_table]['pk'];
        }
    }
    
    function getTableMetadata()
    {
        return array('fields' => $this->db_fields, 'pk' => $this->pk);
    }
    
    /**
     * set primary key for entry, combined keys must be passed as array
     * @param string|array primary key
     * @throws Exception
     * @return boolean
     */
    function setId($id)
    {
        if (!is_array($id)){
            $id = array($id);
        }
        if (count($this->pk) != count($id)){
            throw new Exception("Invalid ID, Primary Key {$this->db_table} is " .join(",",$this->pk));
        } else {
            foreach ($this->pk as $count => $key){
                $this->content[$key] = $id[$count];
            }
            return true;
        }
        return false;
    }

    /**
     * returns primary key, false if none is set
     * @return boolean|string|array
     */
    function getId()
    {
        if (count($this->pk) == 1){
            return $this->content[$this->pk[0]];
        } else {
            foreach ($this->pk as $key){
                $id[] = $this->content[$key];
            }
            return (count($this->pk) == count($id) ? $id : false);
        }
    }

    /**
     * create new unique pk as md5 hash
     * if pk consists of multiple columns, false is returned
     * @return boolean|string
     */
    function getNewId()
    {
        $id = false;
        if (count($this->pk) == 1){
            do {
                $id = md5(uniqid($this->db_table,1));
                $db = DBManager::get()->query("SELECT `{$this->pk[0]}` FROM `{$this->db_table}` "
                    . "WHERE `{$this->pk[0]}` = '$id'");
            } while($db->fetch());
        }
        return $id;
    }


    /**
     * returns data of table row as assoc array or false
     * if no data available
     * @deprecated
     * @return array|boolean
     */
    function getData()
    {
        if ($this->haveData()) {
            return $this->toArray();
        } else {
            return FALSE;
        }
    }

    /**
     * returns data of table row as assoc array
     * @return array
     */
    function toArray()
    {
        $ret = array();
        foreach(array_map('strtolower', array_keys($this->db_fields)) as $field) {
           $ret[$field] = $this->$field;
        }
        return $ret;
    }

    /**
     * returns value of a column
     * @param string $field
     * @return null|string
     */
    function getValue($field)
    {
        $field = strtolower($field);
        return (array_key_exists($field, $this->content) ? $this->content[$field] : null);
    }

    /**
     * sets value of a column
     * @param string $field
     * @param string $value
     * @return string
     */
    function setValue($field, $value)
    {
        $field = strtolower($field);
        $ret = false;
        if($this->db_fields[$field]){
            $ret = ($this->content[$field] = $value);
        }
        return $ret;
    }

    /**
     * magic method for dynamic properties
     */
    function __get($field)
    {
        return $this->getValue($field);
    }
    /**
     * magic method for dynamic properties
     */
    function __set($field, $value)
    {
         return $this->setValue($field, $value);
    }
    /**
     * magic method for dynamic properties
     */
    function __isset($field)
    {
        $field = strtolower($field);
        return isset($this->content[$field]);
    }
    /**
     * ArrayAccess: Check whether the given offset exists.
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->content);
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
     * IteratorAggregate
     */
    public function getIterator()
    {
        return new ArrayIterator($this->content);
    }
    /**
     * Countable
     */
    public function count()
    {
        return count($this->content);
    }

    /**
     * check if given column exists in table
     * @param string $field
     * @return boolean
     */
    function isField($field)
    {
        $field = strtolower($field);
        return isset($this->db_fields[$field]);
    }

    /**
     * set multiple column values
     * if second param is set, existing data in object will be
     * discarded, else new data overrides old data
     * @param array $data assoc array
     * @param boolean $reset
     * @return number of columns changed
     */
    function setData($data, $reset = false)
    {
        $count = 0;
        if ($reset) {
            $this->initializeContent();
        }
        if (is_array($data)) {
            foreach($data as $key => $value) {
                $key = strtolower($key);
                if (isset($this->db_fields[$key])) {
                    $this->content[$key] = $value;
                    ++$count;
                }
            }
        }
        return $count;
    }

    /**
     * check if object is empty
     * @return number of columns with values
     */
    function haveData()
    {
        foreach ($this->content as $c) {
            if ($c !== null) return true;
        }
        return false;
    }

    /**
     * check if object exists in database
     * @return boolean
     */
    function isNew()
    {
        return $this->is_new;
    }

    /**
     * set object to new state
     * @param boolean $is_new
     * @return boolean
     */
    function setNew($is_new)
    {
        return $this->is_new = $is_new;
    }

    /**
     * returns sql clause with current table and pk
     * @return boolean|string
     */
    function getWhereQuery()
    {
        $where_query = null;
        $pk_not_set = array();
        foreach ($this->pk as $key){
            if (isset($this->content[$key])){
                $where_query[] = "`{$this->db_table}`.`{$key}` = "  . DBManager::get()->quote($this->content[$key]);
            } else {
                $pk_not_set[] = $key;
            }
        }
        if (!$where_query || count($pk_not_set)){
            return false;
        }
        return $where_query;
    }

    /**
     * restore entry from database
     * @return boolean
     */
    function restore()
    {
        $where_query = $this->getWhereQuery();

        if ($where_query) {
            $query = "SELECT * FROM `{$this->db_table}` WHERE "
                    . join(" AND ", $where_query);
            $rs = DBManager::get()->query($query)->fetchAll(PDO::FETCH_ASSOC);
            if (isset($rs[0])) {
                if ($this->setData($rs[0], true)){
                    $this->content_db = $this->content;
                    $this->setNew(false);
                    return true;
                } else {
                    $this->setNew(true);
                    return false;
                }
            }
        } else {
            $this->setNew(true);
            $this->initializeContent();
            return FALSE;
        }
    }

    /**
     * store entry in database
     * if data is actually changed triggerChdate() is called
     * @return number|boolean
     */
    function store()
    {

        if ($this->isNew() && !$this->getId()) {
            $this->setId($this->getNewId());
        }

        $where_query = $this->getWhereQuery();

        if ($where_query) {
            foreach ($this->db_fields as $field => $meta) {
                $value = $this->getValue($field);
                if ($value === null && $meta['null'] == 'NO') {
                    $value = $meta['default'];
                    if ($value === null) {
                        throw new Exception($this->db_table . '.' . $field . ' must not be null.');
                    }
                }
                if (is_float($value)) {
                    $value = str_replace(',','.', $value);
                }
                if ($field == 'chdate' && !$this->isFieldDirty($field) && $this->isDirty()) {
                    $value = time();
                }
                if ($field == 'mktime') {
                    if($this->isNew()) {
                        $value = time();
                    } else {
                        continue;
                    }
                }
                if ($value === null) {
                    $query_part[] = "`$field` = NULL ";
                } else {
                    $query_part[] = "`$field` = " . DBManager::get()->quote($value) . " ";
                }
            }


            if (!$this->isNew()){
                $query = "UPDATE `{$this->db_table}` SET "
                . implode(',', $query_part);
                $query .= " WHERE ". join(" AND ", $where_query);
            } else {
                $query = "INSERT INTO `{$this->db_table}` SET "
                . implode(',', $query_part);
            }
            $ret = DBManager::get()->exec($query);
            $this->restore();
            return $ret;
        } else {
            return false;
        }
    }

    /**
     * set chdate column to current timestamp
     * @return boolean
     */
    function triggerChdate()
    {
        if ($this->db_fields['chdate']){
            $this->content['chdate'] = time();
            if ($where_query = $this->getWhereQuery()){
                DBManager::get()->exec("UPDATE `{$this->db_table}` SET chdate={$this->content['chdate']}
                            WHERE ". join(" AND ", $where_query));
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * delete entry from database
     * the object is cleared and turned to new state
     * @return boolean
     */
    function delete()
    {
        if (!$this->isNew()){
            $where_query = $this->getWhereQuery();
            if ($where_query){
                $query = "DELETE FROM `{$this->db_table}` WHERE "
                        . join(" AND ", $where_query);
                DBManager::get()->exec($query);
            }
        }
        $this->setNew(true);
        $this->setData(array(), true);
        return TRUE;
    }
    
    /**
     * init internal content arrays with nulls
     */
    private function initializeContent()
    {
        $this->content = array();
        foreach(array_keys($this->db_fields) as $field) {
            $this->content[$field] = null;
        }
        $this->content_db = $this->content;
    }
    
    /**
     * checks if at least one field was modified since last restore
     * 
     * @return boolean
     */
    public function isDirty() 
    {
        foreach(array_keys($this->db_fields) as $field) {
            if ($this->content[$field] !== $this->content_db[$field]) return true;
        }
        return false;
    }
    
    /**
     * checks if given field was modified since last restore
     * 
     * @param string $field
     * @return boolean
     */
    public function isFieldDirty($field)
    {
        $field = strtolower($field);
        return ($this->content[$field] !== $this->content_db[$field]);
    }

    /**
     * reverts value of given field to last restored value
     * 
     * @param string $field
     * @return mixed the restored value
     */
    public function revertValue($field)
    {
        $field = strtolower($field);
        return ($this->content[$field] = $this->content_db[$field]);
    }
}
