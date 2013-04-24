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
        $first = current($data);
        if ($first instanceof SimpleORMap) {
            $ret->setClassName(get_class($first));
            foreach ($data as $one) {
                $ret[] = $one;
            }
        }
        return $ret;
    }

    public static function getCompFunc($operator, $args)
    {
        if (!is_array($args)) {
            $args = array($args);
        }
        switch ($operator) {
            case '==':
                $comp_func = function ($a) use ($args) {return in_array($a, $args);};
            case '===':
                $comp_func = function ($a) use ($args) {return in_array($a, $args, true);};
            break;
            case '!=':
            case '<>':
                $comp_func = function ($a) use ($args) {return !in_array($a, $args);};
            break;
            case '!==':
                $comp_func = function ($a) use ($args) {return !in_array($a, $args, true);};
            break;
            case '<':
            case '>':
            case '<=':
            case '>=':
                $op_func = create_function('$a,$b', 'return $a ' . $operator . ' $b;');
                $comp_func = function($a) use ($op_func, $args) {return $op_func($a, $args[0]);};
            break;
            case '><':
                $comp_func = function($a) use ($args) {return $a >= $args[0] && $a <= $args[1];};
            break;
            case '%=':
                $comp_func = function($a) use ($args) {
                    $a = strtolower(SimpleOrMapCollection::translitLatin1($a));
                    $args = array_map('SimpleOrMapCollection::translitLatin1', $args);
                    $args = array_map('strtolower', $args);
                    return in_array($a, $args);
                };
            break;
            default:
                throw new InvalidArgumentException('unknown operator: ' . $operator);
         }
         return $comp_func;
    }

    public static function translitLatin1($text) {
        if (!preg_match('/[\200-\377]/', $text)) {
            return $text;
        }
        $text = str_replace(array('ä','A','ö','Ö','ü','Ü','ß'), array('a','A','o','O','u','U','s'), $text);
        $text = str_replace(array('À','Á','Â','Ã','Å','Æ'), 'A' , $text);
        $text = str_replace(array('à','á','â','ã','å','æ'), 'a' , $text);
        $text = str_replace(array('È','É','Ê','Ë'), 'E' , $text);
        $text = str_replace(array('è','é','ê','ë'), 'e' , $text);
        $text = str_replace(array('Ì','Í','Î','Ï'), 'I' , $text);
        $text = str_replace(array('ì','í','î','ï'), 'i' , $text);
        $text = str_replace(array('Ò','Ó','Õ','Ô','Ø'), 'O' , $text);
        $text = str_replace(array('ò','ó','ô','õ','ø'), 'o' , $text);
        $text = str_replace(array('Ù','Ú','Û'), 'U' , $text);
        $text = str_replace(array('ù','ú','û'), 'u' , $text);
        $text = str_replace(array('Ç','ç','Ð','Ñ','Ý','ñ','ý','ÿ'), array('C','c','D','N','Y','n','y','y') , $text);
        return $text;
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
        return $this->findOneBy('id', $value);
    }

    /**
     * returns a new collection containing all elements
     * where given column has given value(s)
     * pass array for multiple values
     *
     * @param string $key the column name
     * @param mixed $value value to search for,
     * @return SimpleORMapCollection with found records
     */
    function findBy($key, $values, $op = '==')
    {
        $comp_func = self::getCompFunc($op, $values);
        return $this->filter(function($record) use ($comp_func, $key) {return $comp_func($record[$key]);});
    }

    /**
     * returns the first element
     * where given column has given value(s)
     * pass array for multiple values
     *
     * @param string $key the column name
     * @param mixed $value value to search for,
     * @return SimpleORMap found record
     */
    function findOneBy($key, $values, $op = '==')
    {
        $comp_func = self::getCompFunc($op, $values);
        return $this->filter(function($record) use ($comp_func, $key) {return $comp_func($record[$key]);}, 1)->first();
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
        foreach ($this as $key => $value) {
            $results[$key] = call_user_func($func, $value, $key);
        }
        return $results;
    }

    /**
     * filter elements
     * if given callback returns true
     *
     * @param Closure $func the function to call
     * @param integer $limit limit number of found records
     * @return SimpleORMapCollection containing filtered elements
     */
    function filter(Closure $func = null, $limit = null)
    {
        $results = array();
        $found = 0;
        foreach ($this as $key => $value) {
            if (call_user_func($func, $value, $key)) {
                $results[$key] = $value;
                if ($limit && (++$found == $limit)) {
                    break;
                }
            }
        }
        $ret = new SimpleORMapCollection();
        $ret->exchangeArray($results);
        return $ret;
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
        $keys = array_keys($this->getArrayCopy());
        $first_offset = reset($keys);
        return $this->offsetGet($first_offset ?: 0);
    }

    /**
     * get the last element
     *
     * @return SimpleORMap last element or null
     */
    function last()
    {
        $keys = array_keys($this->getArrayCopy());
        $last_offset = end($keys);
        return $this->offsetGet($last_offset ?: 0);
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
    function unsetBy($key, $values, $op = '==')
    {
        $ret = false;
        $comp_func = self::getCompFunc($op, $values);
        foreach ($this as $k => $record) {
            if ($comp_func($record[$key])) {
                $this->offsetunset($k);
                $ret += 1;
            }
        }
        return $ret;
    }

    function orderBy($order, $sort_flags = SORT_LOCALE_STRING)
    {
        //('name asc, nummer desc ')
        $sort_locale = false;
        switch ($sort_flags) {
        case SORT_NATURAL:
            $sort_func = 'strnatcmp';
            break;
        case SORT_NATURAL | SORT_FLAG_CASE:
            $sort_func = 'strnatcasecmp';
            break;
        case SORT_STRING | SORT_FLAG_CASE:
            $sort_func = 'strcasecmp';
            break;
        case SORT_STRING:
            $sort_func = 'strcmp';
            break;
        case SORT_NUMERIC:
            $sort_func = function($a,$b) {return (int)$a-(int)$b;};
            break;
        case SORT_LOCALE_STRING:
        default:
            $sort_func = 'strnatcasecmp';
            $sort_locale = true;
        }

        $sorter = array();
        foreach (explode(',', strtolower($order)) as $one) {
            $sorter[] = array_map('trim', explode(' ', $one));
        }

        $func = function ($d1, $d2) use ($sorter, $sort_func, $sort_locale) {
            do {
                list($field, $dir) = current($sorter);
                if (!$sort_locale) {
                    $value1 = $d1[$field];
                    $value2 = $d2[$field];
                } else {
                    $value1 = SimpleOrMapCollection::translitLatin1(substr($d1[$field], 0, 10));
                    $value2 = SimpleOrMapCollection::translitLatin1(substr($d2[$field], 0, 10));
                }
                $ret = $sort_func($value1, $value2);
                if ($dir == 'desc') $ret = $ret * -1;
            } while ($ret === 0 && next($sorter));

            return $ret;
        };
        if (count($sorter)) {
            $this->uasort($func);
        }
        return $this;
    }

    function limit($offset, $row_count = null)
    {
        if (is_null($row_count)) {
            if ($offset > 0) {
                $row_count = $offset;
                $offset = 0;
            } else {
                $row_count = abs($offset);
            }
        }
        $ret = new SimpleORMapCollection();
        $ret->exchangeArray(array_slice($this->getArrayCopy(), $offset, $row_count));
        return $ret;
    }
}