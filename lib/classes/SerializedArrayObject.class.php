<?php
/**
 * This class works like an array.
 * The internal array is constructed from a php serialized string
 * When used in an string context, it is automatically serialized
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      André Noack <noack@data-quest.de>
 * @link http://www.php.net/manual/en/class.arrayobject.php
 */
class SerializedArrayObject extends MultiDimArrayObject
{
    /**
     * Construct an array object from a php serialized string
     *
     * @param string $input a php-serialized string
     */
    function __construct($input)
    {
        if (is_string($input)) {
            $input = (array)unserialize($input);
        }
        parent::__construct((array)$input);
    }

    /**
     * magic method for use of object in string context
     *
     * @return string internal array php serialized
     */
    function __toString()
    {
        return serialize($this->getArrayCopy());
    }
}