<?php
/**
 * MultiDimArrayObject
 *
 *
 * @author      Andr� Noack <noack@data-quest.de>
 *
 */
class MultiDimArrayObject extends StudipArrayObject
{
    /**
     * Constructor
     *
     * @param array  $input
     * @param int    $flags
     * @param string $iteratorClass
     */
    public function __construct($input = array(), $flags = self::STD_PROP_LIST, $iteratorClass = 'ArrayIterator')
    {
        parent::__construct(array(), $flags, $iteratorClass);
        $this->exchangeArray($input);
    }

    /**
     * Appends the value
     *
     * @param  mixed $value
     * @return void
     */
    public function append($value)
    {
        $this->offsetSet(null, $value);
    }

    /**
     * Exchange the array for another one.
     *
     * @param  array|ArrayObject $data
     * @return
     */
    public function exchangeArray($data)
    {
        if (!is_array($data) && !is_object($data)) {
            throw new InvalidArgumentException('Passed variable is not an array or object, using empty array instead');
        }

        if (is_object($data) && ($data instanceof \StudipArrayObject)) {
            $data = $data->getArrayCopy();
        }
        if (!is_array($data)) {
            $data = (array) $data;
        }

        $this->storage = $this->recursiveArrayToArrayObjects($data);

    }

    /**
     * Creates a copy of the ArrayObject.
     *
     * @return array
     */
    public function getArrayCopy()
    {
        $ret = array();
        foreach($this->storage as $key => $value) {
            if ($value instanceOf StudipArrayObject) {
                $ret[$key] = $value->getArrayCopy();
            } else {
                $ret[$key] = $value;
            }
        }
        return $ret;
    }

    /**
     * Create a new iterator from an ArrayObject instance
     *
     * @return \Iterator
     */
    public function getIterator()
    {
        $class = $this->iteratorClass;

        return new $class($this->getArrayCopy());
    }

    /**
     * Sets the value at the specified key to value
     *
     * @param  mixed $key
     * @param  mixed $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        if (is_null($key)) {
            return $this->storage[] = $this->recursiveArrayToArrayObjects($value);
        }
        $this->storage[$key] = $this->recursiveArrayToArrayObjects($value);
    }

    protected function recursiveArrayToArrayObjects($data)
    {
        if ($data instanceOf StudipArrayObject) {
            $data = $data->getArrayCopy();
        }
        if (is_array($data)) {
            $new_data = array();
            foreach ($data as $key => $value) {
                $new_value = $this->recursiveArrayToArrayObjects($value);
                if (is_array($new_value)) {
                    $new_data[$key] = new self($new_value, $this->getFlags(), $this->getIteratorClass());
                } else {
                    $new_data[$key] = $value;
                }
            }
            return $new_data;
        }
        return $data;
    }
}

