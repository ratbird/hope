<?php
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
    private $content = array();
    private $is_new = true;
    
    protected $db_table = '';
    protected $db_fields = null;
    protected $pk = null;
    
    protected static $schemes;
    
    public static function TableScheme ($db_table)
    {
        if (self::$schemes === null) {
            $cache = StudipCacheFactory::getCache();
            self::$schemes = unserialize($cache->read('DB_TABLE_SCHEMES'));
        }
        if (!isset(self::$schemes[$db_table])) {
            $db = DBManager::get()->query("SHOW COLUMNS FROM $db_table");
            while($rs = $db->fetch(PDO::FETCH_ASSOC)){
                $db_fields[strtolower($rs['Field'])] = array
                (
                									   'name' => $rs['Field'],
									             	   'type' => $rs['Type'],
									                   'key'  => $rs['Key']
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
    
    public static function find($class, $id)
    {
        $record = new $class($id);
        if(!$record->isNew()){
            return $record;
        } else {
            return null;
        }
    }
    
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
    
    function getTableScheme()
    {
        if(self::TableScheme($this->db_table)){
            $this->db_fields =& self::$schemes[$this->db_table]['db_fields'];
            $this->pk =& self::$schemes[$this->db_table]['pk'];
        }
    }
    
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
    
    
    function getData()
    {
        if ($this->haveData()) {
            return $this->toArray();
        } else {
            return FALSE;
        }
    }
    
    function toArray()
    {
        $ret = array();
        foreach(array_map('strtolower', array_keys($this->db_fields)) as $field) {
           $ret[$field] = $this->$field;
        }
        return $ret;
    }
    
    function getValue($field)
    {
    	$field = strtolower($field);
        return (array_key_exists($field, $this->content) ? $this->content[$field] : null);
    }
    
    function setValue($field, $value)
    {
    	$field = strtolower($field);
        $ret = false;
        if($this->db_fields[$field]){
            if (is_float($value)) $value = str_replace(',','.',$value);
            $ret = ($this->content[$field] = $value);
        }
        return $ret;
    }
    
    function __get($field)
    {
        return $this->getValue($field);
    }
    
    function __set($field, $value)
    {
         return $this->setValue($field, $value);
    }
    
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
    
    public function count()
    {
    	return count($this->content);
    }
    
    function isField($field)
    {
    	$field = strtolower($field);
        return isset($this->db_fields[$field]);
    }
    
    function setData($data, $reset = false)
    {
        $count = 0;
        if ($reset){
            $this->content = array();
        }
        if (is_array($data)){
            foreach($data as $key => $value){
            	$key = strtolower($key);
                if(isset($this->db_fields[$key])){
                    $this->content[$key] = $value;
                    ++$count;
                }
            }
        }
        return $count;
    }
    
    function haveData()
    {
        return count($this->content);
    }
    
    function isNew()
    {
        return $this->is_new;
    }
    
    function setNew($is_new)
    {
        return $this->is_new = $is_new;
    }
    
    function getWhereQuery()
    {
        $where_query = null;
        $pk_not_set = array();
        foreach ($this->pk as $key){
            if (isset($this->content[$key])){
                $where_query[] = "`{$this->db_table}`.`{$key}`" . "='{$this->content[$key]}'";
            } else {
                $pk_not_set[] = $key;
            }
        }
        if (!$where_query || count($pk_not_set)){
            return false;
        }
        return $where_query;
    }
    
    function restore()
    {
        $where_query = $this->getWhereQuery();
        if ($where_query){
            $query = "SELECT * FROM `{$this->db_table}` WHERE "
                    . join(" AND ", $where_query);
            $rs = DBManager::get()->query($query)->fetchAll(PDO::FETCH_ASSOC);
            if (isset($rs[0])) {
                if ($this->setData($rs[0], true)){
                    $this->setNew(false);
                    return true;
                } else {
                    $this->setNew(true);
                    return false;
                }
            }
        } else {
            $this->setNew(true);
            return FALSE;
        }
    }
    
    function store()
    {
        
        if ($this->isNew() && !$this->getId()) {
            $this->setId($this->getNewId());
        }

        $where_query = $this->getWhereQuery();

        foreach ($this->content as $key => $value) {
            if (is_float($value)) $value = str_replace(',','.',$value);
            if (isset($this->db_fields[$key]) && $key != 'chdate' && $key != 'mkdate'){
                $query_part[] = "`$key` = " . DBManager::get()->quote($value) . " ";
            }
        }
        
        if ($where_query){
            if (!$this->isNew()){
                $query = "UPDATE `{$this->db_table}` SET "
                    . implode(',', $query_part);
                $query .= " WHERE ". join(" AND ", $where_query);
                if ($ret = DBManager::get()->exec($query)){
                    $this->triggerChdate();
                }
            } else {
            $query = "INSERT INTO `{$this->db_table}` SET "
                    . implode(',', $query_part);
            if ($this->db_fields['mkdate']){
                $query .= " ,mkdate=UNIX_TIMESTAMP()";
            }
            if ($this->db_fields['chdate']){
                $query .= " , chdate=UNIX_TIMESTAMP()";
            }
            $ret = DBManager::get()->exec($query);
            }
            $this->restore();
            return $ret;
        } else {
            return false;
        }
    }
    
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
}
