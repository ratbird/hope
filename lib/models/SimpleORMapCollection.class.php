<?php
if (!defined('SORT_NATURAL')) define('SORT_NATURAL', 6);
if (!defined('SORT_FLAG_CASE')) define('SORT_FLAG_CASE', 8);
/**
 * SimpleORMapCollection.class.php
 * simple object-relational mapping
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Noack <noack@data-quest.de>
 * @copyright   2012 Stud.IP Core-Group
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
*/
class SimpleORMapCollection extends ArrayObject
{

    /**
     * the record object this collection belongs to
     *
     * @var SimpleORMap
     */
    protected $related_record;

    /**
     * relation options
     * @var array
     */
    protected $relation_options = array();

    /**
     * callable to initialize collection
     *
     * @var Closure
     */
    protected $finder;

    /**
     * number of records after last init
     *
     * @var int
     */
    protected $last_count;

    /**
     * collection with deleted records
     * @var SimpleORMapCollection
     */
    protected $deleted;

    /**
     * creates a collection from an array of objects
     * all objects must be of the same type
     *
     * @param array $data array with SimpleORMap objects
     * @return SimpleORMapCollection
     */
    public static function createFromArray(Array $data)
    {
        $ret = new SimpleORMapCollection();
        if ($data[0] instanceof SimpleORMap) {
            $ret->setClassName(get_class($data[0]));
            foreach ($data as $one) {
                $ret[] = $one;
            }
        }
        return $ret;
    }

    /**
     * Constructor
     *
     * @param Closure $finder callable to fill collection
     * @param array $options relationship options
     * @param SimpleORMap $record related record
     */
    function __construct(Closure $finder = null, Array $options = null, SimpleORMap $record = null)
    {
        $this->finder = $finder;
        $this->relation_options = $options;
        $this->related_record = $record;
        $this->deleted = clone $this;
        $this->refresh();
    }

    /**
     *
     * @see ArrayObject::append()
     */
    function append($value)
    {
        return $this->offsetSet(null, $value);
    }

    /**
     * Sets the value at the specified index
     * checks if the value is an object of specified class
     *
     * @see ArrayObject::offsetSet()
     * @throws InvalidArgumentException if the given model does not fit (wrong type or id)
     */
    function offsetSet($index, $newval)
    {
        if (!is_null($index)) {
            $index = (int)$index;
        }
        if (strtolower(get_class($newval)) !== $this->getClassName()) {
            throw new InvalidArgumentException('This collection only accepts objects of type: ' .  $this->getClassName());
        }
        if ($this->related_record && $this->relation_options['type'] === 'has_many') {
            $foreign_key_value = call_user_func($this->relation_options['assoc_func_params_func'], $this->related_record);
            call_user_func($this->relation_options['assoc_foreign_key_setter'], $newval, $foreign_key_value);
        }
        if ($newval->id !== null) {
            $exists = $this->find($newval->id);
            if ($exists) {
                throw new InvalidArgumentException('Element could not be appended, element with id: ' . $exists->id . ' is in the way');
            }
        }
        return parent::offsetSet($index, $newval);
    }

    /**
     * Unsets the value at the specified index
     * value is moved to internal deleted collection
     *
     * @see ArrayObject::offsetUnset()
     * @throws InvalidArgumentException
     */
    function offsetUnset($index)
    {
        if ($this->offsetExists($index)) {
            $this->deleted[] = $this->offsetGet($index);
        }
        return parent::offsetUnset($index);
    }
    /**
     * sets the finder function
     *
     * @param Closure $finder
     */
    function setFinder(Closure $finder)
    {
        $this->finder = $finder;
    }

    /**
     * sets the allowed class name
     * @param string $class_name
     */
    function setClassName($class_name)
    {
        $this->relation_options['class_name'] = strtolower($class_name);
        $this->deleted->relation_options['class_name'] = strtolower($class_name);
    }

    /**
     * sets the related record
     *
     * @param SimpleORMap $record
     */
    function setRelatedRecord(SimpleORMap $record)
    {
        $this->related_record = $record;
    }

    /**
     * gets the allowed classname
     *
     * @return string
     */
    function getClassName()
    {
        return strtolower($this->relation_options['class_name']);
    }

    /**
     * get deleted records collection
     * @return SimpleORMapCollection
     */
    function getDeleted()
    {
        return $this->deleted;
    }

    /**
     * reloads the elements of the collection
     * by calling the finder function
     *
     * @throws Exception
     * @return number of records after refresh
     */
    function refresh()
    {
        if (is_callable($this->finder)) {
            $data = call_user_func($this->finder, $this->related_record);
            foreach ($data as $one) {
                if (strtolower(get_class($one)) !== $this->getClassName()) {
                    throw new Exception('This collection only accepts objects of type: ' .  $this->getClassName());
                }
            }
            $this->exchangeArray($data);
            $this->deleted->exchangeArray(array());
            return $this->last_count = $this->count();
        }
    }

    /**
     * calls the given method on all elements
     * of the collection
     * @param string $method methodname to call
     * @param array $params parameters for methodcall
     * @return array of all return values
     */
    function sendMessage($method, $params = array()) {
        $results = array();
        foreach ($this as $record) {
            $results[] = call_user_func_array(array($record, $method), $params);
        }
        return $results;
    }

    /**
     * returns element with given primary key value
     *
     * @param string $value primary key value to search for
     * @return SimpleORMap
     */
    function find($value)
    {
        return $this->findBy('id', $value)->first();
    }

    /**
     * returns a new collection containing all elements
     * where given column has given value(s)
     * pass array or space-delimited string for multiple values
     *
     * @param string $key the column name
     * @param mixed $value value to search for,
     * @return SimpleORMapCollection with found records
     */
    function findBy($key, $values)
    {
        if (!is_array($values)) {
            $values = words($values);
        }
        return $this->filter(function($record) use ($key, $values) {return in_array($record->$key, $values);});
    }

    /**
     * apply given callback to all elements of
     * collection
     *
     * @param Closure $func the function to call
     * @return int addition of return values
     */
    function each(Closure $func)
    {
        $result = false;
        foreach ($this as $record) {
            $result+= call_user_func($func, $record);
        }
        return $result;
    }

    /**
     * apply given callback to all elements of
     * collection and give back array of return values
     *
     * @param Closure $func the function to call
     * @return array
     */
    function map(Closure $func)
    {
        $results = array();
        foreach ($this as $record) {
            $results[] = call_user_func($func, $record);
        }
        return $results;
    }

    /**
     * filter elements
     * if given callback returns true
     *
     * @param Closure $func the function to call
     * @return SimpleORMapCollection containing filtered elements
     */
    function filter(Closure $func = null)
    {
        $results = array();
        foreach ($this as $record) {
            if (call_user_func($func, $record)) {
                $results[] = $record;
            }
        }
        return self::createFromArray($results);
    }

    /**
     * extract array of columns values
     * pass array or space-delimited string for multiple columns
     *
     * @param string|array $columns the column(s) to extract
     * @return array of extracted values
     */
    function pluck($columns)
    {
        if (!is_array($columns)) {
            $columns = words($columns);
        }
        $func = function($r) use ($columns) {
            $result = array();
            foreach ($columns as $c) {
                $result[] = $r->{$c};
            }
            return $result;
        };
        $result = $this->map($func);
        return count($columns) === 1 ? array_map('current', $result) : $result;
    }

    /**
     * returns the collection as grouped array
     * first param is the column to group by, it becomes the key in
     * the resulting array, default is pk. Limit returned fields with second param
     * The grouped entries can optoionally go through the given
     * callback. If no callback is provided, only the first grouped
     * entry is returned, suitable for grouping by unique column
     *
     * @param string $group_by the column to group by, pk if ommitted
     * @param mixed $only_these_fields limit returned fields
     * @param Closure $group_func closure to aggregate grouped entries
     * @return array assoc array
     */
    function toGroupedArray($group_by = 'id', $only_these_fields = null, Closure $group_func = null)
    {
        $result = array();
        foreach ($this as $record) {
            $key = $record->getValue($group_by);
            if (is_array($key)) {
                $key = join('_', $key);
            }
            $result[$key][] = $record->toArray($only_these_fields);
        }
        if ($group_func === null) {
            $group_func = 'current';
        }
        return array_map($group_func, $result);
    }

    /**
     * get the first element
     *
     * @return SimpleORMap first element or null
     */
    function first()
    {
        return $this->offsetGet(0);
    }

    /**
     * get the last element
     *
     * @return SimpleORMap last element or null
     */
    function last()
    {
        return $this->offsetGet($this->count() ? $this->count() - 1 : 0);
    }

     /**
     * get the the value from given key from first element
     *
     * @return mixed
     */
    function val($key)
    {
        return $this->first()->$key;
    }

    /**
     * mark element(s) for deletion
     * element(s) with given primary key are moved to
     * internal deleted collection
     *
     * @param string $id primary key of element
     * @return  number of unsetted elements
     */
    function unsetByPk($id)
    {
        return $this->unsetBy('id', $id);
    }

    /**
     * mark element(s) for deletion
     * where given column has given value(s)
     * pass array or space-delimited string for multiple values
     * element(s) are moved to
     * internal deleted collection
     *
     * @param string $key
     * @param mixed $values
     * @return number of unsetted elements
     */
    function unsetBy($key, $values)
    {
        $ret = false;
        if (!is_array($values)) {
            $values = words($values);
        }
        foreach ($this as $k => $record) {
            if (in_array($record->$key, $values)) {
                $this->offsetunset($k);
                $ret += 1;
            }
        }
        return $ret;
    }
    
    function orderBy($order, $sort_flags = SORT_STRING)
    {
         //('name asc, nummer desc ')
        switch ($sort_flags) {
            case SORT_NATURAL:
                $sort_func = 'natcmp';
            break;
            case SORT_NATURAL & SORT_FLAG_CASE:
                $sort_func = 'natcasecmp';
            break;
            case SORT_STRING & SORT_FLAG_CASE:
                $sort_func = 'strcasecmp';
            break;
            default:
                $sort_func = 'strcmp';
        }
        $sorter = array();
        foreach (explode(',', strtolower($order)) as $one) {
            $sorter[] = array_map('trim', explode(' ', $one));
        }
        
        $func = function ($d1, $d2) use ($sorter, $sort_func) {
            do {
                list($field, $dir) = current($sorter);
                $ret = $sort_func($d1[$field], $d2[$field]);
                if ($dir == 'desc') $ret = $ret * -1;
            } while ($ret === 0 && next($sorter));
            
            return $ret;
        };
        if (count($sorter)) {
            $this->uasort($func);
        }
        return $this;
    }
}