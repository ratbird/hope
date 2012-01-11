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

class FunctionsTest extends PHPUnit_Framework_TestCase {

  function testWords() {
    $string = "one two three";
    $this->assertEquals(words($string), array('one', 'two', 'three'));
  }

  function testWordsWithEmptyString() {
    $string = "";
    $this->assertEquals(words($string), array());
  }

    function testArrayFlatten()
    {
        $array = json_decode(
            '[[1, 2], [3, [4], [[5, 6]]], 7]'
        );

        $this->assertEquals(range(1, 7), array_flatten($array));
    }
}
