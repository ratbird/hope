<?php

/*
 * Copyright (C) 2010 - Marcus Lunzenauer <mlunzena@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once dirname(__FILE__) . '/../bootstrap.php';
require_once 'lib/functions.php';

class StringWrapper {
    function __construct($string)
    {
        $this->string = $string;
    }
    function __toString()
    {
        return (string) $this->string;
    }
}

class FunctionsTest extends PHPUnit_Framework_TestCase {

  function testWords() {
    $string = "one two three";
    $this->assertEquals(array('one', 'two', 'three'), words($string));
  }

  function testWordsWithEmptyString() {
    $string = "";
    $this->assertEquals(array(), words($string));
  }

    function testArrayFlatten()
    {
        $array = json_decode(
            '[[1, 2], [3, [4], [[5, 6]]], 7]'
        );

        $this->assertEquals(range(1, 7), array_flatten($array));
    }

    function testUTF8Encoding()
    {
        $pairs = array(
            array(null, null),
            array(true, true),
            array(false, false),
            array(23, 23),
            array(1/9998, 1/9998),
            array('äbc', "\xc3\xa4bc"),
        );

        // an object responding to __toString
        $pairs[] = array(new StringWrapper("äbc"), "\xc3\xa4bc");

        // an array
        $array_data = array(null, 23, 'äbc');
        $array_expected = array(null, 23, "\xc3\xa4bc");
        $pairs[] = array($array_data, $array_expected);

        // something Traversable
        $traversable = new ArrayIterator($array_data);
        $pairs[] = array($traversable, $array_expected);


        foreach ($pairs as $pair) {
            list($data, $expected) = $pair;
            $this->assertEquals($expected, studip_utf8encode_recursive($data));
        }
    }
}
